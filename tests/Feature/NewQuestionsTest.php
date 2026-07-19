<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nauka nowych pytań: tryb „tylko nowe" i plakietka „nowe" w widoku nauki.
 *
 * Kontekst: domyślna nauka dobiera pytania wg ryzyka + powtórek, więc nowe pytania
 * z przyzwoitym priorytetem IRT prawie z niej nie wychodzą. Ten tryb i plakietka
 * dają świadome domykanie pokrycia puli.
 */
class NewQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    private function student(): User
    {
        return User::factory()->create([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
        ]);
    }

    private function makeQuestion(string $ext): Question
    {
        $q = Question::create([
            'external_id' => $ext, 'type' => 'basic', 'points' => 3, 'text' => "Pytanie {$ext}?",
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($this->category()->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'N', 'position' => 2]);

        return $q->load('answers');
    }

    /** „Widziane" = ma stan FSRS ze stabilnością > 0 (tak liczy plan i silnik nauki). */
    private function markSeen(User $user, Question $q): void
    {
        QuestionUserStat::create([
            'user_id' => $user->id, 'question_id' => $q->id,
            'times_seen' => 1, 'stability' => 12.0, 'difficulty' => 5.0,
            'interval_days' => 12, 'last_reviewed_at' => now(), 'due_at' => now()->addDays(12),
        ]);
    }

    /** Widziane i z zaległym terminem powtórki (bez domieszki wypełniłoby sesję). */
    private function markDue(User $user, Question $q): void
    {
        QuestionUserStat::create([
            'user_id' => $user->id, 'question_id' => $q->id,
            'times_seen' => 1, 'stability' => 2.0, 'difficulty' => 5.0,
            'interval_days' => 1, 'last_reviewed_at' => now()->subDays(2), 'due_at' => now()->subDay(),
        ]);
    }

    // ————————————————— Tryb „tylko nowe" —————————————————

    public function test_tryb_tylko_nowe_pomija_widziane(): void
    {
        $user = $this->student();
        $new = $this->makeQuestion('7001');
        $seen = $this->makeQuestion('7002');
        $this->markSeen($user, $seen);

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'practice', 'scope' => 'new'])
            ->assertRedirect();

        $qids = $user->examSessions()->latest('id')->first()->answers()->pluck('question_id');
        $this->assertTrue($qids->contains($new->id), 'Nowe pytanie powinno wejść do sesji.');
        $this->assertFalse($qids->contains($seen->id), 'Widziane pytanie nie może wejść do sesji „tylko nowe".');
    }

    public function test_brak_nowych_pytan_daje_osobny_komunikat(): void
    {
        $user = $this->student();
        $this->markSeen($user, $this->makeQuestion('7003')); // jedyne pytanie, już widziane

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'practice', 'scope' => 'new'])
            ->assertRedirect()
            ->assertSessionHas('error', __('enums.errors.no_new_questions'));

        $this->assertSame(0, $user->examSessions()->count());
    }

    public function test_scope_new_nie_dziala_dla_egzaminu(): void
    {
        $user = $this->student();
        // Dość pytań na arkusz egzaminu, wszystkie „widziane".
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $q = Question::create([
                    'external_id' => (string) fake()->unique()->numberBetween(20000, 99999),
                    'type' => $type->value, 'points' => $points, 'text' => 'P?',
                    'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
                ]);
                $q->categories()->attach($this->category()->id);
                Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T', 'position' => 1]);
                $this->markSeen($user, $q);
            }
        }

        // Egzamin ignoruje scope=new (ma stały skład) → sesja powstaje mimo „wszystko widziane".
        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'exam', 'scope' => 'new'])
            ->assertRedirect();

        $this->assertSame(1, $user->examSessions()->where('mode', 'exam')->count());
    }

    // ————————————————— Plakietka „nowe" —————————————————

    public function test_plakietka_nowe_dla_nowego_pytania(): void
    {
        $user = $this->student();
        $q = $this->makeQuestion('7004');
        $session = $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'practice', 'status' => 'in_progress',
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(), 'locale' => 'pl',
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        $this->actingAs($user)->get(route('sessions.question', [$session, 1]))
            ->assertOk()
            ->assertSee(__('session.new_badge'));
    }

    public function test_brak_plakietki_dla_pytania_z_wczesniejszej_sesji(): void
    {
        $user = $this->student();
        $q = $this->makeQuestion('7005');

        // Wcześniejsza sesja, w której już na to pytanie odpowiedziano.
        $prev = $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'practice', 'status' => 'completed',
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now()->subDay(), 'completed_at' => now()->subDay(), 'locale' => 'pl',
        ]);
        $prev->answers()->create([
            'user_id' => $user->id, 'question_id' => $q->id, 'position' => 1,
            'is_correct' => true, 'answered_at' => now()->subDay(),
        ]);

        // Bieżąca sesja z tym samym pytaniem — nie jest już „nowe".
        $cur = $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'practice', 'status' => 'in_progress',
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(), 'locale' => 'pl',
        ]);
        $cur->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        $this->actingAs($user)->get(route('sessions.question', [$cur, 1]))
            ->assertOk()
            ->assertDontSee(__('session.new_badge'));
    }

    public function test_zwykla_nauka_dorzuca_troche_nowych(): void
    {
        $user = $this->student();
        // 5 zaległych powtórek — bez domieszki wypełniłyby całą sesję size=5.
        for ($i = 0; $i < 5; $i++) {
            $this->markDue($user, $this->makeQuestion("810{$i}"));
        }
        // 10 nowych do dyspozycji.
        for ($i = 0; $i < 10; $i++) {
            $this->makeQuestion("820{$i}");
        }

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'practice', 'size' => 5])
            ->assertRedirect();

        $qids = $user->examSessions()->latest('id')->first()->answers()->pluck('question_id');
        $this->assertCount(5, $qids);

        $seenIds = QuestionUserStat::where('user_id', $user->id)->pluck('question_id')->all();
        $newInSession = $qids->reject(fn ($id) => in_array($id, $seenIds, true))->count();
        $this->assertGreaterThanOrEqual(1, $newInSession, 'Zwykła nauka musi dorzucić trochę nowych mimo zaległych powtórek.');
    }

    public function test_dashboard_pokazuje_przycisk_tylko_nowe_przy_dawce(): void
    {
        // Przycisk „Tylko nowe" mieszka w kafelku dawki — a ten wymaga daty egzaminu
        // i ukończonej diagnozy (onboarding wygrywa, dopóki diagnozy nie ma).
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
            'skill_theta' => 1.0,
            'exam_date' => now()->addDays(14)->toDateString(),
        ]);
        for ($i = 0; $i < 40; $i++) {
            $this->makeQuestion("83{$i}"); // 40 nowych → newCount = 40, przycisk pokaże (32)
        }
        $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'diagnosis', 'status' => 'completed',
            'total_questions' => 40, 'max_score' => 74, 'score' => 60, 'correct_count' => 30,
            'started_at' => now()->subHour(), 'completed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.start.only_new', ['n' => 32])); // min(40, 32)
    }
}
