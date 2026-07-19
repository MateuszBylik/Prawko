<?php

namespace App\Http\Controllers;

use App\Enums\ExamMode;
use App\Http\Requests\AnswerQuestionRequest;
use App\Http\Requests\StartSessionRequest;
use App\Models\Answer;
use App\Models\ExamSession;
use App\Models\UserAnswer;
use App\Services\ExamForecastService;
use App\Services\ExamService;
use App\Services\RiskQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ExamSessionController extends Controller
{
    /** Rozpoczyna nową sesję nauki lub egzaminu i przenosi do pierwszego pytania. */
    public function store(StartSessionRequest $request, ExamService $exam): RedirectResponse
    {
        // Kategoria jest cechą KONTA, nie parametrem żądania. Dzięki temu nie da się
        // jej podmienić dosłanym polem, a formularze nie muszą jej nosić — middleware
        // `category` gwarantuje, że na tym etapie jest już wybrana.
        $category = $request->user()->drivingCategory;

        if (! $category || ! $category->is_active) {
            return redirect()->route('category.show')
                ->with('error', __('enums.errors.no_category'));
        }

        $mode = ExamMode::from($request->validated('mode'));

        // „Tylko nowe" — sesja z pytań jeszcze niewidzianych (patrz feature: nauka
        // nowych). Zawężenie działa wyłącznie w nauce; egzamin/diagnoza mają swój
        // stały skład, więc scope jest tam bez znaczenia.
        $scope = $mode === ExamMode::Practice ? $request->validated('scope') : null;

        try {
            $session = $exam->start($request->user(), $category, $mode, $request->integer('size') ?: null, $scope);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sessions.question', [$session, 1]);
    }

    /** Wchodzi do sesji: kieruje do pierwszego nieudzielonego pytania lub do wyniku. */
    public function show(ExamSession $session): RedirectResponse
    {
        $this->authorize('view', $session);

        if ($session->isCompleted()) {
            return redirect()->route('sessions.result', $session);
        }

        $target = $session->answers()->whereNull('answered_at')->orderBy('position')->first()
            ?? $session->answers()->orderBy('position')->first();

        return redirect()->route('sessions.question', [$session, $target->position]);
    }

    /** Wyświetla pojedyncze pytanie sesji. */
    public function question(ExamSession $session, int $position): View|RedirectResponse
    {
        $this->authorize('view', $session);

        if ($session->isCompleted()) {
            return redirect()->route('sessions.result', $session);
        }

        // Egzamin: wymuszony przód (bez cofania i przeskoków), czas liczony per-pytanie.
        if ($session->mode === ExamMode::Exam) {
            return $this->examQuestion($session, $position);
        }

        $current = $session->answers()
            ->where('position', $position)
            ->with(['question.answers', 'question.categories', 'selectedAnswer'])
            ->firstOrFail();

        // Lekki zestaw do nawigatora pytań (numerki + stan udzielenia odpowiedzi).
        $navigator = $session->answers()->get(['position', 'answered_at', 'is_correct']);

        // „Nowe": pytanie, na które użytkownik nigdy wcześniej nie odpowiedział poza
        // tą sesją. Liczymy z odpowiedzi (a nie ze stanu FSRS), więc plakietka trzyma
        // się także po udzieleniu odpowiedzi w tej sesji (gdzie stat już istnieje).
        // Tylko w nauce — diagnoza mierzy θ i nie podpowiada, co jest nowe.
        $isNew = $session->mode === ExamMode::Practice
            && ! UserAnswer::where('user_id', $session->user_id)
                ->where('question_id', $current->question_id)
                ->whereNotNull('answered_at')
                ->where('exam_session_id', '!=', $session->id)
                ->exists();

        return view('sessions.question', [
            'session' => $session,
            'current' => $current,
            'question' => $current->question,
            'position' => $position,
            'total' => $session->total_questions,
            'prev' => $position > 1 ? $position - 1 : null,
            'next' => $position < $session->total_questions ? $position + 1 : null,
            'navigator' => $navigator,
            'isNew' => $isNew,
            'answeredCount' => $navigator->whereNotNull('answered_at')->count(),
            // W trybie nauki po odpowiedzi pokazujemy od razu wynik i wyjaśnienie.
            'showFeedback' => $session->mode->givesInstantFeedback() && $current->isAnswered(),
        ]);
    }

    /** Widok pytania egzaminu — wymusza kolejność (kursor = pierwsze nieodpowiedziane). */
    private function examQuestion(ExamSession $session, int $position, ?ExamService $exam = null): View|RedirectResponse
    {
        $cursor = $session->answers()->whereNull('answered_at')->orderBy('position')->value('position');

        // Wszystko odpowiedziane, ale sesja niezamknięta → domknij i pokaż wynik.
        if ($cursor === null) {
            ($exam ?? app(ExamService::class))->complete($session);

            return redirect()->route('sessions.result', $session);
        }

        // Bez cofania i bez przeskoków — zawsze na bieżącym pytaniu.
        if ($position !== $cursor) {
            return redirect()->route('sessions.question', [$session, $cursor]);
        }

        $current = $session->answers()
            ->where('position', $position)
            ->with(['question.answers', 'question.categories'])
            ->firstOrFail();

        return view('sessions.exam-question', [
            'session' => $session,
            'current' => $current,
            'question' => $current->question,
            'position' => $position,
            'total' => $session->total_questions,
            'answeredCount' => $session->answers()->whereNotNull('answered_at')->count(),
        ]);
    }

    /** Zapisuje odpowiedź na bieżące pytanie. */
    public function answer(AnswerQuestionRequest $request, ExamSession $session, int $position, ExamService $exam): RedirectResponse
    {
        $this->authorize('update', $session);
        abort_if($session->isCompleted(), 403);

        $current = $session->answers()->where('position', $position)->firstOrFail();

        // Egzamin bez cofania: nie wolno ponownie odpowiadać na już udzielone pytanie.
        if ($session->mode === ExamMode::Exam && $current->answered_at !== null) {
            $cursor = $session->answers()->whereNull('answered_at')->orderBy('position')->value('position');

            return $cursor
                ? redirect()->route('sessions.question', [$session, $cursor])
                : redirect()->route('sessions.result', $session);
        }

        // Odpowiedź musi należeć do tego konkretnego pytania (obrona przed manipulacją).
        $selected = null;
        if ($answerId = $request->integer('answer_id')) {
            $selected = Answer::where('id', $answerId)
                ->where('question_id', $current->question_id)
                ->first();
        }

        // Pewność bierzemy pod uwagę tylko tam, gdzie o nią pytamy (nauka). Gdyby
        // ktoś dosłał `confidence` w egzaminie, i tak ją tu odcinamy — arkusz PORD
        // ma zostać nietknięty, a diagnoza mierzy θ przy niezmienionym przebiegu.
        $confidence = $session->mode->collectsConfidence() ? $request->confidence() : null;

        $exam->answer($current, $selected, $request->integer('time_spent_ms') ?: null, $confidence);

        // Nauka: zostań na pytaniu i pokaż feedback.
        if ($session->mode->givesInstantFeedback()) {
            return redirect()->route('sessions.question', [$session, $position]);
        }

        // Egzamin: do pierwszego nieodpowiedzianego; gdy brak — zakończ i pokaż wynik.
        if ($session->mode === ExamMode::Exam) {
            $cursor = $session->answers()->whereNull('answered_at')->orderBy('position')->value('position');

            if ($cursor === null) {
                $exam->complete($session);

                return redirect()->route('sessions.result', $session);
            }

            return redirect()->route('sessions.question', [$session, $cursor]);
        }

        // Diagnoza: ostatnie pytanie kończy sesję — przycisk mówi wprost „Zakończ sesję".
        // Wcześniej min($position + 1, $total) przekierowywał z powrotem na to samo
        // pytanie (ślepy zaułek) i trzeba było szukać osobnego przycisku na dole.
        if ($position >= $session->total_questions) {
            $exam->complete($session);

            return redirect()->route('sessions.result', $session);
        }

        return redirect()->route('sessions.question', [$session, $position + 1]);
    }

    /** Kończy sesję i wylicza wynik. */
    public function complete(ExamSession $session, ExamService $exam): RedirectResponse
    {
        $this->authorize('update', $session);

        if (! $session->isCompleted()) {
            $exam->complete($session);
        }

        return redirect()->route('sessions.result', $session);
    }

    /** Podsumowanie sesji wraz z przeglądem odpowiedzi (dla Diagnozy — ekran „szansy zdania"). */
    public function result(ExamSession $session, ExamForecastService $forecast, RiskQueueService $risk): View|RedirectResponse
    {
        $this->authorize('view', $session);

        if (! $session->isCompleted()) {
            return redirect()->route('sessions.show', $session);
        }

        $session->load('category');

        // Diagnoza: odsłona wyniku silnika (szansa zdania) + przegląd popełnionych błędów.
        if ($session->mode->isDiagnosis()) {
            $session->load(['answers.question.answers', 'answers.selectedAnswer']);

            return view('sessions.diagnosis-result', [
                'session' => $session,
                'forecast' => $forecast->for($session->user, $session->category),
                'riskQuestions' => $risk->topQuestions($session->user, $session->category, 6),
            ]);
        }

        $session->load(['answers.question.answers', 'answers.selectedAnswer']);

        return view('sessions.result', ['session' => $session]);
    }
}
