<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\ExamForecastService;
use App\Services\LossBreakdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LossBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'skill_theta' => 1.2,
        ]);
    }

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    /**
     * Pula zgodna z założeniem silnika: KAŻDA warstwa ma więcej pytań, niż arkusz
     * z niej losuje (na produkcji to setki wobec ≤10). Gdyby pula była mniejsza
     * od `count`, Monte Carlo wylosowałoby tylko tyle, ile jest, a rachunek
     * (n/N)·pkt·(1−p) przestałby być prawdopodobieństwem — ten stan to zepsuty
     * import, nie sytuacja do modelowania.
     */
    private function seedPool(DrivingCategory $cat, int $perLayer = 12): void
    {
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $this->assertGreaterThanOrEqual($count, $perLayer, 'Pula warstwy musi być ≥ liczby losowanych pytań.');

            for ($i = 0; $i < $perLayer; $i++) {
                $q = Question::create([
                    'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
                    'type' => $type->value,
                    'points' => $points,
                    'text' => "Pytanie {$type->value} {$points} nr {$i}?",
                    'media_type' => 'none',
                    'is_active' => true,
                    'source' => 'catalog',
                    // Zróżnicowane b_q → zróżnicowane p_correct, inaczej test przechodzi
                    // trywialnie (wszystkie warstwy identyczne).
                    'irt_b' => -1.5 + $i * 0.25,
                ]);
                $q->categories()->attach($cat->id);
                Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'A']);
                Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'B']);
            }
        }
    }

    /**
     * Rdzeń zaufania do tego ekranu: rozbicie MUSI się zgadzać z prognozą.
     * Suma strat warstw = maxScore − oczekiwany wynik z Monte Carlo. Jeśli te
     * liczby się rozjadą, ekran zacznie mówić coś innego niż pulpit.
     */
    public function test_suma_strat_warstw_zgadza_sie_z_prognoza_monte_carlo(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $this->seedPool($cat);

        $breakdown = app(LossBreakdownService::class)->for($user, $cat);
        $forecast = app(ExamForecastService::class)->for($user, $cat);

        $sumLosses = array_sum(array_column($breakdown->layers, 'loss'));

        $this->assertSame($forecast->maxScore, $breakdown->maxScore);

        // Tolerancja 0,5 pkt: przy 10 000 symulacji błąd standardowy średniej
        // to ok. 0,05 pkt, więc to ~10 sigm — łapie realny rozjazd, nie szum.
        $this->assertEqualsWithDelta(
            $breakdown->maxScore - $forecast->expectedScore,
            $sumLosses,
            0.5,
            'Suma strat warstw musi równać się punktom, których Monte Carlo nie przyznaje.'
        );

        $this->assertEqualsWithDelta($forecast->expectedScore, $breakdown->expectedScore, 0.5);
        $this->assertEqualsWithDelta($breakdown->totalLoss(), $sumLosses, 0.001);
    }

    public function test_warstwy_sa_posortowane_malejaco_wg_straty(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $this->seedPool($cat);

        $losses = array_column(app(LossBreakdownService::class)->for($user, $cat)->layers, 'loss');
        $sorted = $losses;
        rsort($sorted);

        $this->assertSame($sorted, $losses, 'Ekran ma szeregować po utraconych punktach, nie po procencie.');
        $this->assertCount(count(config('prawko.exam.composition')), $losses);
    }

    /** Każda warstwa raportuje swoją pulę punktów zgodnie ze składem arkusza. */
    public function test_warstwy_odwzorowuja_sklad_arkusza(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $this->seedPool($cat);

        $breakdown = app(LossBreakdownService::class)->for($user, $cat);

        $expected = [];
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $expected[$type->value.'|'.$points] = $count * $points;
        }

        foreach ($breakdown->layers as $layer) {
            $key = $layer['type']->value.'|'.$layer['points'];
            $this->assertSame($expected[$key], $layer['pool_points']);
            $this->assertSame(12, $layer['pool_size']);
            // Strata nie może przekroczyć puli warstwy ani zejść poniżej zera.
            $this->assertGreaterThanOrEqual(0, $layer['loss']);
            $this->assertLessThanOrEqual($layer['pool_points'], $layer['loss']);
        }

        $this->assertSame(74, $breakdown->maxScore, 'Arkusz WORD to 74 pkt.');
        $this->assertSame(68, $breakdown->threshold);
    }

    /** Skuteczność z historii jest liczona z realnych odpowiedzi, per warstwa. */
    public function test_skutecznosc_z_historii_trafia_do_wlasciwej_warstwy(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $this->seedPool($cat);

        $q = Question::where('type', 'basic')->where('points', 3)->first();

        $session = $user->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => 'practice', 'status' => 'in_progress',
            'total_questions' => 2, 'max_score' => 6, 'started_at' => now(),
        ]);

        // Dwie odpowiedzi na pytanie z warstwy „podstawowe 3 pkt": jedna dobra, jedna zła.
        foreach ([true, false] as $i => $correct) {
            UserAnswer::create([
                'exam_session_id' => $session->id, 'user_id' => $user->id, 'question_id' => $q->id,
                'position' => $i + 1, 'is_correct' => $correct, 'answered_at' => now(),
            ]);
        }

        $breakdown = app(LossBreakdownService::class)->for($user, $cat);

        $layer = collect($breakdown->layers)
            ->first(fn ($l) => $l['type']->value === 'basic' && $l['points'] === 3);

        $this->assertSame(2, $layer['hist_n']);
        $this->assertSame(1, $layer['hist_correct']);
        $this->assertEqualsWithDelta(0.5, $layer['hist_accuracy'], 0.001);

        // Pozostałe warstwy nie mogą podbierać tych odpowiedzi.
        $other = collect($breakdown->layers)
            ->first(fn ($l) => $l['type']->value === 'specialist' && $l['points'] === 1);
        $this->assertSame(0, $other['hist_n']);
        $this->assertNull($other['hist_accuracy']);
    }

    public function test_ekran_strat_pokazuje_warstwy_i_dystans_do_progu(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $this->seedPool($cat);

        $html = $this->actingAs($user)->get(route('losses.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Straty wg warstwy arkusza', $html);
        $this->assertStringContainsString('Kalibracja pewności', $html);
        $this->assertStringContainsString('próg zdania: 68', $html);
        $this->assertStringContainsString('największa strata', $html);
    }

    public function test_ekran_strat_wymaga_zalogowania(): void
    {
        $this->get(route('losses.index'))->assertRedirect(route('login'));
    }
}
