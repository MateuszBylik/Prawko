<?php

namespace App\Enums;

enum ExamStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'W trakcie',
            self::Completed => 'Zakończona',
            self::Abandoned => 'Porzucona',
        };
    }
}
