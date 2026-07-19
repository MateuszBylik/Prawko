<?php

namespace App\Http\Controllers;

use App\Support\LocaleLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Zmiana języka interfejsu. */
class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        // Nieznany kod odrzucamy: to parametr z adresu, więc może być czymkolwiek.
        abort_unless(array_key_exists($locale, config('locales')), 404);

        // Egzamin w toku zamraża język — inaczej dałoby się zmienić warunki pomiaru
        // w jego trakcie. Interfejs chowa przełącznik, ale to tylko UI; prawdziwą
        // bramką jest ten warunek, bo adres da się wpisać ręcznie.
        if (LocaleLock::locked($request->user())) {
            return back()->with('error', __('app.locale.locked_during_exam'));
        }

        // Zalogowanemu zapisujemy wybór na koncie (przeżyje wylogowanie i inne
        // urządzenie), gościowi na stronie głównej — w sesji, bo nie ma gdzie indziej.
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        $request->session()->put('locale', $locale);

        return back();
    }
}
