<?php

namespace App\Support;

/**
 * Rozkład oczekiwanej straty punktów na warstwy arkusza i typy pułapek.
 *
 * To NIE jest osobne oszacowanie, tylko rozbicie prognozy na czynniki pierwsze.
 * Strata pojedynczego pytania (patrz RiskQueueService) to
 *
 *     loss(q) = (n/N) · pkt · (1 − p_correct)
 *
 * a suma po całej warstwie zwija się do n · pkt · (1 − średnie p), czyli dokładnie
 * tylu punktów, ile warstwa oddaje na egzaminie. Dlatego suma strat wszystkich
 * warstw równa się `maxScore − oczekiwany wynik` z Monte Carlo (patrz test) —
 * ekran nie może zacząć mówić czegoś innego niż liczba na pulpicie.
 *
 * Sens praktyczny: procent skuteczności myli, bo warstwy mają różną wagę. Warstwa
 * po 3 pkt oddaje 30 z 74 punktów, więc te same „86%" kosztują w niej wielokrotnie
 * więcej niż w warstwie po 1 pkt. Sortujemy po stracie, nie po procencie.
 *
 * @phpstan-type LayerRow array{type: \App\Enums\QuestionType, points: int, count: int,
 *     pool_size: int, pool_points: int, mean_p: float, expected: float, loss: float,
 *     coverage: float, hist_n: int, hist_correct: int, hist_accuracy: float|null}
 * @phpstan-type TrapRow array{trap: \App\Enums\TrapType, pool_size: int, loss: float,
 *     hist_n: int, hist_correct: int, hist_accuracy: float|null}
 */
final readonly class LossBreakdown
{
    /**
     * @param  array<int, LayerRow>  $layers  malejąco wg straty
     * @param  array<int, TrapRow>  $traps  malejąco wg straty
     */
    public function __construct(
        public array $layers,
        public array $traps,
        public int $maxScore,
        public float $expectedScore,
        public int $threshold,
    ) {}

    /** Ile punktów oczekiwanie tracisz — suma strat wszystkich warstw. */
    public function totalLoss(): float
    {
        return $this->maxScore - $this->expectedScore;
    }

    /** Ile punktów brakuje do progu (dodatnie = brakuje, ujemne = zapas). */
    public function gap(): float
    {
        return $this->threshold - $this->expectedScore;
    }

    public function isShort(): bool
    {
        return $this->expectedScore < $this->threshold;
    }

    /** Największa strata pojedynczej warstwy — skala dla pasków w widoku. */
    public function maxLayerLoss(): float
    {
        return max(0.001, max(array_map(fn ($l) => $l['loss'], $this->layers) ?: [0.001]));
    }

    public function maxTrapLoss(): float
    {
        return max(0.001, max(array_map(fn ($t) => $t['loss'], $this->traps) ?: [0.001]));
    }

    /** Warstwa o największej stracie — „tu toczy się gra". */
    public function worstLayer(): ?array
    {
        return $this->layers[0] ?? null;
    }
}
