<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kompletność tłumaczeń.
 *
 * Laravel przy brakującym kluczu nie rzuca błędem — zwraca sam klucz, więc
 * w interfejsie pojawia się „dashboard.plan.save” zamiast napisu. To się
 * przechodzi niezauważone przy każdym ręcznym klikaniu w jednym języku.
 * Te testy renderują ekrany we WSZYSTKICH językach i szukają takich wycieków.
 */
class TranslationCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** Wzorzec „nieprzetłumaczony klucz”: plik.sekcja[.klucz] w widocznym tekście. */
    private const LEAK = '/\b(app|dashboard|enums|landing|losses|session|questions|admin|profile|category|exam_day)\.[a-z_]+(\.[a-z_]+)*\b/';

    /** @return string[] */
    private function leaks(string $html): array
    {
        // Patrzymy tylko na TEKST — atrybuty (klasy, ścieżki) zawierają kropki legalnie.
        $text = strip_tags($html);
        preg_match_all(self::LEAK, $text, $m);

        return array_values(array_unique($m[0]));
    }

    private function seedPool(): User
    {
        $cat = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochody osobowe', 'is_active' => true, 'sort_order' => 1]
        );

        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            for ($i = 0; $i < $count + 2; $i++) {
                $q = Question::create([
                    'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
                    'type' => $type->value, 'points' => $points, 'text' => 'Pytanie?',
                    'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
                ]);
                $q->categories()->attach($cat->id);
                Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);
                Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'N']);
            }
        }

        return User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $cat->id]);
    }

    public function test_pulpit_nie_wycieka_kluczy_w_zadnym_jezyku(): void
    {
        $user = $this->seedPool();
        // Data egzaminu na dziś → renderuje się też nakładka „jak poszło?", więc
        // ten test pilnuje i jej tłumaczeń we wszystkich językach.
        $user->forceFill(['exam_date' => today()])->save();

        foreach (array_keys(config('locales')) as $locale) {
            $user->forceFill(['locale' => $locale])->save();

            $html = $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk()->getContent();
            $leaks = $this->leaks($html);

            $this->assertSame([], $leaks, "Brakujące tłumaczenia [{$locale}]: ".implode(', ', $leaks));
        }
    }

    public function test_strona_glowna_nie_wycieka_kluczy_w_zadnym_jezyku(): void
    {
        foreach (array_keys(config('locales')) as $locale) {
            $this->get(route('locale.update', $locale));

            $html = $this->get('/')->assertOk()->getContent();
            $leaks = $this->leaks($html);

            $this->assertSame([], $leaks, "Brakujące tłumaczenia [{$locale}]: ".implode(', ', $leaks));
        }
    }

    public function test_ekran_wyboru_kategorii_nie_wycieka_kluczy(): void
    {
        $cat = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochody osobowe', 'is_active' => true, 'sort_order' => 1]
        );

        foreach (array_keys(config('locales')) as $locale) {
            $user = User::factory()->withoutCategory()->create(['role' => UserRole::Student, 'locale' => $locale]);

            $html = $this->actingAs($user)->get(route('category.show'))->assertOk()->getContent();

            $this->assertSame([], $this->leaks($html), "Brakujące tłumaczenia [{$locale}]");
        }
    }

    /**
     * Struktura plików musi być identyczna we wszystkich językach. Brakujący klucz
     * w jednym z nich to napis, który wyjdzie dopiero u użytkownika tego języka.
     */
    public function test_wszystkie_pliki_jezykowe_maja_te_same_klucze(): void
    {
        $flat = function (array $a, string $prefix = '') use (&$flat): array {
            $out = [];
            foreach ($a as $k => $v) {
                $key = $prefix ? "{$prefix}.{$k}" : (string) $k;
                $out += is_array($v) ? $flat($v, $key) : [$key => $v];
            }

            return $out;
        };

        // Pliki autorskie. `validation`/`auth`/`passwords` celowo pomijamy: dla nich
        // Laravel odkłada się per-klucz na fallback (angielski) i niepełny plik jest
        // świadomą decyzją (patrz lang/de/validation.php).
        foreach (['app', 'enums', 'landing', 'dashboard', 'status', 'session', 'questions', 'losses', 'admin', 'profile', 'auth_ui', 'exam_day', 'categories'] as $file) {
            $source = $flat(require lang_path("pl/{$file}.php"));

            foreach (['en', 'de', 'uk'] as $locale) {
                $path = lang_path("{$locale}/{$file}.php");
                $this->assertFileExists($path, "Brak pliku {$locale}/{$file}.php");

                $missing = array_diff(array_keys($source), array_keys($flat(require $path)));

                $this->assertSame([], array_values($missing),
                    "{$locale}/{$file}.php — brakuje kluczy: ".implode(', ', $missing));
            }
        }
    }

    /**
     * Ekrany nauki, wyników i panelu — te, które dopisano w kolejnych przebiegach.
     * Renderujemy je we wszystkich językach i szukamy wyciekających kluczy.
     */
    public function test_ekrany_panelu_nie_wyciekaja_kluczy(): void
    {
        $user = $this->seedPool();
        $cat = \App\Models\DrivingCategory::where('code', 'B')->first();

        // Sesja nauki z jednym pytaniem.
        $session = $user->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => 'practice', 'status' => 'in_progress',
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(),
        ]);
        $q = \App\Models\Question::active()->first();
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        foreach (array_keys(config('locales')) as $locale) {
            $user->forceFill(['locale' => $locale])->save();
            $fresh = $user->fresh();

            foreach ([
                route('questions.index'),
                route('questions.show', $q),
                route('losses.index'),
                route('sessions.question', [$session, 1]),
            ] as $url) {
                $html = $this->actingAs($fresh)->get($url)->assertOk()->getContent();
                $this->assertSame([], $this->leaks($html), "Wyciek na {$url} [{$locale}]");
            }
        }
    }

    public function test_panel_admina_nie_wycieka_kluczy(): void
    {
        $this->seedPool();

        foreach (array_keys(config('locales')) as $locale) {
            $admin = User::factory()->create(['role' => UserRole::Admin, 'locale' => $locale]);

            foreach ([route('admin.dashboard'), route('admin.users.index'), route('admin.users.create')] as $url) {
                $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
                $this->assertSame([], $this->leaks($html), "Wyciek na {$url} [{$locale}]");
            }
        }
    }
}
