<?php

namespace App\Services;

use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use App\Models\UserAnswer;
use App\Support\LossBreakdown;

/**
 * „Gdzie tracisz punkty" — rozkład oczekiwanej straty na warstwy arkusza
 * i typy pułapek (patrz {@see LossBreakdown} po matematykę i uzasadnienie).
 *
 * Świadomie NIE liczy p_correct od nowa: bierze gotowy ranking z
 * {@see RiskQueueService::rank()}, który jest już cache'owany pod tą samą epoką
 * co prognoza. Dzięki temu (a) nie dublujemy przebiegu FSRS po całej puli,
 * (b) ekran i pulpit z definicji pokazują spójne liczby.
 */
class LossBreakdownService
{
    public function __construct(private readonly RiskQueueService $risk) {}

    public function for(User $user, DrivingCategory $category): LossBreakdown
    {
        // p_correct i strata per pytanie — jedno źródło prawdy z kolejką ryzyka.
        $p = [];
        $loss = [];
        foreach ($this->risk->rank($user, $category) as $row) {
            $p[$row['question_id']] = $row['p'];
            $loss[$row['question_id']] = $row['loss'];
        }

        $questions = Question::query()
            ->active()->forCategory($category->id)
            ->get(['id', 'type', 'points', 'traps']);

        $hist = $this->history($user);
        $seen = $this->seen($user);

        // Szkielet warstw w kolejności z configu (typ|punkty => n na arkuszu).
        $layers = [];
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $layers[$type->value.'|'.$points] = [
                'type' => $type, 'points' => $points, 'count' => $count,
                'pool_size' => 0, 'sum_p' => 0.0, 'loss' => 0.0, 'seen' => 0,
                'hist_n' => 0, 'hist_correct' => 0,
            ];
        }

        $traps = [];

        foreach ($questions as $q) {
            // Pytanie spoza rankingu (teoretycznie niemożliwe — te same filtry) pomijamy,
            // zamiast wliczać mu stratę 0 i po cichu zaniżać wynik warstwy.
            if (! isset($loss[$q->id])) {
                continue;
            }

            $key = $q->type->value.'|'.$q->points;
            $h = $hist[$q->id] ?? null;

            if (isset($layers[$key])) {
                $layers[$key]['pool_size']++;
                $layers[$key]['sum_p'] += $p[$q->id];
                $layers[$key]['loss'] += $loss[$q->id];
                $layers[$key]['seen'] += isset($seen[$q->id]) ? 1 : 0;
                $layers[$key]['hist_n'] += $h['n'] ?? 0;
                $layers[$key]['hist_correct'] += $h['c'] ?? 0;
            }

            // Pułapki: pytanie może nieść kilka naraz, więc warstwy pułapek się
            // NAKŁADAJĄ — ich strat nie wolno sumować do wyniku, służą tylko
            // porównaniu „który haczyk kosztuje najwięcej".
            foreach ($q->trapTypes() as $trap) {
                $t = $trap->value;
                $traps[$t] ??= ['trap' => $trap, 'pool_size' => 0, 'loss' => 0.0, 'hist_n' => 0, 'hist_correct' => 0];
                $traps[$t]['pool_size']++;
                $traps[$t]['loss'] += $loss[$q->id];
                $traps[$t]['hist_n'] += $h['n'] ?? 0;
                $traps[$t]['hist_correct'] += $h['c'] ?? 0;
            }
        }

        $maxScore = 0;
        $expectedScore = 0.0;
        $layerRows = [];

        foreach ($layers as $l) {
            $poolPoints = $l['count'] * $l['points'];
            $maxScore += $poolPoints;
            $expected = $poolPoints - $l['loss'];
            $expectedScore += $expected;

            $layerRows[] = [
                'type' => $l['type'],
                'points' => $l['points'],
                'count' => $l['count'],
                'pool_size' => $l['pool_size'],
                'pool_points' => $poolPoints,
                'mean_p' => $l['pool_size'] > 0 ? $l['sum_p'] / $l['pool_size'] : 0.0,
                'expected' => $expected,
                'loss' => $l['loss'],
                'coverage' => $l['pool_size'] > 0 ? $l['seen'] / $l['pool_size'] : 0.0,
                'hist_n' => $l['hist_n'],
                'hist_correct' => $l['hist_correct'],
                'hist_accuracy' => $l['hist_n'] > 0 ? $l['hist_correct'] / $l['hist_n'] : null,
            ];
        }

        $trapRows = array_map(fn ($t) => [
            'trap' => $t['trap'],
            'pool_size' => $t['pool_size'],
            'loss' => $t['loss'],
            'hist_n' => $t['hist_n'],
            'hist_correct' => $t['hist_correct'],
            'hist_accuracy' => $t['hist_n'] > 0 ? $t['hist_correct'] / $t['hist_n'] : null,
        ], array_values($traps));

        // Sortujemy po stracie, nie po procencie — o to w tym ekranie chodzi.
        usort($layerRows, fn ($a, $b) => $b['loss'] <=> $a['loss']);
        usort($trapRows, fn ($a, $b) => $b['loss'] <=> $a['loss']);

        return new LossBreakdown(
            layers: $layerRows,
            traps: $trapRows,
            maxScore: $maxScore,
            expectedScore: $expectedScore,
            threshold: (int) config('prawko.exam.pass_threshold'),
        );
    }

    /**
     * Historia odpowiedzi zagregowana po pytaniu. Bez `whereIn` po całej puli —
     * to byłoby kilka tysięcy parametrów, a i tak interesują nas tylko pytania,
     * na które użytkownik faktycznie odpowiadał (rzędy setek).
     *
     * @return array<int, array{n:int, c:int}>
     */
    private function history(User $user): array
    {
        return UserAnswer::query()
            ->where('user_id', $user->id)
            ->whereNotNull('answered_at')
            ->groupBy('question_id')
            ->selectRaw('question_id, COUNT(*) as n, SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as c')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->question_id => ['n' => (int) $r->n, 'c' => (int) $r->c]])
            ->all();
    }

    /**
     * Pytania z realnym stanem FSRS (nie z priora) — do pokrycia per warstwa.
     *
     * @return array<int, true>
     */
    private function seen(User $user): array
    {
        return QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->pluck('question_id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }
}
