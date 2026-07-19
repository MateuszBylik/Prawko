<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Wejście administratora w podgląd cudzego konta i powrót na własne. */
class ImpersonationController extends Controller
{
    /** Rozpoczyna podgląd. Trasa jest za middlewarem `admin`. */
    public function store(Request $request, User $user): RedirectResponse
    {
        // Zagnieżdżanie podglądu rozspójniłoby powrót (kogo przywracać?).
        abort_if(Impersonation::active(), 403, __('app.impersonation.nested'));

        abort_if($user->is($request->user()), 403, __('app.impersonation.self'));

        // Podgląd admina nie ma sensu (i tak widzisz to samo), a otwierałby drogę
        // do przejęcia cudzych uprawnień administracyjnych.
        abort_if($user->isAdmin(), 403, __('app.impersonation.admin'));

        Impersonation::start($request->user(), $user);

        return redirect()->route('dashboard')
            ->with('success', __('app.impersonation.started', ['email' => $user->email]));
    }

    /**
     * Kończy podgląd i wraca na konto administratora.
     *
     * Trasa NIE jest za middlewarem `admin`, bo w trakcie podglądu jesteś
     * zalogowany jako zwykły użytkownik i taki warunek zamknąłby drogę wyjścia.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (! Impersonation::active()) {
            return redirect()->route('dashboard');
        }

        $admin = Impersonation::stop();

        // Konto admina zniknęło albo straciło uprawnienia w trakcie podglądu —
        // nie zostawiamy nikogo zalogowanego na cudzym koncie.
        if (! $admin) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', __('app.impersonation.restore_failed'));
        }

        return redirect()->route('admin.users.index')
            ->with('success', __('app.impersonation.stopped'));
    }
}
