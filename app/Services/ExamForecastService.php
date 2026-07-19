<?php

namespace App\Services;

use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use App\Models\UserAnswer;
use App\Support\ExamForecast;
use Illuminate\Support\Facades\Cache;

/**
 * Estymator „szansy zdania" metodą Monte Carlo (Etap 2).
 *
 * Dla każdego z {@see self::SIMULATIONS} losowań budujemy arkusz zgodny z
 * oficjalną kompozycją (20 podstawowych + 12 specjalistycznych, 74 pkt), losując
 * pytania z odpowiednich pul, a następnie dla każdego pytania rzucamy monetą z
 * prawdopodobieństwem p_correct (z {@see PCorrectService}). Rozkład sum punktów
 * daje P(zdania) oraz prognozę: medianę i 90% przedział (p5–p95).
 *
 * Uwzględniamy dwa źródła losowości: DOBÓR pytań na arkusz i POPRAWNOŚĆ każdego
 * z nich — dlatego przedział jest uczciwy, a nie tylko z modelu Bernoulliego.
 */
class ExamForecastService
{
    public const SIMULATIONS = 10000;

    public function __construct(private readonly PCorrectService $pcorrect) {}

    /** Prognoza dla użytkownika i kategorii — cache'owana do najbliższej odpowiedzi. */
    public function for(User $user, DrivingCategory $category): ExamForecast
    {
        // Cache trzyma tablicę (store bazodanowy nie deserializuje obiektów) — DTO odtwarzamy.
        $data = Cache::remember(
            EngineCache::key('forecast', $user->id, $category->id),
            now()->addMinutes(30),
            fn () => $this->compute($user, $category)->toArray(),
        );

        return ExamForecast::fromArray($data);
    }

    private function compute(User $user, DrivingCategory $category): ExamForecast
    {
        $threshold = (int) config('prawko.exam.pass_threshold');

        // 1) Pule per warstwa [typ, punkty, liczba na arkuszu] + p_correct jednym przebiegiem.
        $pools = [];         // [ [points, count, poolQuestions], ... ]
        $allQuestions = collect();
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $pool = Question::query()
                ->active()->ofType($type)->where('points', $points)
                ->forCategory($category->id)
                ->get(['id', 'type', 'points']);
            $pools[] = [$points, $count, $pool];
            $allQuestions = $allQuestions->concat($pool);
        }

        $maxScore = array_sum(array_map(fn ($p) => $p[0] * $p[1], $pools));
        $pMap = $this->pcorrect->forQuestions($user, $allQuestions);

        // 2) Pokrycie: jaka (ważona składem arkusza) część pytań ma realne dane FSRS.
        $seen = QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereIn('question_id', $allQuestions->pluck('id'))
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->pluck('question_id')->flip();

        // 3) Warstwy jako czyste tablice prawdopodobieństw (bez modeli — szybka pętla).
        $layers = [];
        $sheetTotal = 0;
        $sheetSeen = 0.0;
        foreach ($pools as [$points, $count, $pool]) {
            $probs = $pool->map(fn ($q) => $pMap[$q->id])->values()->all();
            $layers[] = ['points' => $points, 'count' => $count, 'p' => $probs, 'size' => count($probs)];

            $poolSize = $pool->count();
            $seenInPool = $poolSize > 0 ? $pool->filter(fn ($q) => $seen->has($q->id))->count() : 0;
            $sheetTotal += $count;
            $sheetSeen += $count * ($poolSize > 0 ? $seenInPool / $poolSize : 0.0);
        }
        $coverage = $sheetTotal > 0 ? $sheetSeen / $sheetTotal : 0.0;

        // 4) Symulacja.
        $n = self::SIMULATIONS;
        $scores = array_fill(0, $n, 0);
        $passes = 0;
        $mtMax = mt_getrandmax();

        for ($i = 0; $i < $n; $i++) {
            $score = 0;
            foreach ($layers as $layer) {
                $probs = $layer['p'];
                $points = $layer['points'];
                foreach ($this->sampleIndices($layer['size'], $layer['count']) as $idx) {
                    if (mt_rand() / $mtMax < $probs[$idx]) {
                        $score += $points;
                    }
                }
            }
            $scores[$i] = $score;
            if ($score >= $threshold) {
                $passes++;
            }
        }

        sort($scores);

        return new ExamForecast(
            passProbability: $passes / $n,
            expectedScore: array_sum($scores) / $n,
            medianScore: $scores[intdiv($n, 2)],
            ciLow: $scores[(int) floor(0.05 * $n)],
            ciHigh: $scores[(int) floor(0.95 * $n)],
            threshold: $threshold,
            maxScore: (int) $maxScore,
            coverage: $coverage,
            samples: $n,
            answered: UserAnswer::where('user_id', $user->id)->whereNotNull('answered_at')->count(),
        );
    }

    /**
     * `count` różnych indeksów z zakresu [0, poolSize). Pule są duże, a `count`
     * małe (≤10), więc losowanie z odrzucaniem kolizji jest szybkie i wystarczające.
     *
     * @return int[]
     */
    private function sampleIndices(int $poolSize, int $count): array
    {
        // Pusta warstwa = brak losowań. Bez tego range(0, max(0, -1)) zwraca [0],
        // czyli indeks w pustą tablicę prawdopodobieństw → „Undefined array key 0".
        // W produkcji każda kategoria ma setki pytań w każdej warstwie, ale kategoria
        // bez pokrycia (albo wąski zestaw testowy) wywalała cały pulpit.
        if ($poolSize <= 0) {
            return [];
        }

        if ($count >= $poolSize) {
            return range(0, $poolSize - 1);
        }

        $picked = [];
        while (count($picked) < $count) {
            $picked[mt_rand(0, $poolSize - 1)] = true;
        }

        return array_keys($picked);
    }
}
