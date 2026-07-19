<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * BEZPIECZNIK: nie pozwól testom dotknąć bazy innej niż sqlite w pamięci.
     *
     * Dlaczego to istnieje: `php artisan config:cache` (robimy go na produkcji)
     * sprawia, że zacache'owany config WYGRYWA ze zmiennymi z phpunit.xml —
     * `<env name="DB_CONNECTION" value="sqlite"/>` przestaje działać i testy
     * celują w produkcyjną bazę MariaDB. Wtedy RefreshDatabase (migrate:fresh)
     * skasowałby prawdziwe dane. Uratowało nas kiedyś tylko APP_ENV=production,
     * bo Laravel blokuje wtedy destrukcyjne komendy bez --force — czyli ochrona
     * zależała od przypadku.
     *
     * Sprawdzenie musi być PRZED setUpTraits(), bo to tam RefreshDatabase
     * uruchamia migracje. Lek: `php artisan config:clear` przed testami.
     */
    protected function setUpTraits(): array
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "STOP: testy celują w bazę [{$connection}/{$database}] zamiast sqlite/:memory:. "
                .'Prawdopodobna przyczyna: zacache'.'owany config przesłania phpunit.xml. '
                .'Uruchom `php artisan config:clear`, zrób testy, potem `php artisan config:cache`.'
            );
        }

        return parent::setUpTraits();
    }
}
