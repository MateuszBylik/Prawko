<?php

namespace App\Http\Controllers;

use App\Models\DrivingCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Wybór kategorii przy pierwszym logowaniu (potem zmienia się ją w profilu). */
class CategoryChoiceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        // Ekran jest jednorazowy — kto już wybrał, nie ma tu czego szukać.
        if (! $request->user()->needsCategoryChoice()) {
            return redirect()->route('dashboard');
        }

        return view('category.choose', [
            'categories' => DrivingCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'driving_category_id' => [
                'required', 'integer',
                // `exists` na aktywnych: nieaktywna kategoria nie ma pytań, więc
                // wybór byłby pułapką — pusta pula i prognoza nie do policzenia.
                Rule::exists('driving_categories', 'id')->where('is_active', true),
            ],
        ]);

        $request->user()->forceFill($data)->save();

        return redirect()->route('dashboard')->with('success', __('app.category.saved'));
    }
}
