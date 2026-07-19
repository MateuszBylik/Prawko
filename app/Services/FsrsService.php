<?php

namespace App\Services;

/**
 * FSRS-5 (Free Spaced Repetition Scheduler) — czysta implementacja algorytmu
 * pamięci. Operuje na dwóch wielkościach dla pary (użytkownik, pytanie):
 *   S — stabilność (dni do spadku retrievability do request_retention),
 *   D — trudność (1–10).
 * Oceny (grade): 1 = Again, 2 = Hard, 3 = Good, 4 = Easy.
 *
 * Świadomie pomijamy kroki „short-term / same-day" — w nauce teorii powtórki
 * są rozłożone w czasie, a nie w obrębie jednej sesji.
 */
class FsrsService
{
    public const AGAIN = 1;
    public const HARD = 2;
    public const GOOD = 3;
    public const EASY = 4;

    /** @var float[] */
    private array $w;
    private float $decay;
    private float $factor;
    private float $requestRetention;
    private int $maximumInterval;

    public function __construct()
    {
        $cfg = config('prawko.fsrs');
        $this->w = array_map('floatval', $cfg['weights']);
        $this->decay = (float) $cfg['decay'];
        // factor tak dobrane, by R(S) = 0.9 (dla decay -0.5 → 19/81 ≈ 0.234375).
        $this->factor = 0.9 ** (1 / $this->decay) - 1;
        $this->requestRetention = (float) $cfg['request_retention'];
        $this->maximumInterval = (int) $cfg['maximum_interval'];
    }

    /** Retrievability (szansa przypomnienia) po `elapsedDays` dniach przy stabilności S. */
    public function retrievability(float $elapsedDays, float $stability): float
    {
        if ($stability <= 0) {
            return 0.0;
        }
        $t = max(0.0, $elapsedDays);
        return (1 + $this->factor * $t / $stability) ** $this->decay;
    }

    /** Odstęp do następnej powtórki (dni), by trafić w docelową retencję. */
    public function interval(float $stability): int
    {
        $ivl = $stability / $this->factor * ($this->requestRetention ** (1 / $this->decay) - 1);
        return (int) max(1, min($this->maximumInterval, (int) round($ivl)));
    }

    /** Stabilność początkowa dla nowej karty (S0(grade) = w[grade-1]). */
    public function initialStability(int $grade): float
    {
        return max(0.1, $this->w[$grade - 1]);
    }

    /** Trudność początkowa: D0(G) = w4 − e^{w5·(G−1)} + 1. */
    public function initialDifficulty(int $grade): float
    {
        return $this->clampDifficulty($this->w[4] - exp($this->w[5] * ($grade - 1)) + 1);
    }

    /** Kolejna trudność: liniowe tłumienie + powrót do średniej ku D0(Easy). */
    public function nextDifficulty(float $difficulty, int $grade): float
    {
        $deltaD = -$this->w[6] * ($grade - 3);
        $damped = $difficulty + $deltaD * (10 - $difficulty) / 9;
        $target = $this->w[4] - exp($this->w[5] * 3) + 1; // D0(Easy)
        $reverted = $this->w[7] * $target + (1 - $this->w[7]) * $damped;
        return $this->clampDifficulty($reverted);
    }

    /** Nowa stabilność po poprawnym przypomnieniu (grade ≥ 2). */
    public function stabilityAfterRecall(float $difficulty, float $stability, float $retrievability, int $grade): float
    {
        $hardPenalty = $grade === self::HARD ? $this->w[15] : 1.0;
        $easyBonus = $grade === self::EASY ? $this->w[16] : 1.0;

        $inc = exp($this->w[8])
            * (11 - $difficulty)
            * $stability ** (-$this->w[9])
            * (exp($this->w[10] * (1 - $retrievability)) - 1)
            * $hardPenalty
            * $easyBonus;

        return $stability * (1 + $inc);
    }

    /** Nowa stabilność po wpadce (grade = Again); nigdy nie rośnie powyżej poprzedniej. */
    public function stabilityAfterLapse(float $difficulty, float $stability, float $retrievability): float
    {
        $lapse = $this->w[11]
            * $difficulty ** (-$this->w[12])
            * (($stability + 1) ** $this->w[13] - 1)
            * exp($this->w[14] * (1 - $retrievability));

        return max(0.1, min($lapse, $stability));
    }

    private function clampDifficulty(float $d): float
    {
        return min(10.0, max(1.0, $d));
    }
}
