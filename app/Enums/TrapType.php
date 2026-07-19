<?php

namespace App\Enums;

/**
 * Rodzaj „pułapki" w pytaniu — powierzchniowy wzorzec językowy, który najczęściej
 * prowadzi do błędu przez nieuważne czytanie. Wykrywany deterministycznie
 * (słowa-klucze), pozwala ćwiczyć konkretne typy haczyków.
 */
enum TrapType: string
{
    case Negation = 'negation';     // „nie", „z wyjątkiem", zakaz
    case Quantifier = 'quantifier'; // „zawsze", „nigdy", „tylko", „każdy"
    case Modality = 'modality';     // obowiązek („musisz") vs możliwość („możesz")
    case Scope = 'scope';           // warunek/wyjątek („jeżeli", „pod warunkiem")
    case Number = 'number';         // konkretna wartość liczbowa (próg)

    public function label(): string
    {
        return __('enums.trap.'.$this->value.'.label');
    }

    /** Krótka podpowiedź: na co uważać przy tym typie pułapki. */
    public function hint(): string
    {
        return __('enums.trap.'.$this->value.'.hint');
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Negation => 'badge-rose',
            self::Quantifier => 'badge-amber',
            self::Modality => 'badge-brand',
            self::Scope => 'badge-slate',
            self::Number => 'badge-emerald',
        };
    }
}
