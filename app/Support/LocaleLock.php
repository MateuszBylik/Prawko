<?php

namespace App\Support;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Models\ExamSession;
use App\Models\User;

/**
 * Blokada języka na czas trwającego egzaminu próbnego.
 *
 * Dlaczego tylko egzamin: to POMIAR, a nie nauka. Przełączenie języka w połowie
 * zmienia warunki pomiaru — inne sformułowania, inny czas czytania, inny wynik —
 * i przestaje on być porównywalny z poprzednimi podejściami oraz z prognozą.
 * Z jakim językiem się zaczęło, z takim trzeba skończyć.
 *
 * Nauka i diagnoza blokady NIE mają: nauka to nie pomiar (można sobie przełączać
 * do woli), a diagnoza jest jednorazowa i i tak kończy się w jednym posiedzeniu.
 */
final class LocaleLock
{
    /** Trwający egzamin użytkownika albo null. Jedno zapytanie, bez modelu w cache. */
    public static function activeExam(?User $user): ?ExamSession
    {
        if (! $user) {
            return null;
        }

        return ExamSession::query()
            ->where('user_id', $user->getKey())
            ->where('mode', ExamMode::Exam->value)
            ->where('status', ExamStatus::InProgress->value)
            ->whereNotNull('locale')
            ->latest('started_at')
            ->first();
    }

    /** Język wymuszony przez trwający egzamin albo null, gdy nic nie blokuje. */
    public static function forcedLocale(?User $user): ?string
    {
        $locale = self::activeExam($user)?->locale;

        // Nieznany kod (np. język usunięty z configu po starcie egzaminu) nie może
        // zablokować interfejsu na czymś, czego nie umiemy wyświetlić.
        return $locale && array_key_exists($locale, config('locales')) ? $locale : null;
    }

    public static function locked(?User $user): bool
    {
        return self::forcedLocale($user) !== null;
    }
}
