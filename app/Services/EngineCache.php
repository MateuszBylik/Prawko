<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Wersjonowanie cache silnika bez Redis (na tym serwerze cache = baza danych).
 *
 * Prognoza „szansy zdania" i kolejka ryzyka zależą od globalnego θ_u oraz stanu
 * FSRS — a te zmieniają się po KAŻDEJ odpowiedzi. Zamiast kasować pojedyncze
 * klucze (store bazodanowy nie ma tagów/wildcardów), trzymamy licznik „epoki"
 * na użytkownika i wstawiamy go w klucz. Bump epoki unieważnia wszystkie stare
 * wpisy naraz — te dożywają swojego TTL i znikają.
 */
final class EngineCache
{
    /** Bieżąca epoka użytkownika (0, gdy jeszcze nie liczono). */
    public static function epoch(int $userId): int
    {
        return (int) Cache::get("prawko.epoch.$userId", 0);
    }

    /** Unieważnia cały cache silnika użytkownika (wywoływane po zapisie odpowiedzi). */
    public static function bump(int $userId): void
    {
        Cache::forever("prawko.epoch.$userId", self::epoch($userId) + 1);
    }

    /** Klucz cache dla danej wartości (np. 'forecast', 'risk') z wpiętą epoką. */
    public static function key(string $name, int $userId, int $categoryId): string
    {
        return sprintf('prawko.%s.%d.e%d.c%d', $name, $userId, self::epoch($userId), $categoryId);
    }
}
