<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Ustawianie/usuwanie daty egzaminu (Etap 8 — plan nauki). */
class StudyPlanController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exam_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addYears(2)->toDateString()],
        ]);

        $request->user()->update(['exam_date' => $data['exam_date'] ?? null]);

        return back()->with('success', ($data['exam_date'] ?? null)
            ? __('app.plan.saved')
            : __('app.plan.removed'));
    }
}
