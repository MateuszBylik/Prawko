<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konto administratora służy do zarządzania, nie do nauki.
 *
 * Blokujemy trasy uczące (pulpit, sesje, plan, straty), a NIE ruszamy bazy pytań
 * ani profilu — admin musi móc przeglądać i weryfikować pytania.
 *
 * To nie jest bariera bezpieczeństwa, tylko porządek: gdyby admin rozwiązywał testy,
 * silnik liczyłby mu θ, prognozę i plan, a panel pokazywałby go w statystykach obok
 * kursantów. Przekierowanie zamiast 403 — to nie jest próba włamania, tylko klik
 * w link, którego dla admina nie powinno być.
 *
 * Podgląd konta kursanta (Impersonation) działa niezależnie: admin jest wtedy
 * zalogowany jako kursant, więc widzi jego pulpit — tyle że tylko do odczytu.
 */
class DenyAdminLearning
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            // Bez komunikatu przy zwykłym wejściu: admin ma własny pulpit i to jest
            // jego strona domowa, a nie błąd. Wyjaśniamy tylko wtedy, gdy próbował
            // coś ZROBIĆ (start sesji, zapis planu) — wtedy cisza byłaby myląca.
            $redirect = redirect()->route('admin.dashboard');

            return $request->isMethodSafe()
                ? $redirect
                : $redirect->with('error', __('app.admin_only'));
        }

        return $next($request);
    }
}
