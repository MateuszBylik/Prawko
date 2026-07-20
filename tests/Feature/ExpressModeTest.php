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
 * Tryb ekspresowy: przed egzaminem dowieźć KAŻDE pytanie kategorii do stanu
 * „utrwalone lub odpowiedziane poprawnie" = streak ≥ 1 (streak zeruje się przy
 * błędzie, więc ≥ 1 znaczy „ostatnio poprawnie"). Niespełnione = brak stanu
 * albo ostatnio błędnie. Dostępny w oknie planu tylko w ostatnim tygodniu.
 */
class ExpressModeTest extends TestCase
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

    /** „Spełnione" — ostatnia odpowiedź poprawna (streak ≥ 1; tu utrwalone: 2). */
    private function markSatisfied(User $user, Question $q): void
    {
        QuestionUserStat::create([
            'user_id' => $user->id, 'question_id' => $q->id,
            'times_seen' => 2, 'streak' => 2, 'stability' => 30.0, 'difficulty' => 5.0,
            'interval_days' => 30, 'last_reviewed_at' => now(), 'due_at' => now()->addDays(30),
        ]);
    }

    /** Widziane, ale ostatnio błędnie (streak = 0) — wciąż NIESPEŁNIONE. */
    private function markLastWrong(User $user, Question $q): void
    {
        QuestionUserStat::create([
            'user_id' => $user->id, 'question_id' => $q->id,
            'times_seen' => 1, 'streak' => 0, 'stability' => 1.0, 'difficulty' => 6.0,
            'interval_days' => 1, 'last_reviewed_at' => now(), 'due_at' => now()->addDay(),
        ]);
    }

    public function test_ekspres_pomija_spelnione_a_bierze_niespelnione(): void
    {
        $user = $this->student();
        $satisfied = $this->makeQuestion('9001');
        $this->markSatisfied($user, $satisfied);
        $wrong = $this->makeQuestion('9002');
        $this->markLastWrong($user, $wrong);
        $new = $this->makeQuestion('9003'); // brak stanu

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'practice', 'scope' => 'express'])
            ->assertRedirect();

        $qids = $user->examSessions()->latest('id')->first()->answers()->pluck('question_id');
        $this->assertFalse($qids->contains($satisfied->id), 'Utrwalone/poprawne nie może wejść do ekspresu.');
        $this->assertTrue($qids->contains($wrong->id), 'Ostatnio błędne musi wejść do ekspresu.');
        $this->assertTrue($qids->contains($new->id), 'Nowe (bez stanu) musi wejść do ekspresu.');
    }

    public function test_ekspres_bez_niespelnionych_daje_osobny_komunikat(): void
    {
        $user = $this->student();
        $this->markSatisfied($user, $this->makeQuestion('9010')); // jedyne pytanie, już spełnione

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'practice', 'scope' => 'express'])
            ->assertRedirect()
            ->assertSessionHas('error', __('enums.errors.express_done'));

        $this->assertSame(0, $user->examSessions()->count());
    }

    public function test_scope_express_nie_dziala_dla_egzaminu(): void
    {
        $user = $this->student();
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $q = Question::create([
                    'external_id' => (string) fake()->unique()->numberBetween(20000, 99999),
                    'type' => $type->value, 'points' => $points, 'text' => 'P?',
                    'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
                ]);
                $q->categories()->attach($this->category()->id);
                Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T', 'position' => 1]);
                $this->markSatisfied($user, $q); // wszystko spełnione — dla egzaminu bez znaczenia
            }
        }

        // Egzamin ma stały skład i ignoruje scope → sesja powstaje mimo „wszystko spełnione".
        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'exam', 'scope' => 'express'])
            ->assertRedirect();

        $this->assertSame(1, $user->examSessions()->where('mode', 'exam')->count());
    }

    public function test_dashboard_pokazuje_ekspres_w_ostatnim_tygodniu(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
            'skill_theta' => 1.0,
            'exam_date' => now()->addDays(3)->toDateString(),
        ]);
        for ($i = 0; $i < 20; $i++) {
            $this->makeQuestion("95{$i}");
        }
        $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'diagnosis', 'status' => 'completed',
            'total_questions' => 20, 'max_score' => 40, 'score' => 30, 'correct_count' => 15,
            'started_at' => now()->subHour(), 'completed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.express.title'));
    }

    public function test_dashboard_ukrywa_ekspres_gdy_daleko_do_egzaminu(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
            'skill_theta' => 1.0,
            'exam_date' => now()->addDays(30)->toDateString(), // > tydzień
        ]);
        for ($i = 0; $i < 20; $i++) {
            $this->makeQuestion("96{$i}");
        }
        $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'diagnosis', 'status' => 'completed',
            'total_questions' => 20, 'max_score' => 40, 'score' => 30, 'correct_count' => 15,
            'started_at' => now()->subHour(), 'completed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('dashboard.express.title'));
    }

    public function test_przelacznik_zmienia_kafelek_dawki_na_ekspres(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
            'skill_theta' => 1.0,
            'exam_date' => now()->addDays(3)->toDateString(),
        ]);
        for ($i = 0; $i < 20; $i++) {
            $this->makeQuestion("97{$i}"); // 20 niespełnionych (bez stanu)
        }
        $user->examSessions()->create([
            'driving_category_id' => $this->category()->id, 'mode' => 'diagnosis', 'status' => 'completed',
            'total_questions' => 20, 'max_score' => 40, 'score' => 30, 'correct_count' => 15,
            'started_at' => now()->subHour(), 'completed_at' => now()->subHour(),
        ]);

        // Domyślnie OFF → kafelek dawki normalny, bez przycisku ekspresowego.
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('dashboard.express.start', ['n' => 20]));

        // Włączamy przełącznik.
        $this->actingAs($user)->post(route('plan.express'), ['express_mode' => 1])->assertRedirect();
        $this->assertTrue($user->fresh()->express_mode);

        // ON → kafelek staje się ekspresowy (przycisk „Ćwicz ekspresowo").
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.express.start', ['n' => 20]));
    }
}
