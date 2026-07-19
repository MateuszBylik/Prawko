<?php

namespace App\Enums;

/**
 * Deklarowana pewność odpowiedzi — samoocena przed sprawdzeniem.
 *
 * Dwa poziomy, nie trzy: sygnał kalibracyjny jest najmocniejszy na skrajach,
 * a każdy dodatkowy przycisk to koszt na telefonie przy każdym pytaniu.
 */
enum Confidence: string
{
    case Sure = 'sure';   // „wiem" — deklaruję wiedzę
    case Guess = 'guess'; // „strzelam" — zgaduję

    public function label(): string
    {
        return __('enums.confidence.'.$this->value);
    }

    public function isGuess(): bool
    {
        return $this === self::Guess;
    }
}
