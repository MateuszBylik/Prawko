<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Serce silnika: p_correct dla pary (użytkownik, pytanie) w danej chwili.
 *
 *   p_correct = p_recall · p_time
 *
 * gdzie:
 *   p_recall — dla pytań WIDZIANYCH: retrievability FSRS w chwili `at`;
 *              dla NIEROZWIĄZANYCH: prior Rascha σ(θ_u − b_q),
 *   p_time   — szansa, że użytkownik zdąży w limicie egzaminacyjnym
 *              (na egzaminie liczy się też czas).
 *
 * To wejście dla Etapu 2 (Monte Carlo „szansa zdania" + kolejka ryzyka).
 */
class PCorrectService
{
    public function __construct(
        private readonly FsrsService $fsrs,
        private readonly AbilityService $ability,
    ) {}

    /** p_correct dla jednego pytania. */
    public function forQuestion(User $user, Question $question, ?QuestionUserStat $stat = null, ?CarbonImmutable $at = null): float
    {
        $at ??= CarbonImmutable::now();

        $pRecall = $this->recall($user, $question, $stat, $at);
        $pTime = $this->timely($question, $stat);

        return $this->clampProbability($pRecall * $pTime);
    }

    /**
     * Wersja wsadowa — mapa question_id => p_correct, bez N+1 (statystyki jednym zapytaniem).
     *
     * @param  Collection<int, Question>  $questions
     * @return array<int, float>
     */
    public function forQuestions(User $user, Collection $questions, ?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now();

        $stats = QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('question_id');

        $result = [];
        foreach ($questions as $question) {
            $result[$question->id] = $this->forQuestion($user, $question, $stats->get($question->id), $at);
        }

        return $result;
    }

    /** Składowa pamięciowa. */
    private function recall(User $user, Question $question, ?QuestionUserStat $stat, CarbonImmutable $at): float
    {
        if ($stat && $stat->stability !== null && $stat->stability > 0 && $stat->last_reviewed_at) {
            $elapsedDays = max(0.0, $stat->last_reviewed_at->diffInSeconds($at) / 86400);
            return $this->fsrs->retrievability($elapsedDays, (float) $stat->stability);
        }

        // Pytanie jeszcze nierozwiązane (lub sprzed FSRS) → prior Rascha.
        return $this->ability->prior((float) $user->skill_theta, (float) $question->irt_b);
    }

    /** Składowa czasowa — p(zdążę w limicie). */
    private function timely(Question $question, ?QuestionUserStat $stat): float
    {
        if (! $stat || ! $stat->avg_time_ms) {
            return (float) config('prawko.timing.default_p');
        }

        $limitSeconds = $question->type === QuestionType::Basic
            ? (int) config('prawko.timing.basic_seconds')
            : (int) config('prawko.timing.specialist_seconds');

        $avgSeconds = $stat->avg_time_ms / 1000;
        // Logistyczny spadek wokół limitu; szerokość ~15% limitu.
        $width = max(3.0, $limitSeconds * 0.15);

        return $this->clampProbability(1 / (1 + exp(($avgSeconds - $limitSeconds) / $width)));
    }

    private function clampProbability(float $p): float
    {
        return max(0.02, min(0.995, $p));
    }
}
