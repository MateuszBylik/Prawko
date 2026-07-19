<?php

namespace App\Console\Commands;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

/**
 * Import oficjalnej bazy pytań egzaminacyjnych (kat. wszystkie) z pliku XLSX
 * dostarczonego przez Ministerstwo do bazy MariaDB.
 *
 * Idempotentny: kluczem jest `external_id` (kolumna „Numer pytania"), więc
 * ponowny import aktualizuje istniejące rekordy zamiast je duplikować.
 *
 * Arkusze:
 *   - „katalog"              → pytania aktywne (source=catalog, is_active=true)
 *   - „W trakcie weryfikacji"→ pytania w recenzji (source=review, is_active=false)
 */
class ImportQuestionBank extends Command
{
    protected $signature = 'prawko:import-questions
        {--file= : ścieżka do pliku XLSX (domyślnie storage/app/import/baza_pytan.xlsx)}
        {--fresh : usuń wszystkie pytania/odpowiedzi/powiązania przed importem}';

    protected $description = 'Importuje oficjalną bazę pytań egzaminacyjnych z pliku XLSX do bazy danych.';

    /** Indeks kolumn (0-based) w arkuszu. */
    private const COL = [
        'numer' => 1, 'text' => 2, 'a' => 3, 'b' => 4, 'c' => 5,
        'correct' => 6, 'media' => 7, 'type' => 8, 'points' => 9, 'categories' => 10,
        'pjm_q' => 11, 'pjm_a' => 12, 'pjm_b' => 13, 'pjm_c' => 14,
        'en_q' => 15, 'en_a' => 16, 'en_b' => 17, 'en_c' => 18,
        'de_q' => 19, 'de_a' => 20, 'de_b' => 21, 'de_c' => 22,
        'ua_q' => 23, 'ua_a' => 24, 'ua_b' => 25, 'ua_c' => 26,
    ];

    /** Arkusze do przetworzenia: nazwa => [source, is_active]. */
    private const SHEETS = [
        'katalog' => ['catalog', true],
        'W trakcie weryfikacji' => ['review', false],
    ];

    /** Ładne nazwy PL dla kategorii tworzonych „w locie", gdyby brakło w seederze. */
    private const CATEGORY_NAMES = [
        'AM' => 'Motorower, czterokołowiec lekki',
        'A1' => 'Motocykle do 125 cm³', 'A2' => 'Motocykle o mocy do 35 kW', 'A' => 'Motocykle',
        'B1' => 'Czterokołowce', 'B' => 'Samochody osobowe do 3,5 t',
        'C1' => 'Samochody ciężarowe 3,5–7,5 t', 'C' => 'Samochody ciężarowe powyżej 3,5 t',
        'D1' => 'Autobusy do 17 osób', 'D' => 'Autobusy',
        'T' => 'Ciągnik rolniczy', 'PT' => 'Tramwaj',
    ];

    /** Cache: kod kategorii => id (żeby nie odpytywać bazy w pętli). */
    private array $categoryIds = [];

    /** Zbiór dostępnych plików mediów (lowercase basename) — do raportu pokrycia. */
    private array $mediaFiles = [];

