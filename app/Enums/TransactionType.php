<?php

namespace App\Enums;

/**
 * Typ operacji na wirtualnym portfelu (żetony / kredyty).
 * Fundament pod funkcje premium odblokowywane za kredyty.
 */
enum TransactionType: string
{
    case Topup = 'topup';
    case Purchase = 'purchase';
    case Bonus = 'bonus';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Topup => 'Doładowanie',
            self::Purchase => 'Zakup',
            self::Bonus => 'Bonus',
            self::Refund => 'Zwrot',
            self::Adjustment => 'Korekta',
        };
    }
}
