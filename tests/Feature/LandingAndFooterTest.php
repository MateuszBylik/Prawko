<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAndFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_strona_glowna_dziala_dla_niezalogowanego(): void
    {
        $this->get('/')->assertOk()->assertSee('Rozwiąż całą oficjalną bazę', false);
    }

    /** Dane rejestrowe firmy — jeśli znikną, strona przestaje spełniać obowiązek informacyjny. */
    public function test_stopka_strony_glownej_ma_dane_firmy(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach ([
            'ZałapTo Mateusz Bylica',
            'ul. Heroldów 10B/53',
            '01-991 Warszawa',
            '7011290100',   // NIP
            '543455540',    // REGON
            'kontakt@zalapto.pl',
            '+48 22 247 26 19',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "Brak w stopce: {$needle}");
        }

        $this->assertStringContainsString('Wszelkie prawa zastrzeżone', $html);
    }

    /**
     * Numer rachunku jest w stopce zalapto.pl, ale świadomie go tu nie ma:
     * w aplikacji do nauki nie ma za co płacić, a opublikowany IBAN ułatwia
     * podszywanie się pod firmę. Test pilnuje, żeby nie wrócił przez przypadek.
     */
    public function test_stopka_nie_zawiera_numeru_rachunku(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('1090 1014', $html);
        $this->assertStringNotContainsString('Santander', $html);
    }

    public function test_stopka_aplikacji_ma_note_o_prawach_autorskich(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('© '.date('Y').' ZałapTo. Wszelkie prawa zastrzeżone.', $html);
    }

    /**
     * Strona reklamuje reguły egzaminu — muszą pochodzić z tej samej konfiguracji,
     * którą napędzana jest symulacja. Inaczej strona zacznie obiecywać co innego,
     * niż aplikacja robi.
     */
    public function test_reguly_egzaminu_na_stronie_zgadzaja_sie_z_konfiguracja(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $threshold = (int) config('prawko.exam.pass_threshold');
        $maxScore = collect(config('prawko.exam.composition'))->sum(fn ($l) => $l[1] * $l[2]);

        $this->assertSame(68, $threshold);
        $this->assertSame(74, $maxScore);
        $this->assertStringContainsString("{$threshold}/{$maxScore}", $html);
        $this->assertStringContainsString('próg zdania: '.$threshold, $html);
    }

    /** Rejestracja jest wyłączona — strona nie może obiecywać zakładania konta. */
    public function test_strona_nie_obiecuje_rejestracji(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('Zarejestruj', $html);
        $this->assertStringContainsString('Konta zakłada administrator', $html);
    }
}
