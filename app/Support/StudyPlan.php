<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Plan nauki pod datę egzaminu (Etap 8). Liczony na bieżąco (tanio), nie cache'owany.
 *
 * status: no_date | past | ready | on_track | behind
 */
final readonly class StudyPlan
{
    public function __construct(
        public ?CarbonImmutable $examDate,
        public int $daysLeft,
        public float $passProbability,
        public float $targetPass,
        public int $total,
        public int $seen,
        public int $unseen,
        public int $dueToday,
        /** Cel na dziś: ile NOWYCH pytań trzeba dziś odkryć (ustalony na początek dnia). */
        public int $newPerDay,
        /** Ile z tego celu już zrobiłeś dzisiaj. */
        public int $newDoneToday,
        /** Ile nowych pytań zostało dziś do celu. */
        public int $newRemaining,
        /** Ile pytań zostało dziś łącznie (nowe + zaległe powtórki). */
        public int $dailyDose,
        public int $sessionSize,
        /** Odpowiedzi na dzień, uśrednione po REALNIE przepracowanych dniach (max 7). */
        public float $recentPace,
        /** Z ilu dni policzono `recentPace` — pokazywane wprost, by liczba nie była zagadką. */
        public int $paceDays,
        public string $status,
    ) {}

    public function hasDate(): bool
    {
        return $this->examDate !== null;
    }

    public function passPercent(): int
    {
        return (int) round($this->passProbability * 100);
    }

    public function targetPercent(): int
    {
        return (int) round($this->targetPass * 100);
    }

    public function coveragePercent(): int
    {
        return $this->total > 0 ? (int) round($this->seen / $this->total * 100) : 0;
    }

    /** Czy dzisiejsza dawka jest już zrobiona. */
    public function doseDone(): bool
    {
        return $this->dailyDose === 0;
    }

    /** Postęp dzisiejszego celu nowych pytań (0–100) — do paska na pulpicie. */
    public function newProgressPercent(): int
    {
        if ($this->newPerDay <= 0) {
            return 100;
        }

        return (int) min(100, round($this->newDoneToday / $this->newPerDay * 100));
    }

    /** Etykieta i ton statusu (semantyczny kolor). */
    public function statusMeta(): array
    {
        // Status z serwisu może być tylko jednym z pięciu; nieznany traktujemy jak
        // „brak daty", żeby widok nigdy nie dostał pustej etykiety.
        $key = in_array($this->status, ['ready', 'on_track', 'behind', 'past'], true) ? $this->status : 'no_date';

        $tone = match ($key) {
            'ready' => 'emerald',
            'on_track' => 'brand',
            'behind' => 'rose',
            default => 'slate',
        };

        return [
            'label' => __('enums.plan_status.'.$key.'.label'),
            'tone' => $tone,
            'note' => __('enums.plan_status.'.$key.'.note'),
        ];
    }
}
