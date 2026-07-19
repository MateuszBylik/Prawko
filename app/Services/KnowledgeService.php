<?php

namespace App\Services;

use App\Enums\Confidence;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Orkiestrator silnika wiedzy: przyjmuje pojedynczą odpowiedź i aktualizuje
 *   1) liczniki agregatowe (times_seen/correct, streak — nadal na pulpicie),
 *   2) stan FSRS pary (użytkownik, pytanie) — stabilność, trudność, termin powtórki,
 *   3) parametry IRT — θ_u użytkownika i b_q pytania (prior dla nierozwiązanych).
 *
 * Zastępuje SM-2 (SpacedRepetitionService) jako domyślny silnik powtórek.
 */
class KnowledgeService
{
    public function __construct(
        private readonly FsrsService $fsrs,
        private readonly AbilityService $ability,
    ) {}

    public function record(int $userId, Question $question, bool $correct, ?int $timeSpentMs = null, ?Confidence $confidence = null): QuestionUserStat
    {
        $now = CarbonImmutable::now();

        $stat = QuestionUserStat::firstOrNew([
            'user_id' => $userId,
            'question_id' => $question->id,
        ]);

        $grade = $this->grade($question, $correct, $timeSpentMs, $confidence);
        $isNew = $stat->stability === null || $stat->last_reviewed_at === null;

        if ($isNew) {
            $difficulty = $this->fsrs->initialDifficulty($grade);
            $stability = $this->fsrs->initialStability($grade);
        } else {
            $elapsedDays = max(0.0, $stat->last_reviewed_at->diffInSeconds($now) / 86400);
            $retrievability = $this->fsrs->retrievability($elapsedDays, (float) $stat->stability);
            $difficulty = $this->fsrs->nextDifficulty((float) $stat->difficulty, $grade);
            $stability = $grade === FsrsService::AGAIN
                ? $this->fsrs->stabilityAfterLapse((float) $stat->difficulty, (float) $stat->stability, $retrievability)
                : $this->fsrs->stabilityAfterRecall((float) $stat->difficulty, (float) $stat->stability, $retrievability, $grade);
        }

        // 1) Liczniki agregatowe.
        $stat->times_seen = (int) $stat->times_seen + 1;
        if ($correct) {
            $stat->times_correct = (int) $stat->times_correct + 1;
            $stat->streak = (int) $stat->streak + 1;
        } else {
            $stat->times_incorrect = (int) $stat->times_incorrect + 1;
            $stat->streak = 0;
            $stat->lapses = (int) $stat->lapses + 1;
        }

        // Średni czas odpowiedzi (wykładnicza średnia krocząca) — do modelu czasowego.
        if ($timeSpentMs !== null && $timeSpentMs > 0) {
            $stat->avg_time_ms = $stat->avg_time_ms === null
                ? $timeSpentMs
                : (int) round(0.7 * $stat->avg_time_ms + 0.3 * $timeSpentMs);
        }

        // 2) Stan FSRS + termin powtórki.
        $interval = $this->fsrs->interval($stability);
        $stat->stability = round($stability, 6);
        $stat->difficulty = round($difficulty, 6);
        $stat->fsrs_state = $grade === FsrsService::AGAIN ? 3 : 2; // relearning / review
        $stat->interval_days = $interval;
        $stat->last_reviewed_at = $now;
        $stat->due_at = $now->addDays($interval);
        $stat->save();

        // 3) IRT: θ_u (użytkownik) i b_q (pytanie).
        $user = User::find($userId);
        if ($user) {
            [$theta, $b] = $this->ability->update((float) $user->skill_theta, (float) $question->irt_b, $correct);
            $user->forceFill(['skill_theta' => round($theta, 6)])->save();
            $question->forceFill(['irt_b' => round($b, 6)])->save();
        }

        // θ_u i stan FSRS się zmieniły → prognoza i kolejka ryzyka są nieaktualne.
        EngineCache::bump($userId);

        return $stat;
    }

    /**
     * Ocena FSRS z poprawności, deklarowanej pewności i szybkości:
     * błąd = Again; trafiony strzał = Hard; poprawnie i pewnie: szybko→Easy, w normie→Good, wolno→Hard.
     */
    private function grade(Question $question, bool $correct, ?int $timeSpentMs, ?Confidence $confidence = null): int
    {
        if (! $correct) {
            return FsrsService::AGAIN;
        }

        // Trafiony strzał to szczęście, nie przypomnienie. Bez tego sygnału FSRS
        // traktował go jak wiedzę i podbijał stabilność (przy szybkim kliknięciu
        // nawet do Easy) — a pytanie wracało za tygodnie, choć nie umiemy go wcale.
        // HARD, a nie AGAIN: to nie wpadka (nie liczymy lapsu), tylko najsłabszy
        // możliwy dowód, że pamięć w ogóle zadziałała.
        if ($confidence?->isGuess()) {
            return FsrsService::HARD;
        }

        if ($timeSpentMs === null || $timeSpentMs <= 0) {
            return FsrsService::GOOD;
        }

        $limitMs = ($question->type === QuestionType::Basic
            ? (int) config('prawko.timing.basic_seconds')
            : (int) config('prawko.timing.specialist_seconds')) * 1000;

        $ratio = $timeSpentMs / max(1, $limitMs);

        return match (true) {
            $ratio < 0.5 => FsrsService::EASY,
            $ratio <= 0.9 => FsrsService::GOOD,
            default => FsrsService::HARD,
        };
    }
}
