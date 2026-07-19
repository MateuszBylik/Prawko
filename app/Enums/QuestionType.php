<?php

namespace App\Enums;

/**
 * Typ pytania zgodny z podziałem na egzaminie państwowym.
 *
 *  - Basic (podstawowe)      → pytanie TAK / NIE, zwykle z materiałem wideo.
 *  - Specialist (specjalist.) → pytanie jednokrotnego wyboru A / B / C.
 */
enum QuestionType: string
{
    case Basic = 'basic';
    case Specialist = 'specialist';

    public function label(): string
    {
        return __('enums.question_type.'.$this->value);
    }

    /** Liczba wariantów odpowiedzi dla danego typu pytania. */
    public function optionCount(): int
    {
        return $this === self::Basic ? 2 : 3;
    }

    /** Czy pytanie jest typu TAK/NIE. */
    public function isTrueFalse(): bool
    {
        return $this === self::Basic;
    }
}
