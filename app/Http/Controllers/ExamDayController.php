<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Odpowiedź na pytanie z dnia egzaminu: „jak poszło?".
 *
 * Cztery wyjścia:
 *  - passed     → zapisz zdane podejście (trofeum), wyzeruj datę.
 *  - failed     → zapisz niezdane podejście, wyzeruj datę, uczymy się dalej.
 *  - later       → „będę mieć dzisiaj": odłóż pytanie na dziś (drzemka do jutra).
 *  - reschedule → „zmieniłem termin": ustaw nową datę egzaminu.
 */
class ExamDayController extends Controller
{
    public function respond(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'outcome' => ['required', 'in:passed,failed,later,reschedule'],
            'exam_date' => [
                'nullable',
                'required_if:outcome,reschedule',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addYears(2)->toDateString(),
            ],
        ]);

        // Data, którą wpisujemy do podejścia: dzień zaplanowanego egzaminu. Gdyby
        // z jakiegoś powodu jej nie było, bierzemy dziś — podejście i tak istnieje.
        $takenOn = $user->exam_date ?? today();

        return match ($data['outcome']) {
            'passed' => $this->record($user, $takenOn, passed: true)
                ->with('success', __('exam_day.flash.passed')),

            'failed' => $this->record($user, $takenOn, passed: false)
                ->with('info', __('exam_day.flash.failed')),

            // Drzemka: nie zapisujemy podejścia, tylko chowamy pytanie do jutra.
            'later' => tap(back(), fn () => $user->forceFill([
                'exam_prompt_snoozed_on' => today(),
            ])->save()),

            'reschedule' => tap(
                back()->with('success', __('exam_day.flash.rescheduled')),
                fn () => $user->forceFill([
                    'exam_date' => $data['exam_date'],
                    'exam_prompt_snoozed_on' => null,
                ])->save()
            ),
        };
    }

    /**
     * Zapisuje podejście i zamyka temat egzaminu (zerujemy datę i drzemkę), żeby
     * pytanie nie wróciło. Nowy termin użytkownik ustawi z planu na pulpicie.
     */
    private function record($user, $takenOn, bool $passed): RedirectResponse
    {
        $user->examAttempts()->create([
            'driving_category_id' => $user->driving_category_id,
            'passed' => $passed,
            'taken_on' => $takenOn,
        ]);

        $user->forceFill([
            'exam_date' => null,
            'exam_prompt_snoozed_on' => null,
        ])->save();

        return back();
    }
}
