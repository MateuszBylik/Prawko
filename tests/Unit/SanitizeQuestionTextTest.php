<?php

namespace Tests\Unit;

use App\Console\Commands\ImportQuestionBank;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Reguła czyszcząca treść pytań przy imporcie. Testujemy ją w izolacji (bez bazy),
 * bo to czysta funkcja tekstowa — a jej wartość leży w tym, czego NIE tnie.
 */
class SanitizeQuestionTextTest extends TestCase
{
    private function sanitize(string $text, array &$stats = null, array &$samples = null): string
    {
        $stats ??= ['text_cleaned' => 0];
        $samples ??= [];

        $method = new ReflectionMethod(ImportQuestionBank::class, 'sanitizeText');

        return $method->invokeArgs(
            new ImportQuestionBank(),
            [$text, '10816', &$stats, &$samples]
        );
    }

    /** Realny przypadek: pytanie #10816 z wydania 2026-07-14. */
    public function test_obcina_urwany_duplikat_poczatku_tresci(): void
    {
        $broken = "Policjant zatrzyma prawo jazdy za pokwitowaniem w przypadku ujawnienia czynu polegającego na:\n\n\nPolicjant zatrzyma";
        $stats = ['text_cleaned' => 0];
        $samples = [];

        $clean = $this->sanitize($broken, $stats, $samples);

        $this->assertSame(
            'Policjant zatrzyma prawo jazdy za pokwitowaniem w przypadku ujawnienia czynu polegającego na:',
            $clean
        );
        $this->assertSame(1, $stats['text_cleaned']);
        $this->assertStringContainsString('Policjant zatrzyma', $samples[0], 'Cięcie musi zostawić ślad w raporcie.');
    }

    /**
     * Najważniejszy test: pojedyncze `\n` to normalne zawijanie wiersza (#11050,
     * #13118) i nie wolno go tknąć. Gdyby reguła dzieliła na `\n+` zamiast `\n{2,}`,
     * treść tych pytań zostałaby okaleczona.
     */
    public function test_nie_rusza_pojedynczego_zawijania_wiersza(): void
    {
        $wrapped = "Znajdujesz się w obszarze zabudowanym. Czy w tej sytuacji jesteś zobowiązany\nzmniejszyć prędkość, a w razie potrzeby zatrzymać się?";

        $this->assertSame($wrapped, $this->sanitize($wrapped));
    }

    public function test_nie_rusza_zwyklej_tresci(): void
    {
        $plain = 'Czy w tej sytuacji masz obowiązek ustąpić pierwszeństwa?';

        $this->assertSame($plain, $this->sanitize($plain));
    }

    /** Akapit, który NIE jest powtórzeniem początku, musi zostać. */
    public function test_nie_rusza_prawdziwego_akapitu_po_pustej_linii(): void
    {
        $twoParagraphs = "Zbliżasz się do skrzyżowania.\n\nCzy masz obowiązek ustąpić pierwszeństwa tramwajowi?";

        $this->assertSame($twoParagraphs, $this->sanitize($twoParagraphs));
    }

    /** Ogon identyczny z resztą to inny przypadek (pełny duplikat) — nie nasza reguła. */
    public function test_nie_rusza_pelnego_duplikatu(): void
    {
        $full = "Czy wolno tu zawracać?\n\nCzy wolno tu zawracać?";

        $this->assertSame($full, $this->sanitize($full));
    }

    /** Ogon niebędący prefiksem początku to normalna treść, choćby był krótki. */
    public function test_nie_rusza_ogona_ktory_nie_jest_prefiksem(): void
    {
        $text = "Policjant zatrzyma prawo jazdy w przypadku:\n\nWybierz jedną odpowiedź.";

        $this->assertSame($text, $this->sanitize($text));
    }

    public function test_nie_zglasza_ciecia_gdy_nic_nie_zmieniono(): void
    {
        $stats = ['text_cleaned' => 0];
        $samples = [];

        $this->sanitize('Zwykłe pytanie bez pułapek?', $stats, $samples);

        $this->assertSame(0, $stats['text_cleaned']);
        $this->assertSame([], $samples);
    }
}
