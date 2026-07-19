<?php

namespace App\Services;

use App\Models\DrivingCategory;
use App\Models\ForecastSnapshot;
use App\Models\User;
use App\Support\ExamForecast;
use App\Support\Impersonation;
use Illuminate\Support\Collection;

/**
 * Historia „szansy zdania" (Etap 9).
 *
 * Migawkę zapisujemy leniwie — przy wejściu na pulpit, raz na dobę — zamiast
 * zadania cron. Dzięki temu historia powstaje dokładnie w te dni, w które
 * użytkownik korzysta z aplikacji, i nie ma osobnego mechanizmu do pilnowania.
 */
class ForecastHistoryService
{
    /** Zapisuje (lub aktualizuje) dzisiejszą migawkę prognozy. */
    public function record(User $user, DrivingCategory $category, ExamForecast $forecast): void
    {
        // Podgląd admina to jedyny ZAPIS, którego nie zatrzyma PreventImpersonatedWrites:
        // migawka powstaje przy zwykłym GET-cie pulpitu. Bez tego wejścia w podgląd
        // dopisywałoby użytkownikowi punkt na wykresie w dniu, w którym się nie uczył —
        // czyli dokładnie ten ślad aktywności, którego ma nie być.
        if (Impersonation::active()) {
            return;
        }

        // Bez sygnału (za mało odpowiedzi) prognoza to niemal sam prior —
        // zapisanie jej zaśmieciłoby wykres płaską linią „z sufitu".
        if (! $forecast->hasSignal()) {
            return;
        }

        ForecastSnapshot::updateOrCreate(
            [
                'user_id' => $user->id,
                'driving_category_id' => $category->id,
                'snapshot_date' => now()->toDateString(),
            ],
            [
                'pass_probability' => round($forecast->passProbability, 5),
                'coverage_percent' => round($forecast->coverage * 100, 2),
                'skill_theta' => $user->skill_theta,
                'answered_count' => $forecast->answered,
            ],
        );
    }

    /**
     * Seria do wykresu — od najstarszej migawki.
     *
     * @return Collection<int, ForecastSnapshot>
     */
    public function history(User $user, DrivingCategory $category, int $days = 90): Collection
    {
        return ForecastSnapshot::query()
            ->where('user_id', $user->id)
            ->where('driving_category_id', $category->id)
            ->recent($days)
            ->get();
    }
}
