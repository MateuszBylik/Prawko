<?php

namespace App\Services;

use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Kolejka ryzyka (Etap 2): pytania uszeregowane wg oczekiwanej straty punktów.
 *
 *   loss(q) = (n_warstwy / N_warstwy) · waga · (1 − p_correct)
 *
 * gdzie n_warstwy/N_warstwy = szansa, że akurat to pytanie trafi na arkusz
 * (liczba pytań danej warstwy na egzaminie / rozmiar puli tej warstwy), waga =
 * punkty pytania, a (1 − p_correct) = szansa pomyłki. Iloczyn to oczekiwana
 * strata punktów na egzaminie — im wyżej, tym większy zwrot z nauki tego pytania.
 *
 * Napędza „co ćwiczyć najpierw" na pulpicie oraz dobór pytań do sesji nauki.
 */
class RiskQueueService
{
    public function __construct(private readonly PCorrectService $pcorrect) {}

    /**
     * Pełny ranking [ ['question_id','loss','p'], ... ] malejąco wg straty.
     * Cache'owany do najbliższej odpowiedzi (patrz EngineCache).
     *
     * @return array<int, array{question_id:int, loss:float, p:float}>
     */
    public function rank(User $user, DrivingCategory $category): array
    {
        return Cache::remember(
            EngineCache::key('risk', $user->id, $category->id),
            now()->addMinutes(30),
            fn () => $this->compute($user, $category),
        );
    }

    /** Top N pytań jako modele z doczepionym `risk_loss` i `p_correct` (do widoku). */
    public function topQuestions(User $user, DrivingCategory $category, int $limit = 6): Collection
    {
        $ranked = array_slice($this->rank($user, $category), 0, $limit);
        $ids = array_column($ranked, 'question_id');
        if (empty($ids)) {
            return collect();
        }

        $byId = Question::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ranked)
            ->map(function (array $row) use ($byId) {
                $q = $byId->get($row['question_id']);
                if (! $q) {
                    return null;
                }
                $q->risk_loss = $row['loss'];
                $q->p_correct = $row['p'];

                return $q;
            })
            ->filter()
            ->values();
    }

    /**
     * ID najwyższego ryzyka (dla ExamService — pula, z której losujemy sesję nauki).
     *
     * @return int[]
     */
    public function topQuestionIds(User $user, DrivingCategory $category, int $limit): array
    {
        return array_column(array_slice($this->rank($user, $category), 0, $limit), 'question_id');
    }

    /** @return array<int, array{question_id:int, loss:float, p:float}> */
    private function compute(User $user, DrivingCategory $category): array
    {
        // Liczba pytań każdej warstwy na arkuszu: klucz "typ|punkty" => n.
        $sheetCount = [];
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $sheetCount[$type->value.'|'.$points] = $count;
        }

        $questions = Question::query()
            ->active()->forCategory($category->id)
            ->get(['id', 'type', 'points']);

        $pMap = $this->pcorrect->forQuestions($user, $questions);

        // Rozmiary pul per warstwa (N_warstwy).
        $poolSize = [];
        foreach ($questions as $q) {
            $key = $q->type->value.'|'.$q->points;
            $poolSize[$key] = ($poolSize[$key] ?? 0) + 1;
        }

        $ranked = [];
        foreach ($questions as $q) {
            $key = $q->type->value.'|'.$q->points;
            $n = $sheetCount[$key] ?? 0;
            $bigN = $poolSize[$key] ?? 1;
            $p = $pMap[$q->id];

            $ranked[] = [
                'question_id' => $q->id,
                'loss' => ($bigN > 0 ? $n / $bigN : 0.0) * (int) $q->points * (1 - $p),
                'p' => $p,
            ];
        }

        usort($ranked, fn ($a, $b) => $b['loss'] <=> $a['loss']);

        return $ranked;
    }
}
