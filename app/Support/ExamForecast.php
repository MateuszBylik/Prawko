<?php

namespace App\Support;

/**
 * Wynik estymatora Monte Carlo — „szansa zdania" wraz z prognozą wyniku.
 * Niemutowalny i serializowalny (trzymany w cache silnika, patrz EngineCache).
 *
 *   passProbability — P(wynik ≥ próg zdania) z symulacji arkusza egzaminu,
 *   medianScore     — mediana punktów, ciLow/ciHigh — 90% przedział (p5–p95),
 *   coverage        — jaka część arkusza to pytania z realnymi danymi FSRS
 *                     (reszta liczona z priora) — miara pewności oszacowania.
 */
final readonly class ExamForecast
{
    public function __construct(
        public float $passProbability,
        public float $expectedScore,
        public int $medianScore,
        public int $ciLow,
        public int $ciHigh,
        public int $threshold,
        public int $maxScore,
        public float $coverage,
        public int $samples,
        public int $answered,
    ) {}

    /** @return array<string, int|float> */
    public function toArray(): array
    {
        return [
            'passProbability' => $this->passProbability,
            'expectedScore' => $this->expectedScore,
            'medianScore' => $this->medianScore,
            'ciLow' => $this->ciLow,
            'ciHigh' => $this->ciHigh,
            'threshold' => $this->threshold,
            'maxScore' => $this->maxScore,
            'coverage' => $this->coverage,
            'samples' => $this->samples,
            'answered' => $this->answered,
        ];
    }

    /**
     * Odtworzenie z tablicy — cache bazodanowy Laravela nie robi round-tripu obiektów
     * (wracają jako __PHP_Incomplete_Class), więc trzymamy w cache czystą tablicę.
     *
     * @param  array<string, int|float>  $a
     */
    public static function fromArray(array $a): self
    {
        return new self(
            (float) $a['passProbability'],
            (float) $a['expectedScore'],
            (int) $a['medianScore'],
            (int) $a['ciLow'],
            (int) $a['ciHigh'],
            (int) $a['threshold'],
            (int) $a['maxScore'],
            (float) $a['coverage'],
            (int) $a['samples'],
            (int) $a['answered'],
        );
    }

    public function passPercent(): int
    {
        return (int) round($this->passProbability * 100);
    }

    public function coveragePercent(): int
    {
        return (int) round($this->coverage * 100);
    }

    /** Czy jest dość danych, by pokazać liczbę zamiast zachęty do rozwiązania pytań. */
    public function hasSignal(): bool
    {
        return $this->answered >= 10;
    }

    /** Etykieta i ton (semantyczny kolor) poziomu gotowości. */
    public function readiness(): array
    {
        [$key, $tone] = match (true) {
            $this->passProbability >= 0.85 => ['ready', 'emerald'],
            $this->passProbability >= 0.60 => ['close', 'brand'],
            $this->passProbability >= 0.35 => ['learning', 'amber'],
            default => ['start', 'rose'],
        };

        return ['label' => __('enums.readiness.'.$key), 'tone' => $tone];
    }

    /** Pozycja (%) na osi 0..max — do pozycjonowania znaczników na pasku prognozy. */
    public function pctOf(int $points): float
    {
        return $this->maxScore > 0 ? min(100, max(0, $points / $this->maxScore * 100)) : 0;
    }
}
