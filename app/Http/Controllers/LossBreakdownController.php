<?php

namespace App\Http\Controllers;

use App\Services\CalibrationService;
use App\Services\ExamForecastService;
use App\Services\LossBreakdownService;
use App\Services\RiskQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * „Gdzie tracisz punkty" — rozbicie prognozy z pulpitu na warstwy arkusza,
 * typy pułapek i kalibrację pewności. Ekran analityczny: wchodzi się tu z karty
 * prognozy, żeby zobaczyć, z czego składa się jedna liczba na pulpicie.
 */
class LossBreakdownController extends Controller
{
    public function index(
        Request $request,
        LossBreakdownService $losses,
        ExamForecastService $forecast,
        CalibrationService $calibration,
        RiskQueueService $risk,
    ): View|RedirectResponse {
        $user = $request->user();

        $category = $user->drivingCategory;

        if (! $category) {
            return redirect()->route('category.show')->with('error', __('enums.errors.pick_category'));
        }

        $breakdown = $losses->for($user, $category);
        $worst = $breakdown->worstLayer();

        return view('losses.index', [
            'category' => $category,
            'breakdown' => $breakdown,
            'forecast' => $forecast->for($user, $category),
            'calibration' => $calibration->for($user),
            'sureMistakes' => $calibration->sureMistakes($user),
            // Kolejka ryzyka jest już posortowana po tej samej stracie, więc
            // „od czego zacząć" nie jest osobną heurystyką, tylko tym samym
            // rachunkiem zejściem na poziom pojedynczych pytań.
            'riskQuestions' => $worst ? $risk->topQuestions($user, $category, 6) : collect(),
        ]);
    }
}
