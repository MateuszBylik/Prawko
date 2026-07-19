<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionSimilar;
use App\Services\ExplanationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint wyjaśnień pytań (AI). Zwraca zapisane wyjaśnienie albo — jeśli AI
 * włączone i wyjaśnienia jeszcze nie ma — generuje je, zapisuje i zwraca.
 * Front (Alpine) woła to leniwie: przy błędnej odpowiedzi lub po kliknięciu „Wyjaśnij".
 */
class ExplanationController extends Controller
{
    public function show(Request $request, Question $question, ExplanationService $service): JsonResponse
    {
        // Kontekst błędu: wariant wybrany przez uczącego się (musi należeć do pytania).
        $chosen = null;
        if ($id = $request->integer('chosen_answer_id')) {
            $chosen = Answer::where('id', $id)->where('question_id', $question->id)->first();
        }

        $text = $service->explain($question, $chosen);

        return response()->json([
            'enabled' => $service->enabled(),
            'available' => $text !== null,
            'explanation' => $text,
            'source' => $question->explanation_source,
        ]);
    }

    /** Wyjaśnienie różnicy w parze mylącej (Etap 6, hybryda) — leniwie generowane. */
    public function difference(QuestionSimilar $pair, ExplanationService $service): JsonResponse
    {
        $text = $service->explainDifference($pair);

        return response()->json([
            'enabled' => $service->enabled(),
            'available' => $text !== null,
            'difference' => $text,
        ]);
    }
}
