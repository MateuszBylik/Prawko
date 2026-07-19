<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tłumaczenia TREŚCI pytań (kolumna `translations` z arkusza MI).
 *
 * Dwie rzeczy, na których łatwo się przewrócić i których nie widać z zewnątrz:
 *  1. locale `uk` odpowiada kluczowi danych `ua` — literówka w mapowaniu daje ciche
 *     odbicie na polski i nikt tego nie zauważy poza użytkownikiem ukraińskim,
 *  2. w `translations` jest klucz `pjm` z NAZWAMI PLIKÓW WMV, a nie tekstem —
 *     wyświetlony jako treść pytania byłby bełkotem.
 */
class QuestionTranslationTest extends TestCase
{
    use RefreshDatabase;

    private function specialist(): Question
    {
        $q = Question::create([
            'external_id' => '290', 'type' => 'specialist', 'points' => 2,
            'text' => 'Jaką odległość należy zachować?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
            'translations' => [
                'en' => ['q' => 'What distance should be kept?', 'a' => 'Not less than 800 metres.', 'b' => 'Not less than 500 metres.', 'c' => 'Not less than 200 metres.'],
                'de' => ['q' => 'Wie groß soll der Abstand sein?', 'a' => 'Nicht kleiner als 800 meter.', 'b' => 'Nicht kleiner als 500 meter.', 'c' => 'Nicht kleiner als 200 meter.'],
                'ua' => ['q' => 'Яку відстань слід дотримуватися?', 'a' => 'Не менше 800 метрів', 'b' => 'Не менше 500 метрів', 'c' => 'Не менше 200 метрів'],
                // Język migowy: NAZWY PLIKÓW, nie tekst. Nigdy nie może trafić na ekran.
                'pjm' => ['q' => 'pjm290.wmv', 'a' => 'pjm290a.wmv', 'b' => 'pjm290b.wmv', 'c' => 'pjm290c.wmv'],
            ],
        ]);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie mniejszą niż 800 m.', 'is_correct' => true, 'label' => 'A', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie mniejszą niż 500 m.', 'is_correct' => false, 'label' => 'B', 'position' => 2]);

        return $q->load('answers');
    }

    private function basic(): Question
    {
        $q = Question::create([
            'external_id' => '99', 'type' => 'basic', 'points' => 3,
            'text' => 'Czy masz obowiązek zatrzymać pojazd?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
            // Arkusz tłumaczy podstawowym tylko treść — „Tak/Nie” nie ma w danych.
            'translations' => [
                'en' => ['q' => 'Are you obliged to stop the vehicle?'],
                'ua' => ['q' => 'Чи зобовʼязаний ти зупинити транспортний засіб?'],
            ],
        ]);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'N', 'position' => 2]);

        return $q->load('answers');
    }

    public function test_tresc_pytania_idzie_za_jezykiem_interfejsu(): void
    {
        $q = $this->specialist();

        $expected = [
            'pl' => 'Jaką odległość należy zachować?',
            'en' => 'What distance should be kept?',
            'de' => 'Wie groß soll der Abstand sein?',
            'uk' => 'Яку відстань слід дотримуватися?',
        ];

        foreach ($expected as $locale => $text) {
            app()->setLocale($locale);
            $this->assertSame($text, $q->localizedText(), "Zła treść dla [{$locale}]");
        }
    }

    /** Klucz ukraiński w danych to `ua`, a locale to `uk` — najłatwiejsza pomyłka. */
    public function test_ukrainski_czyta_z_klucza_ua_a_nie_uk(): void
    {
        app()->setLocale('uk');

        $this->assertSame('Яку відстань слід дотримуватися?', $this->specialist()->localizedText());
    }

    public function test_warianty_specjalistyczne_tlumacza_sie_po_literze(): void
    {
        $q = $this->specialist();
        app()->setLocale('de');

        $this->assertSame('Nicht kleiner als 800 meter.', $q->localizedAnswerText($q->answers->firstWhere('label', 'A')));
        $this->assertSame('Nicht kleiner als 500 meter.', $q->localizedAnswerText($q->answers->firstWhere('label', 'B')));
    }

    /** Podstawowe: arkusz nie tłumaczy „Tak/Nie”, więc biorą je z plików językowych. */
    public function test_tak_nie_tlumacza_sie_mimo_braku_w_danych(): void
    {
        $q = $this->basic();

        foreach (['en' => ['Yes', 'No'], 'de' => ['Ja', 'Nein'], 'uk' => ['Так', 'Ні'], 'pl' => ['Tak', 'Nie']] as $locale => [$yes, $no]) {
            app()->setLocale($locale);
            $this->assertSame($yes, $q->localizedAnswerText($q->answers->firstWhere('label', 'T')), "TAK [{$locale}]");
            $this->assertSame($no, $q->localizedAnswerText($q->answers->firstWhere('label', 'N')), "NIE [{$locale}]");
        }
    }

    /** Brak tłumaczenia to normalny stan — lepiej polski tekst niż puste pytanie. */
    public function test_brak_tlumaczenia_odbija_sie_na_polski(): void
    {
        $q = $this->basic(); // nie ma klucza `de`
        app()->setLocale('de');

        $this->assertSame('Czy masz obowiązek zatrzymać pojazd?', $q->localizedText());
    }

    /** `pjm` to nazwy plików WMV — nie wolno ich pokazać jako treści w żadnym języku. */
    public function test_jezyk_migowy_nigdy_nie_trafia_na_ekran(): void
    {
        $q = $this->specialist();

        foreach (array_keys(config('locales')) as $locale) {
            app()->setLocale($locale);

            $this->assertStringNotContainsString('.wmv', $q->localizedText(), "PJM wyciekł w [{$locale}]");

            foreach ($q->answers as $a) {
                $this->assertStringNotContainsString('.wmv', $q->localizedAnswerText($a), "PJM wyciekł w wariancie [{$locale}]");
            }
        }
    }

    // ————————————————— Sekcja „tłumaczenia" na karcie pytania —————————————————

    /** Polski zawsze pierwszy, a wyświetlany język pomijamy (jest już w treści). */
    public function test_warianty_maja_polski_pierwszy_i_pomijaja_wyswietlany(): void
    {
        $q = $this->specialist();
        app()->setLocale('en');

        $variants = $q->languageVariants();

        $this->assertSame(['pl', 'de', 'uk'], array_column($variants, 'locale'));
        $this->assertSame('Jaką odległość należy zachować?', $variants[0]['q']);
        // Warianty A/B (pytanie ma tylko dwie odpowiedzi) z polskimi tekstami.
        $this->assertSame(['A', 'B'], array_keys($variants[0]['options']));
        $this->assertSame('Nie mniejszą niż 800 m.', $variants[0]['options']['A']);
    }

    /** W widoku polskim polski nie dubluje się w sekcji tłumaczeń. */
    public function test_w_polskim_widoku_polski_nie_dubluje_sie(): void
    {
        $q = $this->specialist();
        app()->setLocale('pl');

        $this->assertSame(['en', 'de', 'uk'], array_column($q->languageVariants(), 'locale'));
    }

    /** Język migowy (nazwy plików WMV) nie wchodzi do wariantów w żadnym języku. */
    public function test_warianty_nie_zawieraja_jezyka_migowego(): void
    {
        $q = $this->specialist();

        foreach (array_keys(config('locales')) as $locale) {
            app()->setLocale($locale);
            foreach ($q->languageVariants() as $v) {
                $this->assertStringNotContainsString('.wmv', $v['q'], "PJM w q [{$locale}]");
                foreach ($v['options'] as $text) {
                    $this->assertStringNotContainsString('.wmv', $text, "PJM w wariancie [{$locale}]");
                }
            }
        }
    }

    /** Podstawowe: brak tłumaczenia (tu `de`) pomijamy, a „Tak/Nie” nie są wariantami. */
    public function test_podstawowe_warianty_pomijaja_brak_i_tak_nie(): void
    {
        $q = $this->basic(); // tłumaczenia tylko en + ua
        app()->setLocale('pl');

        $variants = $q->languageVariants();

        $this->assertSame(['en', 'uk'], array_column($variants, 'locale'));
        foreach ($variants as $v) {
            $this->assertSame([], $v['options']);
        }
    }

    /** Konfiguracja jest jedynym miejscem mapowania locale → klucz danych. */
    public function test_mapowanie_jezykow_na_klucze_danych(): void
    {
        $this->assertNull(config('locales.pl.questions_key'), 'Polski to oryginał, nie tłumaczenie.');
        $this->assertSame('en', config('locales.en.questions_key'));
        $this->assertSame('de', config('locales.de.questions_key'));
        $this->assertSame('ua', config('locales.uk.questions_key'));
    }
}
