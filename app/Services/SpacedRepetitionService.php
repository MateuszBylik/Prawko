<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionUserStat;
use Carbon\CarbonImmutable;

/**
 * Aktualizuje stan wiedzy użytkownika o pytaniu według algorytmu SM-2
 * (SuperMemo 2) — podstawa systemu powtórek rozłożonych w czasie.
 *
 * To celowo minimalny, ale poprawny fundament: pełne planowanie powtórek
 * (kolejka „na dziś", tryb powtórkowy) można dobudować na tych danych.
 */
class SpacedRepetitionService
{
    public function record(int $userId, Question $question, bool $correct): QuestionUserStat
    {
        $stat = QuestionUserStat::firstOrNew([
            'user_id' => $userId,
            'question_id' => $question->id,
        ]);

        $ease = (float) ($stat->ease_factor ?? 2.5);
        $repetitions = (int) ($stat->repetitions ?? 0);
        $interval = (int) ($stat->interval_days ?? 0);

        $stat->times_seen = (int) ($stat->times_seen ?? 0) + 1;

        if ($correct) {
            $stat->times_correct = (int) ($stat->times_correct ?? 0) + 1;
            $stat->streak = (int) ($stat->streak ?? 0) + 1;
        } else {
            $stat->times_incorrect = (int) ($stat->times_incorrect ?? 0) + 1;
            $stat->streak = 0;
        }

        // Jakość odpowiedzi w skali SM-2 (0–5). Upraszczamy: poprawnie = 5, błędnie = 2.
        $quality = $correct ? 5 : 2;

        // Nowy współczynnik łatwości (EF), nie mniejszy niż 1.3.
        $ease += 0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02);
        $ease = max(1.3, $ease);

        if ($quality < 3) {
            // Błędna odpowiedź — powtórka od nowa (następnego dnia).
            $repetitions = 0;
            $interval = 1;
        } else {
            $repetitions++;
            $interval = match (true) {
                $repetitions === 1 => 1,
                $repetitions === 2 => 6,
                default => (int) round($interval * $ease),
            };
        }

        $now = CarbonImmutable::now();

        $stat->ease_factor = round($ease, 2);
        $stat->repetitions = $repetitions;
        $stat->interval_days = $interval;
        $stat->last_reviewed_at = $now;
        $stat->due_at = $now->addDays(max(1, $interval));
        $stat->save();

        return $stat;
    }
}
