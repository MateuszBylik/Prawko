<?php

namespace App\Console\Commands;

use App\Enums\QuestionType;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionSimilar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wykrywanie par mylących: dla każdego pytania znajduje najbardziej podobne
 * pytania tego samego typu (podobieństwo Jaccarda na trigramach znakowych).
 * Deterministyczne, bez AI. Wynik ląduje w `question_similars` (top-K per pytanie).
 *
 * Wydajność na ~2 tys. pytań: budujemy indeks odwrócony trigram→pytania i
 * pomijamy trigramy zbyt częste (mało znaczące, a rozdmuchują liczbę porównań).
 */
class DetectTwins extends Command
{
    protected $signature = 'prawko:detect-twins {--category=B} {--threshold=0.5} {--max=0.90} {--per=6} {--df=0.25}';

    protected $description = 'Wykrywa pary mylące (podobne pytania) trigramami i zapisuje do question_similars.';

    public function handle(): int
    {
        $threshold = (float) $this->option('threshold');
        $max = (float) $this->option('max');
        $per = (int) $this->option('per');
        $dfRatio = (float) $this->option('df');

        $category = DrivingCategory::where('code', (string) $this->option('category'))->first();
        if (! $category) {
            $this->error('Nie znaleziono kategorii '.$this->option('category'));

            return self::FAILURE;
        }

        DB::table('question_similars')->truncate();

        $total = 0;
        foreach ([QuestionType::Basic, QuestionType::Specialist] as $type) {
            $total += $this->processType($type, $category->id, $threshold, $max, $per, $dfRatio);
        }

        $this->info("Gotowe — zapisano {$total} skojarzeń (próg {$threshold}–{$max}, top-{$per}).");
        $this->showSamples();

        return self::SUCCESS;
    }

    private function processType(QuestionType $type, int $categoryId, float $threshold, float $max, int $per, float $dfRatio): int
    {
        $questions = Question::query()->active()->ofType($type)->forCategory($categoryId)->get(['id', 'text']);
        $n = $questions->count();
        if ($n < 2) {
            return 0;
        }

        // Zbiory trigramów + document frequency (w ilu pytaniach trigram występuje).
        $tri = [];
        $df = [];
        foreach ($questions as $q) {
            $set = $this->trigrams($this->normalize($q->text));
            $tri[$q->id] = $set;
            foreach ($set as $g) {
                $df[$g] = ($df[$g] ?? 0) + 1;
            }
        }
        $dfMax = max(2, (int) floor($n * $dfRatio));

        // Indeks odwrócony (bez zbyt częstych trigramów) + rozmiary filtrowanych zbiorów.
        $index = [];
        $fsize = [];
        foreach ($tri as $qid => $set) {
            $c = 0;
            foreach ($set as $g) {
                if ($df[$g] <= $dfMax) {
                    $index[$g][] = $qid;
                    $c++;
                }
            }
            $fsize[$qid] = $c;
        }

        $rows = [];
        $pairs = 0;
        foreach ($questions as $q) {
            $qid = $q->id;
            if ($fsize[$qid] === 0) {
                continue;
            }

            // Liczba wspólnych (filtrowanych) trigramów z każdym kandydatem.
            $shared = [];
            foreach ($tri[$qid] as $g) {
                if ($df[$g] > $dfMax) {
                    continue;
                }
                foreach ($index[$g] as $other) {
                    if ($other !== $qid) {
                        $shared[$other] = ($shared[$other] ?? 0) + 1;
                    }
                }
            }

            // Jaccard = wspólne / (|A| + |B| − wspólne); zostaw top-K powyżej progu.
            $scored = [];
            foreach ($shared as $other => $inter) {
                $union = $fsize[$qid] + $fsize[$other] - $inter;
                if ($union > 0) {
                    $j = $inter / $union;
                    // Górny limit odcina pary o ~identycznej treści (te same słowa,
                    // inny film/zdjęcie) — nie są pułapką tekstową i AI nie ma czego różnicować.
                    if ($j >= $threshold && $j < $max) {
                        $scored[$other] = $j;
                    }
                }
            }
            arsort($scored);

            foreach (array_slice($scored, 0, $per, true) as $other => $j) {
                $rows[] = ['question_id' => $qid, 'similar_question_id' => $other, 'score' => round($j, 4)];
                $pairs++;
            }

            if (count($rows) >= 1000) {
                DB::table('question_similars')->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table('question_similars')->insert($rows);
        }

        $this->line(sprintf('  %-12s %5d pytań → %d skojarzeń', $type->value, $n, $pairs));

        return $pairs;
    }

    /** Normalizacja: małe litery, bez interpunkcji, pojedyncze spacje (UTF-8/PL). */
    private function normalize(string $text): string
    {
        $t = mb_strtolower($text, 'UTF-8');
        $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t);

        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    /** @return string[] unikalne trigramy znakowe */
    private function trigrams(string $norm): array
    {
        $chars = preg_split('//u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $count = count($chars);
        $set = [];
        for ($i = 0; $i + 3 <= $count; $i++) {
            $set[$chars[$i].$chars[$i + 1].$chars[$i + 2]] = true;
        }

        return array_keys($set);
    }

    private function showSamples(): void
    {
        $samples = QuestionSimilar::with(['question:id,text', 'similarQuestion:id,text'])
            ->orderByDesc('score')->limit(4)->get();

        $this->line("\nPrzykładowe pary (najwyższe podobieństwo):");
        foreach ($samples as $s) {
            $this->line(sprintf('  [%.2f] #%d: %s', $s->score, $s->question_id, mb_substr($s->question->text, 0, 66)));
            $this->line(sprintf('         #%d: %s', $s->similar_question_id, mb_substr($s->similarQuestion->text, 0, 66)));
        }
    }
}