    public function handle(): int
    {
        @ini_set('memory_limit', '1024M');

        $path = $this->option('file') ?: storage_path('app/import/baza_pytan.xlsx');
        if (! is_file($path)) {
            $this->error("Nie znaleziono pliku XLSX: {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Czyszczę istniejące pytania, odpowiedzi i powiązania…');
            DB::table('category_question')->delete();
            Answer::query()->delete();
            Question::withTrashed()->forceDelete();
        }

        $this->indexMediaFiles();

        $this->info("Wczytuję arkusz: {$path}");
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $stats = [
            'imported' => 0, 'skipped' => 0, 'basic' => 0, 'specialist' => 0,
            'with_media' => 0, 'media_missing' => 0, 'bad_correct' => 0, 'deactivated' => 0,
            'text_cleaned' => 0,
        ];
        $missingSamples = [];
        $cleanedSamples = [];

        foreach (self::SHEETS as $sheetName => [$source, $isActive]) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                $this->warn("Pomijam — brak arkusza «{$sheetName}».");
                continue;
            }

            $rows = $sheet->toArray(null, true, false, false);
            array_shift($rows); // nagłówek

            $this->line("Arkusz «{$sheetName}»: {$this->plural(count($rows))} do przetworzenia (source={$source}).");
            $bar = $this->output->createProgressBar(count($rows));
            $bar->start();

            DB::transaction(function () use ($rows, $source, $isActive, &$stats, &$missingSamples, &$cleanedSamples, $bar) {
                foreach ($rows as $r) {
                    $bar->advance();

                    $numer = $this->str($r, 'numer');
                    $text = $this->str($r, 'text');
                    if ($numer === '' || $text === '') {
                        $stats['skipped']++;
                        continue;
                    }

                    $text = $this->sanitizeText($text, $numer, $stats, $cleanedSamples);

                    $correct = mb_strtoupper($this->str($r, 'correct'));
                    $type = $this->resolveType($correct, $this->str($r, 'type'));
                    $points = (int) $this->str($r, 'points');
                    $points = max(1, min(3, $points ?: 1));

                    // Media
                    $mediaFile = $this->str($r, 'media');
                    [$mediaType, $mediaPath] = $this->resolveMedia($mediaFile);
                    if ($mediaPath !== null) {
                        $stats['with_media']++;
                        if (! isset($this->mediaFiles[mb_strtolower($mediaFile)])) {
                            $stats['media_missing']++;
                            if (count($missingSamples) < 10) {
                                $missingSamples[] = $mediaFile;
                            }
                        }
                    }

                    $question = Question::updateOrCreate(
                        ['external_id' => $numer],
                        [
                            'type' => $type,
                            'points' => $points,
                            'text' => $text,
                            // `explanation` (i jego metadane) CELOWO nie ma na tej liście.
                            // Kolumna jest nullable, więc nowe pytanie i tak powstaje bez
                            // wyjaśnienia — a wpisanie tu `null` kasowało je przy KAŻDYM
                            // ponownym imporcie. To wyjaśnienia generowane przez model
                            // (płatne wywołania), a metadane `explanation_source/_model/
                            // _generated_at` i tak zostawały, więc rekord robił się niespójny:
                            // „wygenerowano modelem X" bez treści.
                            'translations' => $this->buildTranslations($r, $type),
                            'media_type' => $mediaType,
                            'media_path' => $mediaPath,
                            'difficulty' => null, // brak w danych → neutralny prior + FSRS
                            'is_active' => $isActive,
                            'source' => $source,
                        ],
                    );

                    // Odpowiedzi — przebuduj od zera (idempotencja przy ponownym imporcie).
                    $question->answers()->delete();
                    $validCorrect = $this->syncAnswers($question, $type, $r, $correct);
                    if (! $validCorrect) {
                        // Pytanie bez dającej się wskazać poprawnej odpowiedzi jest
                        // bezużyteczne w nauce/egzaminie → wyłączamy z aktywnej puli.
                        $stats['bad_correct']++;
                        if ($question->is_active) {
                            $question->update(['is_active' => false]);
                            $stats['deactivated']++;
                        }
                    }

                    // Kategorie
                    $question->categories()->sync($this->resolveCategories($this->str($r, 'categories')));

                    $stats['imported']++;
                    $stats[$type === QuestionType::Basic ? 'basic' : 'specialist']++;
                }
            });

            $bar->finish();
            $this->newLine(2);
        }

        $this->report($stats, $missingSamples, $cleanedSamples);

