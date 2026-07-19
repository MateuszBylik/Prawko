<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\ForecastSnapshot;
use App\Models\User;
use App\Services\ForecastHistoryService;
use App\Support\ExamForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ForecastHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function forecast(float $pass, int $answered = 50): ExamForecast
    {
        return new ExamForecast(
            passProbability: $pass, expectedScore: 60.0, medianScore: 60, ciLow: 54, ciHigh: 66,
            threshold: 68, maxScore: 74, coverage: 0.42, samples: 10000, answered: $answered,
        );
    }

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]);
    }

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now(), 'skill_theta' => 1.5]);
    }

    public function test_zapisuje_migawke_z_dzisiejsza_data(): void
    {
        $user = $this->user();
        $cat = $this->category();

        app(ForecastHistoryService::class)->record($user, $cat, $this->forecast(0.42));

        $snap = ForecastSnapshot::first();
        $this->assertNotNull($snap);
        $this->assertSame(0.42, $snap->pass_probability);
        $this->assertSame(42.0, $snap->coverage_percent);
        $this->assertSame(now()->toDateString(), $snap->snapshot_date->toDateString());
        $this->assertSame(42, (int) round($snap->passPercent()));
    }

    public function test_drugi_zapis_tego_samego_dnia_nadpisuje_zamiast_duplikowac(): void
    {
        $user = $this->user();
        $cat = $this->category();
        $svc = app(ForecastHistoryService::class);

        $svc->record($user, $cat, $this->forecast(0.42));
        $svc->record($user, $cat, $this->forecast(0.55));

        $this->assertSame(1, ForecastSnapshot::count(), 'Na dzień może być tylko jedna migawka.');
        $this->assertSame(0.55, ForecastSnapshot::first()->pass_probability);
    }

    public function test_nie_zapisuje_migawki_bez_sygnalu(): void
    {
        // Za mało odpowiedzi = prognoza to niemal sam prior; taki punkt zaśmieciłby wykres.
        app(ForecastHistoryService::class)->record($this->user(), $this->category(), $this->forecast(0.5, answered: 3));

        $this->assertSame(0, ForecastSnapshot::count());
    }

    public function test_historia_zwraca_serie_od_najstarszej(): void
    {
        $user = $this->user();
        $cat = $this->category();

        foreach ([[10, 0.20], [5, 0.35], [1, 0.60]] as [$daysAgo, $p]) {
            ForecastSnapshot::create([
                'user_id' => $user->id, 'driving_category_id' => $cat->id,
                'snapshot_date' => now()->subDays($daysAgo)->toDateString(),
                'pass_probability' => $p, 'coverage_percent' => 40, 'answered_count' => 50,
            ]);
        }

        $history = app(ForecastHistoryService::class)->history($user, $cat);

        $this->assertCount(3, $history);
        $this->assertSame([20.0, 35.0, 60.0], $history->map(fn ($s) => $s->passPercent())->all());
    }

    public function test_historia_pomija_migawki_starsze_niz_okno(): void
    {
        $user = $this->user();
        $cat = $this->category();

        foreach ([200, 3] as $daysAgo) {
            ForecastSnapshot::create([
                'user_id' => $user->id, 'driving_category_id' => $cat->id,
                'snapshot_date' => now()->subDays($daysAgo)->toDateString(),
                'pass_probability' => 0.5, 'coverage_percent' => 40, 'answered_count' => 50,
            ]);
        }

        $this->assertCount(1, app(ForecastHistoryService::class)->history($user, $cat, days: 90));
    }

    public function test_wykres_renderuje_sie_z_seria_danych(): void
    {
        $user = $this->user();
        $cat = $this->category();

        foreach ([[10, 0.20], [5, 0.35], [1, 0.67]] as [$daysAgo, $p]) {
            ForecastSnapshot::create([
                'user_id' => $user->id, 'driving_category_id' => $cat->id,
                'snapshot_date' => now()->subDays($daysAgo)->toDateString(),
                'pass_probability' => $p, 'coverage_percent' => 40, 'answered_count' => 50,
            ]);
        }

        $history = app(ForecastHistoryService::class)->history($user, $cat);
        $html = Blade::render('<x-forecast-chart :history="$history" :target="0.95" />', ['history' => $history]);

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('cel 95%', $html);
        $this->assertStringContainsString('67%', $html, 'Końcówka musi być etykietowana wprost.');
        $this->assertStringContainsString(__('dashboard.chart.table'), $html, 'Dostępny odpowiednik wykresu.');
        // Cel jest kreskowany (to próg), ale siatka NIE — kreskowana siatka to szum.
        $this->assertSame(1, substr_count($html, 'stroke-dasharray'));
        // Nigdy liczba przy każdym punkcie — tylko końcówka.
        $this->assertSame(1, substr_count($html, 'text-xs font-bold'));
        $this->assertStringNotContainsString('20%</text>', $html);
    }

    public function test_wykres_z_jednym_punktem_nie_wywala_sie(): void
    {
        $user = $this->user();
        $cat = $this->category();

        ForecastSnapshot::create([
            'user_id' => $user->id, 'driving_category_id' => $cat->id,
            'snapshot_date' => now()->toDateString(),
            'pass_probability' => 0.42, 'coverage_percent' => 40, 'answered_count' => 50,
        ]);

        $history = app(ForecastHistoryService::class)->history($user, $cat);
        $html = Blade::render('<x-forecast-chart :history="$history" />', ['history' => $history]);

        // Jeden punkt: bez linii i bez dzielenia przez zero przy skalowaniu osi X.
        $this->assertStringContainsString('42%', $html);
        $this->assertStringContainsString('Wykres zacznie rosnąć', $html);
    }

    public function test_historia_nie_miesza_uzytkownikow(): void
    {
        $cat = $this->category();
        $mine = $this->user();
        $other = $this->user();

        foreach ([$mine, $other] as $u) {
            ForecastSnapshot::create([
                'user_id' => $u->id, 'driving_category_id' => $cat->id,
                'snapshot_date' => now()->toDateString(),
                'pass_probability' => 0.5, 'coverage_percent' => 40, 'answered_count' => 50,
            ]);
        }

        $this->assertCount(1, app(ForecastHistoryService::class)->history($mine, $cat));
    }
}
