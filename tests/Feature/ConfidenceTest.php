<?php

namespace Tests\Feature;

use App\Enums\Confidence;
use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use App\Services\CalibrationService;
use App\Services\KnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfidenceTest extends TestCase
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

    private function question(string $type = 'basic', int $points = 3): Question
    {
        $q = Question::create([
            'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => $type, 'points' => $points, 'text' => 'Czy wolno?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($this->category()->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'A']);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'B']);

        return $q->load('answers');
    }

    private function makeSession(User $user, ExamMode $mode, Question $q): ExamSession
    {
        $session = ExamSession::create([
            'user_id' => $user->id,
            'driving_category_id' => $this->category()->id,
            'mode' => $mode,
            'status' => ExamStatus::InProgress,
            'total_questions' => 1,
            'max_score' => $q->points,
            'started_at' => now(),
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        return $session;
    }

    public function test_nauka_zapisuje_zadeklarowana_pewnosc(): void
    {
        $user = $this->user();
        $q = $this->question();
        $session = $this->makeSession($user, ExamMode::Practice, $q);

        $this->actingAs($user)->post(route('sessions.answer', [$session, 1]), [
            'answer_id' => $q->answers->firstWhere('is_correct', true)->id,
            'confidence' => 'guess',
        ]);

        $this->assertSame(Confidence::Guess, $session->answers()->first()->confidence);
    }

    /**
     * Egzamin próbny ma zostać 1:1 z arkuszem WORD — nie pytamy tam o pewność.
     * Test celowo DOSYŁA pole, którego formularz nie wysyła: gdyby kontroler je
     * przyjął, tryb egzaminu przestałby być wierną symulacją tylnymi drzwiami.
     */
    public function test_egzamin_ignoruje_doslana_pewnosc(): void
    {
        $user = $this->user();
        $q = $this->question();
        $session = $this->makeSession($user, ExamMode::Exam, $q);

        $this->actingAs($user)->post(route('sessions.answer', [$session, 1]), [
            'answer_id' => $q->answers->firstWhere('is_correct', true)->id,
            'confidence' => 'sure',
        ]);

        $this->assertNull($session->answers()->first()->confidence);
    }

    public function test_diagnoza_ignoruje_doslana_pewnosc(): void
    {
        $user = $this->user();
        $q = $this->question();
        $session = $this->makeSession($user, ExamMode::Diagnosis, $q);

        $this->actingAs($user)->post(route('sessions.answer', [$session, 1]), [
            'answer_id' => $q->answers->firstWhere('is_correct', true)->id,
            'confidence' => 'sure',
        ]);

        $this->assertNull($session->answers()->first()->confidence);
    }

    /**
     * Sedno wpięcia w FSRS: trafiony strzał nie może budować pamięci tak jak
     * wiedza. Bez tego szybkie, przypadkowe trafienie dostawało ocenę Easy
     * i odsuwało powtórkę o tygodnie.
     */
    public function test_trafiony_strzal_daje_slabsza_stabilnosc_niz_pewna_odpowiedz(): void
    {
        $sureUser = $this->user();
        $guessUser = $this->user();
        $q = $this->question();
        $knowledge = app(KnowledgeService::class);

        // Ta sama odpowiedź, ten sam (szybki) czas — różni je wyłącznie deklaracja.
        $sure = $knowledge->record($sureUser->id, $q, true, 5000, Confidence::Sure);
        $guess = $knowledge->record($guessUser->id, $q, true, 5000, Confidence::Guess);

        $this->assertLessThan(
            $sure->stability,
            $guess->stability,
            'Strzał musi dawać mniejszą stabilność niż deklarowana wiedza.'
        );
        $this->assertTrue($guess->due_at->lessThan($sure->due_at), 'Zgadywane pytanie musi wrócić szybciej.');

        // Strzał to nie wpadka — nie liczymy go jako lapsu ani nie cofamy do relearningu.
        // Czytamy z bazy: licznik lapsów jest nietykany, więc na modelu zostaje null.
        $this->assertSame(0, (int) $guess->fresh()->lapses);
        $this->assertSame(2, $guess->fsrs_state, 'Trafiony strzał to nadal powtórka, nie wpadka.');
    }

    public function test_bledna_odpowiedz_jest_wpadka_niezaleznie_od_deklaracji(): void
    {
        $user = $this->user();
        $q = $this->question();

        $stat = app(KnowledgeService::class)->record($user->id, $q, false, 5000, Confidence::Sure);

        $this->assertSame(1, $stat->lapses);
        $this->assertSame(3, $stat->fsrs_state, 'Błąd = relearning, także gdy deklarowałeś pewność.');
    }

    public function test_kalibracja_liczy_tabele_2x2_i_poziom_przypadku(): void
    {
        $user = $this->user();
        $basic = $this->question('basic', 3);        // TAK/NIE → przypadek 50%
        $specialist = $this->question('specialist', 2); // A/B/C → przypadek 33%
        $session = $this->makeSession($user, ExamMode::Practice, $basic);

        $rows = [
            [$basic, Confidence::Sure, true],
            [$basic, Confidence::Sure, false],
            [$basic, Confidence::Guess, true],
            [$specialist, Confidence::Guess, false],
        ];
        foreach ($rows as $i => [$q, $confidence, $correct]) {
            $session->answers()->create([
                'user_id' => $user->id, 'question_id' => $q->id, 'position' => $i + 10,
                'is_correct' => $correct, 'confidence' => $confidence, 'answered_at' => now(),
            ]);
        }

        $c = app(CalibrationService::class)->for($user);

        $this->assertSame(1, $c->sureCorrect);
        $this->assertSame(1, $c->sureWrong);
        $this->assertSame(1, $c->guessCorrect);
        $this->assertSame(1, $c->guessWrong);
        $this->assertEqualsWithDelta(0.5, $c->overconfidence(), 0.001);

        // Strzelał raz na TAK/NIE (1/2) i raz na A/B/C (1/3) → przypadek = 5/12.
        $this->assertEqualsWithDelta((0.5 + 1 / 3) / 2, $c->chanceLevel, 0.001);
        $this->assertEqualsWithDelta(0.5 - $c->chanceLevel, $c->guessLift(), 0.001);
    }

    public function test_kalibracja_pomija_odpowiedzi_bez_deklaracji(): void
    {
        $user = $this->user();
        $q = $this->question();
        $session = $this->makeSession($user, ExamMode::Exam, $q);

        $session->answers()->first()->update(['is_correct' => true, 'answered_at' => now()]);

        $c = app(CalibrationService::class)->for($user);

        $this->assertSame(0, $c->total(), 'Odpowiedzi z egzaminu (bez deklaracji) nie wchodzą do kalibracji.');
        $this->assertFalse($c->hasSignal());
        $this->assertSame('Za mało danych', $c->verdict()['label']);
    }

    public function test_pewne_bledy_sa_zwijane_do_unikalnych_pytan_z_licznikiem(): void
    {
        $user = $this->user();
        $q = $this->question();
        $session = $this->makeSession($user, ExamMode::Practice, $q);

        foreach ([1, 2, 3] as $i) {
            $session->answers()->create([
                'user_id' => $user->id, 'question_id' => $q->id, 'position' => $i + 10,
                'is_correct' => false, 'confidence' => Confidence::Sure, 'answered_at' => now()->addMinutes($i),
            ]);
        }

        $mistakes = app(CalibrationService::class)->sureMistakes($user);

        $this->assertCount(1, $mistakes, 'Ten sam haczyk to jedna pozycja, nie trzy.');
        $this->assertSame(3, $mistakes[0]['times']);
        $this->assertTrue($mistakes[0]['question']->is($q));
    }

    public function test_formularz_nauki_pyta_o_pewnosc_a_diagnoza_nie(): void
    {
        $user = $this->user();
        $q = $this->question();

        $practice = $this->actingAs($user)
            ->get(route('sessions.question', [$this->makeSession($user, ExamMode::Practice, $q), 1]))
            ->assertOk()->getContent();
        $diagnosis = $this->actingAs($user)
            ->get(route('sessions.question', [$this->makeSession($user, ExamMode::Diagnosis, $q), 1]))
            ->assertOk()->getContent();

        $this->assertStringContainsString('name="confidence" value="sure"', $practice);
        $this->assertStringContainsString('name="confidence" value="guess"', $practice);
        $this->assertStringNotContainsString('Sprawdź odpowiedź</button>', $practice);

        $this->assertStringNotContainsString('name="confidence"', $diagnosis);
    }
}
