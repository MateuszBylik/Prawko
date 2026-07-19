<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Podgląd konta użytkownika oczami administratora („zaloguj jako").
 *
 * Stan trzymamy WYŁĄCZNIE w sesji serwera (id administratora, który wszedł
 * w podgląd) — nigdy w ciasteczku ani w parametrze żądania, bo to dane, na
 * podstawie których przywracamy uprawnienia admina.
 *
 * Podgląd jest TYLKO DO ODCZYTU (patrz PreventImpersonatedWrites). To nie
 * ostrożnościowy kaprys, tylko jedyny sposób, by wymaganie „nie pojawia się
 * w aktywności" było prawdziwe z konstrukcji, a nie z filtrowania: aktywność
 * (`last_active_at`, liczba sesji, odpowiedzi) liczy się z ZAPISÓW, a każda
 * odpowiedź dodatkowo przestawia θ i stan FSRS podglądanego użytkownika.
 * Gdyby admin mógł pisać, popsułby cudzą naukę, a nie tylko statystykę.
 */
final class Impersonation
{
    private const KEY = 'impersonator_id';

    /** Czy bieżąca sesja jest podglądem cudzego konta. */
    public static function active(): bool
    {
        return Session::has(self::KEY);
    }

    public static function impersonatorId(): ?int
    {
        $id = Session::get(self::KEY);

        return $id === null ? null : (int) $id;
    }

    /** Administrator, który wszedł w podgląd (do pokazania na pasku). */
    public static function impersonator(): ?User
    {
        $id = self::impersonatorId();

        return $id === null ? null : User::find($id);
    }

    /**
     * Przełącza sesję na konto `$target`, zapamiętując, kto to zrobił.
     * Kolejność ma znaczenie: `Auth::login()` woła `session()->migrate(true)`,
     * które zmienia identyfikator sesji, ale PRZENOSI jej dane — dlatego znacznik
     * ustawiamy przed logowaniem i przeżywa on podmianę.
     */
    public static function start(User $admin, User $target): void
    {
        Session::put(self::KEY, $admin->getKey());

        Auth::login($target);
    }

    /**
     * Wraca na konto administratora. Zwraca go, albo null gdy powrót jest
     * niemożliwy (konto zniknęło lub straciło uprawnienia) — wtedy wywołujący
     * musi sesję zamknąć, a nie zostawić kogokolwiek zalogowanego.
     */
    public static function stop(): ?User
    {
        $admin = self::impersonator();

        Session::forget(self::KEY);

        if (! $admin || ! $admin->isAdmin()) {
            return null;
        }

        Auth::login($admin);

        return $admin;
    }
}
