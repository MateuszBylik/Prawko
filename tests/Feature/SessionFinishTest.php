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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionFinishTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
    }

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    /** Sesja z dwoma pytaniami: pozycja 1 (nie ostatnia) i 2 (ostatnia). */
    private function makeSession(User $user, ExamMode $mode): ExamSession
    {
        $cat = $this->category();

        $session = ExamSession::create([
            'user_id' => $user->id,
            'driving_category_id' => $cat->id,
            'mode' => $mode,
            'status' => ExamStatus::InProgress,
            'total_questions' => 2,
            'max_score' => 6,
            'started_at' => now(),
        ]);

        foreach ([1, 2] as $pos) {
            $q = Question::create([
                'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
                'type' => 'basic', 'points' => 3, 'text' => "Pytanie $pos?",
                'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
            ]);
            $q->categories()->attach($cat->id);
            Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'A']);
            Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'B']);

            $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => $pos]);
        }

        return $session;
    }

    public function test_diagnoza_ostatnie_pytanie_konczy_sesje_i_pokazuje_wynik(): void
    {
        $user = $this->user();
        $session = $this->makeSession($user, ExamMode::Diagnosis);
        $last = $session->answers()->where('position', 2)->first();

        $response = $this->actingAs($user)->post(
            route('sessions.answer', [$session, 2]),
            ['answer_id' => $last->question->answers->first()->id]
        );

        // Wcześniej: min($position+1, $total) → przekierowanie na TO SAMO pytanie (ślepy zaułek).
        $response->assertRedirect(route('sessions.result', $session));
        $this->assertTrue($session->fresh()->isCompleted(), 'Ostatnia odpowiedź musi domknąć diagnozę.');
    }

    public function test_diagnoza_wczesniejsze_pytanie_idzie_dalej_a_nie_konczy(): void
    {
        $user = $this->user();
        $session = $this->makeSession($user, ExamMode::Diagnosis);
        $first = $session->answers()->where('position', 1)->first();

        $this->actingAs($user)
            ->post(route('sessions.answer', [$session, 1]), ['answer_id' => $first->question->answers->first()->id])
            ->assertRedirect(route('sessions.question', [$session, 2]));

        $this->assertFalse($session->fresh()->isCompleted());
    }

    public function test_na_ostatnim_pytaniu_przycisk_konczy_a_osobny_znika(): void
    {
        $user = $this->user();
        $session = $this->makeSession($user, ExamMode::Diagnosis);

        $last = $this->actingAs($user)->get(route('sessions.question', [$session, 2]))->assertOk()->getContent();
        $earlier = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();

        // Ostatnie: główny przycisk kończy sesję, osobnego (ghost) nie ma.
        $this->assertStringContainsString('Zakończ sesję', $last);
        $this->assertStringNotContainsString('Zapisz i dalej', $last);
        $this->assertStringNotContainsString("confirm('Zakończyć sesję", $last);

        // Wcześniejsze: normalne „dalej" + dyskretny przycisk przerwania.
        $this->assertStringContainsString('Zapisz i dalej', $earlier);
        $this->assertStringContainsString("confirm('Zakończyć sesję", $earlier);
    }

    public function test_wynik_diagnozy_pokazuje_popelnione_bledy(): void
    {
        $user = $this->user();
        $session = $this->makeSession($user, ExamMode::Diagnosis);

        // Pytanie 1 źle, pytanie 2 dobrze.
        $a1 = $session->answers()->where('position', 1)->first();
        $this->actingAs($user)->post(route('sessions.answer', [$session, 1]), [
            'answer_id' => $a1->question->answers->firstWhere('is_correct', false)->id,
        ]);
        $a2 = $session->answers()->where('position', 2)->first();
        $this->actingAs($user)->post(route('sessions.answer', [$session, 2]), [
            'answer_id' => $a2->question->answers->firstWhere('is_correct', true)->id,
        ]);

        $html = $this->actingAs($user)->get(route('sessions.result', $session))->assertOk()->getContent();

        $this->assertStringContainsString('Twoje błędy', $html);
        $this->assertStringContainsString('Pytanie 1?', $html, 'Błędne pytanie musi być widoczne.');
        $this->assertStringContainsString('Poprawna:', $html, 'Musi pokazać poprawną odpowiedź.');
        $this->assertStringContainsString('Podgląd pytania i odpowiedzi', $html);
    }
}
