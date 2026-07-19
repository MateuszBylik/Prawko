<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bez wybranej kategorii nauka nie ma sensu — cały silnik (pula pytań, skład
 * arkusza, prognoza) liczy się per kategoria. Przy pierwszym logowaniu odsyłamy
 * na ekran wyboru.
 *
 * Istniejące konta dostały kat. B w migracji, więc w praktyce dotyczy to tylko
 * nowo zakładanych.
 */
class EnsureCategoryChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->needsCategoryChoice()) {
            return redirect()->route('category.show');
        }

        return $next($request);
    }
}