        return self::SUCCESS;
    }

    /** Buduje mapę dostępnych plików mediów (lowercase basename => ścieżka). */
    private function indexMediaFiles(): void
    {
        $base = storage_path('app/import/media');
        if (! is_dir($base)) {
            $this->warn("Katalog mediów nie istnieje: {$base} — pomijam raport pokrycia.");
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile()) {
                $this->mediaFiles[mb_strtolower($file->getFilename())] = true;
            }
        }
        $this->line('Zindeksowano '.count($this->mediaFiles).' plików mediów.');
    }

    /**
     * Typ pytania wyznaczamy z DANYCH, nie z kolumny „Zakres struktury" — ta bywa
     * błędnie oznaczona (~10 rekordów). Reguła egzaminu jest sztywna: podstawowe
     * ⟺ TAK/NIE, specjalistyczne ⟺ A/B/C, więc litera poprawnej odpowiedzi jest
     * jednoznaczna: T/N → podstawowe, A/B/C → specjalistyczne. Dopiero gdy litera
     * jest nieznana, ufamy kolumnie (rekord i tak zostanie oznaczony jako wątpliwy).
     */
    private function resolveType(string $correct, string $rawType): QuestionType
    {
        if (in_array($correct, ['T', 'N'], true)) {
            return QuestionType::Basic;
        }
        if (in_array($correct, ['A', 'B', 'C'], true)) {
            return QuestionType::Specialist;
        }
        return str_starts_with(mb_strtoupper(trim($rawType)), 'SPEC')
            ? QuestionType::Specialist
            : QuestionType::Basic;
    }

    /** @return array{0: MediaType, 1: ?string} */
    private function resolveMedia(string $file): array
    {
        if ($file === '') {
            return [MediaType::None, null];
        }
        $ext = mb_strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $type = in_array($ext, ['wmv', 'mp4', 'avi', 'mov', 'webm'], true)
            ? MediaType::Video
            : MediaType::Image;

        return [$type, $file]; // przechowujemy oryginalną nazwę; serwowanie/transkodowanie = osobny etap
    }

    /** Tworzy odpowiedzi. Zwraca false, gdy „poprawna" nie pasuje do żadnego wariantu. */
    private function syncAnswers(Question $question, QuestionType $type, array $r, string $correct): bool
    {
        if ($type === QuestionType::Basic) {
            $question->answers()->createMany([
                ['label' => 'T', 'text' => 'Tak', 'is_correct' => $correct === 'T', 'position' => 1],
                ['label' => 'N', 'text' => 'Nie', 'is_correct' => $correct === 'N', 'position' => 2],
            ]);
            return in_array($correct, ['T', 'N'], true);
        }

        $map = ['A' => 'a', 'B' => 'b', 'C' => 'c'];
        $position = 1;
        $matched = false;
        foreach ($map as $label => $key) {
            $atext = $this->str($r, $key);
            if ($atext === '') {
                continue;
            }
            $isCorrect = $correct === $label;
            $matched = $matched || $isCorrect;
            $question->answers()->create([
                'label' => $label,
                'text' => $atext,
                'is_correct' => $isCorrect,
                'position' => $position++,
            ]);
        }
        return $matched;
    }

    /** Zwraca id kategorii dla listy kodów, tworząc brakujące. */
    private function resolveCategories(string $codes): array
    {
        $ids = [];
        foreach (array_filter(array_map('trim', explode(',', $codes))) as $code) {
            if (! isset($this->categoryIds[$code])) {
                $cat = DrivingCategory::firstOrCreate(
                    ['code' => $code],
                    ['name' => self::CATEGORY_NAMES[$code] ?? $code, 'is_active' => true, 'sort_order' => 99],
                );
                $this->categoryIds[$code] = $cat->id;
            }
            $ids[] = $this->categoryIds[$code];
        }
        return $ids;
    }

    /** Zbiera tłumaczenia (EN/DE/UA) i media PJM w strukturę JSON; pomija puste. */
    private function buildTranslations(array $r, QuestionType $type): ?array
    {
        $out = [];
        foreach (['en', 'de', 'ua'] as $lang) {
            $t = array_filter([
                'q' => $this->str($r, "{$lang}_q"),
                'a' => $this->str($r, "{$lang}_a"),
                'b' => $this->str($r, "{$lang}_b"),
                'c' => $this->str($r, "{$lang}_c"),
            ], fn ($v) => $v !== '');
            if ($t !== []) {
                $out[$lang] = $t;
            }
        }
        $pjm = array_filter([
            'q' => $this->str($r, 'pjm_q'),
            'a' => $this->str($r, 'pjm_a'),
            'b' => $this->str($r, 'pjm_b'),
            'c' => $this->str($r, 'pjm_c'),
        ], fn ($v) => $v !== '');
        if ($pjm !== []) {
            $out['pjm'] = $pjm;
        }

        return $out === [] ? null : $out;
    }

    /** Bezpieczne pobranie komórki jako przycięty string. */
    private function str(array $row, string $key): string
    {
        $idx = self::COL[$key];
        return trim((string) ($row[$idx] ?? ''));
    }

    /**
     * Usuwa doklejony, urwany fragment początku treści — ślad po pomyłce copy-paste
     * w arkuszu źródłowym.
     *
     * Przykład (pytanie #10816 w wydaniu z 2026-07-14): treść to poprawne zdanie,
     * po nim trzy znaki nowej linii, a po nich „Policjant zatrzyma" — czyli początek
     * tego samego zdania, urwany w połowie słowa. Sprawdziliśmy, że to błąd ŹRÓDŁA,
     * nie importu: hex komórki w XLSX zgadza się co do bajtu z tym, co trafiło do bazy,
     * a tłumaczenia EN/DE/UA tego wiersza są kompletne i czyste.
     *
     * Czyścimy przy imporcie, a nie ręcznie w bazie, bo import jest idempotentny po
     * `external_id` — ręczna poprawka zostałaby nadpisana przy najbliższym przebiegu.
     *
     * Reguła jest CELOWO wąska:
     *  - dzielimy tylko na PUSTEJ LINII (`\n{2,}`), więc pojedyncze `\n` — normalne
     *    zawijanie wiersza, jak w #11050 i #13118 — zostaje nietknięte,
     *  - obcinamy wyłącznie ogon będący WŁAŚCIWYM prefiksem reszty treści.
     * Na wydaniu z 3707 wierszy trafia dokładnie 1 raz. Każde cięcie jest logowane —
     * gdyby reguła kiedyś ruszyła coś legalnego, zobaczysz to w raporcie, zamiast
     * po cichu stracić fragment pytania.
     */
    private function sanitizeText(string $text, string $numer, array &$stats, array &$samples): string
    {
        $chunks = preg_split('/\n{2,}/', $text);
        if ($chunks === false || count($chunks) < 2) {
            return $text;
        }

        $tail = trim((string) array_pop($chunks));
        $rest = trim(implode("\n\n", $chunks));

        if ($tail === '' || $rest === '' || $rest === $tail || ! str_starts_with($rest, $tail)) {
            return $text;
        }

        $stats['text_cleaned']++;
        if (count($samples) < 10) {
            $samples[] = "#{$numer}: obcięto ".var_export($tail, true);
        }

        return $rest;
    }

    private function plural(int $n): string
    {
        return $n.' '.($n === 1 ? 'wiersz' : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 'wiersze' : 'wierszy'));
    }

    private function report(array $stats, array $missingSamples, array $cleanedSamples = []): void
    {
        $this->info('=== Import zakończony ===');
        $this->table(
            ['Metryka', 'Wartość'],
            [
                ['Zaimportowane pytania', $stats['imported']],
                ['  — podstawowe (TAK/NIE)', $stats['basic']],
                ['  — specjalistyczne (A/B/C)', $stats['specialist']],
                ['Pominięte (puste wiersze)', $stats['skipped']],
                ['Pytania z mediami', $stats['with_media']],
                ['  — brakujący plik media', $stats['media_missing']],
                ['Wątpliwa „poprawna odp."', $stats['bad_correct']],
                ['  — wyłączone z puli (0 poprawnych)', $stats['deactivated']],
                ['Oczyszczona treść (urwany duplikat)', $stats['text_cleaned']],
            ],
        );

        if ($missingSamples !== []) {
            $this->warn('Przykłady brakujących plików mediów: '.implode(', ', $missingSamples));
        }

        // Głośno, nie po cichu: każde cięcie treści musi być widoczne w raporcie,
        // żeby ewentualne fałszywe trafienie reguły dało się wychwycić.
        if ($cleanedSamples !== []) {
            $this->warn('Usunięto doklejone, urwane duplikaty początku treści:');
            foreach ($cleanedSamples as $sample) {
                $this->line('  '.$sample);
            }
        }
    }
}
