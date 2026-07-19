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

/**
 * Tabliczka o wadzie katalogu MI (pytania 10898/10899 — próg „o dwa" vs „o więcej
 * niż 2" z art. 135 ust. 2 PoRD).
 *
 * Sedno testów: tabliczka podaje LITERĘ odpowiedzi katalogowej, więc wolno jej
 * istnieć wyłącznie tam, gdzie odpowiedź i tak jest już odsłonięta. Pokazana przed
 * udzieleniem odpowiedzi byłaby ściągą — na egzaminie wprost oddawałaby 2 punkty.
 */
class DisputedAnswerTest extends TestCase
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

    private function disputedQuestion(string $externalId = '10898'): Question
    {
        $q = Question::create([
            'external_id' => $externalId, 'type' => 'specialist', 'points' => 2,
            'text' => 'Za które naruszenie przepisów ruchu drogowego policjant zatrzyma prawo jazdy?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($this->category()->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Za wyprzedzanie…', 'is_correct' => false, 'label' => 'A', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Za przewożenie osób…o dwa…', 'is_correct' => true, 'label' => 'B', 'position' => 2]);
        Answer::create(['question_id' => $q->id, 'text' => 'Za wymijanie…', 'is_correct' => false, 'label' => 'C', 'position' => 3]);

        return $q->load('answers');
    }

    private function plainQuestion(): Question
    {
        $q = Question::create([
            'external_id' => '99999', 'type' => 'specialist', 'points' => 2,
            'text' => 'Zwykłe pytanie bez zastrzeżeń?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($this->category()->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'A', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'B', 'position' => 2]);

        return $q->load('answers');
    }

    private function makeSession(User $user, ExamMode $mode, Question $q): ExamSession
    {
        $session = ExamSession::create([
            'user_id' => $user->id, 'driving_category_id' => $this->category()->id,
            'mode' => $mode, 'status' => ExamStatus::InProgress,
            'total_questions' => 1, 'max_score' => $q->points, 'started_at' => now(),
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        return $session;
    }

    public function test_konfiguracja_zna_oba_sporne_pytania(): void
    {
        foreach (['10898', '10899'] as $id) {
            $d = config('prawko.disputed.'.$id);

            $this->assertIsArray($d, "Pytanie {$id} musi być oznaczone jako sporne.");
            $this->assertSame('B', $d['catalog_answer']);
            $this->assertStringContainsString('art. 135', $d['basis']);
        }
    }

    public function test_przegladarka_pytan_pokazuje_tabliczke(): void
    {
        $q = $this->disputedQuestion();

        $html = $this->actingAs($this->user())->get(route('questions.show', $q))->assertOk()->getContent();

        $this->assertStringContainsString('Pytanie sporne', $html);
        $this->assertStringContainsString('Na egzaminie zaznacz B', $html, 'Praktyczna instrukcja musi być na wierzchu.');
        $this->assertStringContainsString('art. 135', $html);
    }

    public function test_zwykle_pytanie_nie_dostaje_tabliczki(): void
    {
        $html = $this->actingAs($this->user())
            ->get(route('questions.show', $this->plainQuestion()))->assertOk()->getContent();

        $this->assertStringNotContainsString('Pytanie sporne', $html);
    }

    /** W nauce tabliczka pojawia się dopiero po sprawdzeniu odpowiedzi. */
    public function test_nauka_pokazuje_tabliczke_dopiero_po_odpowiedzi(): void
    {
        $user = $this->user();
        $q = $this->disputedQuestion();
        $session = $this->makeSession($user, ExamMode::Practice, $q);

        $before = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();
        $this->assertStringNotContainsString('Pytanie sporne', $before, 'Przed odpowiedzią tabliczka byłaby ściągą.');

        $this->actingAs($user)->post(route('sessions.answer', [$session, 1]), [
            'answer_id' => $q->answers->firstWhere('label', 'A')->id,
            'confidence' => 'sure',
        ]);

        $after = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();
        $this->assertStringContainsString('Pytanie sporne', $after);
    }

    /**
     * Najważniejsze: ekran pytania egzaminacyjnego NIE MOŻE pokazywać tabliczki —
     * podałaby literę odpowiedzi, czyli oddała 2 punkty i zepsuła pomiar.
     */
    public function test_ekran_pytania_egzaminu_nie_zdradza_tabliczki(): void
    {
        $user = $this->user();
        $q = $this->disputedQuestion();
        $session = $this->makeSession($user, ExamMode::Exam, $q);

        $html = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();

        $this->assertStringNotContainsString('Pytanie sporne', $html);
        $this->assertStringNotContainsString('Na egzaminie zaznacz', $html);
    }

    /** …ale w podsumowaniu egzaminu, gdzie odpowiedzi są już odsłonięte, musi być. */
    public function test_podsumowanie_egzaminu_pokazuje_tabliczke(): void
    {
        $user = $this->user();
        $q = $this->disputedQuestion();
        $session = $this->makeSession($user, ExamMode::Exam, $q);

        $session->answers()->first()->update([
            'is_correct' => false, 'answered_at' => now(),
            'selected_answer_id' => $q->answers->firstWhere('label', 'A')->id,
        ]);
        $session->update(['status' => ExamStatus::Completed, 'completed_at' => now(), 'score' => 0]);

        $html = $this->actingAs($user)->get(route('sessions.result', $session))->assertOk()->getContent();

        $this->assertStringContainsString('Pytanie sporne', $html);
        $this->assertStringContainsString('art. 135', $html);
    }
}
