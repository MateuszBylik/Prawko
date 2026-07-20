<?php

namespace Tests\Feature;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wznawianie egzaminu ma sens tylko z realnym zapasem czasu. Wracać („Wznów sesję")
 * wolno wyłącznie, gdy zostało WIĘCEJ niż `exam.min_resume_seconds` (10 min). Poniżej
 * progu (albo po upływie 25 min) wejście domyka egzamin z obecnymi odpowiedziami —
 * bez wznawiania; nieudzielone pytania = 0 pkt.
 */
class ExamResumeTest extends TestCase
{
    use RefreshDatabase;

    private function student(): array
    {
        $cat = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
        $user = User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $cat->id]);

        return [$user, $cat];
    }

    private function examStartedAt(User $user, DrivingCategory $cat, CarbonInterface $startedAt): ExamSession
    {
        $q = Question::create([
            'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => 'basic', 'points' => 3, 'text' => 'P?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($cat->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T', 'position' => 1]);

        $session = ExamSession::create([
            'user_id' => $user->id, 'driving_category_id' => $cat->id,
            'mode' => ExamMode::Exam, 'status' => ExamStatus::InProgress,
            'total_questions' => 1, 'max_score' => 3,
            'time_limit_seconds' => 25 * 60, 'started_at' => $startedAt,
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        return $session;
    }

    public function test_wznowienie_z_zapasem_wraca_do_pytania(): void
    {
        [$user, $cat] = $this->student();
        $session = $this->examStartedAt($user, $cat, now()->subMinutes(5)); // ~20 min zostało

        $this->actingAs($user)->get(route('sessions.show', $session))
            ->assertRedirect(route('sessions.question', [$session, 1]));

        $this->assertFalse($session->fresh()->isCompleted(), 'Z zapasem czasu egzamin ma się dać wznowić.');
    }

    public function test_wznowienie_ponizej_progu_domyka_i_pokazuje_wynik(): void
    {
        [$user, $cat] = $this->student();
        $session = $this->examStartedAt($user, $cat, now()->subMinutes(20)); // ~5 min ≤ próg 10 min

        $this->actingAs($user)->get(route('sessions.show', $session))
            ->assertRedirect(route('sessions.result', $session));

        $this->assertTrue($session->fresh()->isCompleted(), 'Poniżej progu wznowienie domyka egzamin.');
    }

    public function test_wznowienie_po_limicie_bez_odpowiedzi_daje_zero_procent(): void
    {
        [$user, $cat] = $this->student();
        $session = $this->examStartedAt($user, $cat, now()->subMinutes(30)); // po 25 min

        $this->actingAs($user)->get(route('sessions.show', $session))
            ->assertRedirect(route('sessions.result', $session));

        $fresh = $session->fresh();
        $this->assertTrue($fresh->isCompleted());
        $this->assertSame(0, (int) $fresh->score);
        $this->assertSame(0, $fresh->scorePercent());
        $this->assertFalse((bool) $fresh->passed, 'Bez odpowiedzi egzamin jest niezdany (0%).');
    }
}
