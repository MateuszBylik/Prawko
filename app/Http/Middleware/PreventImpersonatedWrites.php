<?php

namespace App\Http\Middleware;

use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * W podglądzie cudzego konta wpuszczamy wyłącznie odczyt.
 *
 * Dlaczego twardo, a nie „nie zapisuj aktywności": aktywność użytkownika
 * (`last_active_at`, liczba sesji i odpowiedzi) jest liczona z jego zapisów, a
 * każda odpowiedź dodatkowo przestawia θ, stan FSRS i prognozę. Filtrowanie
 * takich śladów po fakcie wymagałoby pamiętania o podglądzie w każdym miejscu,
 * które cokolwiek zapisuje — jedno przeoczenie i admin po cichu psuje cudzą naukę.
 * Blokada na wejściu daje tę gwarancję raz, dla całej aplikacji.
 *
 * Wyjątki są dwa i oba kończą podgląd, zamiast go pogłębiać: powrót na konto
 * administratora i wylogowanie.
 */
class PreventImpersonatedWrites
{
    /** Trasy, które muszą działać mimo blokady — bo są drogą wyjścia. */
    private const ALLOWED = ['impersonate.stop', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Impersonation::active() || $request->isMethodSafe()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        abort(403, __('app.impersonation.read_only_error'));
    }
}
