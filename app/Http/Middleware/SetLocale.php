<?php

namespace App\Http\Middleware;

use App\Support\LocaleLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustala język interfejsu. Kolejność źródeł jest istotna:
 *
 *  1. TRWAJĄCY EGZAMIN — wygrywa ze wszystkim. Egzamin to pomiar; zmiana języka
 *     w połowie unieważniłaby go (patrz LocaleLock). Wymuszamy nawet wtedy, gdy
 *     konto ma ustawiony inny język, bo liczy się to, w czym egzamin się zaczął.
 *  2. KONTO (users.locale) — świadomy wybór zalogowanego użytkownika, przeżywa
 *     urządzenia i wylogowanie.
 *  3. SESJA — wybór gościa na stronie głównej, zanim w ogóle się zalogował.
 *  4. Domyślny z config/app.php.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleLock::forcedLocale($request->user())
            ?? $request->user()?->locale
            ?? $request->session()->get('locale');

        // Wartość z sesji i z bazy zapisuje sam użytkownik — do setLocale() nie może
        // trafić kod, którego nie znamy, bo dałby brakujące tłumaczenia zamiast błędu.
        if (is_string($locale) && array_key_exists($locale, config('locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
