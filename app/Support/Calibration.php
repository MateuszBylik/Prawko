<?php

namespace App\Support;

/**
 * Kalibracja pewności: czy Twoja deklaracja („wiem" / „strzelam") zgadza się
 * z wynikiem. Tabela 2×2 zbierana w trybie nauki.
 *
 * Dwie liczby, które naprawdę coś znaczą:
 *
 *  • PEWNE BŁĘDY — „wiedziałem" i pomyliłem się. Najgroźniejsza kategoria, bo
 *    nic w Tobie nie sygnalizuje problemu: na egzaminie zaznaczysz je spokojnie
 *    i stracisz punkty. Zwykłe „nie wiem" przynajmniej widać.
 *
 *  • TRAFNOŚĆ STRZAŁÓW vs POZIOM PRZYPADKU — sam procent trafionych strzałów
 *    jest bez wartości, dopóki nie zestawi się go z losowaniem. Pytania
 *    podstawowe są TAK/NIE (przypadek = 50%), specjalistyczne A/B/C (33%).
 *    Dlatego liczymy poziom przypadku ważony tym, co faktycznie obstawiałeś.
 *    Trafność ≈ przypadek → strzały nic nie niosą. Wyraźnie wyżej → wiesz
 *    więcej, niż Ci się wydaje (i za nisko oceniasz własną pewność).
 */
final readonly class Calibration
{
    public function __construct(
        public int $sureCorrect,
        public int $sureWrong,
        public int $guessCorrect,
        public int $guessWrong,
        /** Oczekiwana trafność, gdyby strzały były czysto losowe (ważona typem pytań). */
        public float $chanceLevel,
    ) {}

    public function total(): int
    {
        return $this->sureCorrect + $this->sureWrong + $this->guessCorrect + $this->guessWrong;
    }

    public function sureTotal(): int
    {
        return $this->sureCorrect + $this->sureWrong;
    }

    public function guessTotal(): int
    {
        return $this->guessCorrect + $this->guessWrong;
    }

    /** Czy zebrano dość odpowiedzi, by w ogóle pokazywać procenty. */
    public function hasSignal(): bool
    {
        return $this->total() >= 10;
    }

    /** Odsetek deklaracji „wiem", które okazały się błędne. */
    public function overconfidence(): ?float
    {
        return $this->sureTotal() > 0 ? $this->sureWrong / $this->sureTotal() : null;
    }

    /** Skuteczność na pytaniach oznaczonych „wiem". */
    public function sureAccuracy(): ?float
    {
        return $this->sureTotal() > 0 ? $this->sureCorrect / $this->sureTotal() : null;
    }

    /** Skuteczność na pytaniach oznaczonych „strzelam". */
    public function guessAccuracy(): ?float
    {
        return $this->guessTotal() > 0 ? $this->guessCorrect / $this->guessTotal() : null;
    }

    /** O ile strzały biją poziom przypadku (dodatnie = jednak coś wiesz). */
    public function guessLift(): ?float
    {
        $acc = $this->guessAccuracy();

        return $acc === null ? null : $acc - $this->chanceLevel;
    }

    /**
     * Jednozdaniowa diagnoza kalibracji — z tonem do plakietki.
     *
     * @return array{label: string, tone: string, note: string}
     */
    public function verdict(): array
    {
        if (! $this->hasSignal()) {
            return [
                'label' => __('enums.calibration.no_data.label'),
                'tone' => 'slate',
                'note' => __('enums.calibration.no_data.note'),
            ];
        }

        $over = $this->overconfidence() ?? 0.0;
        $lift = $this->guessLift();

        // Kolejność ma znaczenie: nadmierna pewność to realny koszt na egzaminie,
        // niedoszacowanie własnej wiedzy tylko marnuje czas na naukę.
        if ($over >= 0.15) {
            return [
                'label' => __('enums.calibration.overconfident.label'),
                'tone' => 'rose',
                'note' => __('enums.calibration.overconfident.note'),
            ];
        }

        if ($lift !== null && $lift >= 0.20 && $this->guessTotal() >= 5) {
            return [
                'label' => __('enums.calibration.underconfident.label'),
                'tone' => 'brand',
                'note' => __('enums.calibration.underconfident.note'),
            ];
        }

        return [
            'label' => __('enums.calibration.good.label'),
            'tone' => 'emerald',
            'note' => __('enums.calibration.good.note'),
        ];
    }
}
