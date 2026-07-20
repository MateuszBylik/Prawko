<?php

namespace App\Http\Controllers;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\UserAnswer;
use App\Services\ExamForecastService;
use App\Services\ForecastHistoryService;
use App\Services\RiskQueueService;
use App\Services\StudyPlanService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ExamForecastService $forecast,
        RiskQueueService $risk,
        StudyPlanService $planner,
        ForecastHistoryService $historian,
    ): View {
        $user = $request->user();

        $completed = $user->examSessions()->where('status', ExamStatus::Completed->value);

        // Średni wynik procentowy ze wszystkich ukończonych sesji.
        $avgPercent = (clone $completed)
            ->where('max_score', '>', 0)
            ->selectRaw('AVG(score / max_score * 100) as avg')
            ->value('avg');

        $examQuery = (clone $completed)->where('mode', ExamMode::Exam->value);
        $examsTaken = (clone $examQuery)->count();
        $examsPassed = (clone $examQuery)->where('passed', true)->count();

        $stats = [
            'completed_sessions' => (clone $completed)->count(),
            'avg_percent' => $avgPercent !== null ? round((float) $avgPercent) : null,
            'exams_taken' => $examsTaken,
            'exams_passed' => $examsPassed,
            'answered_total' => UserAnswer::where('user_id', $user->id)->whereNotNull('answered_at')->count(),
            // „Opanowane" = poprawnie odpowiedziane co najmniej dwa razy z rzędu.
            'mastered' => $user->questionStats()->where('streak', '>=', 2)->count(),
            // Pytania, których termin powtórki (spaced repetition) już nadszedł.
            'due' => $user->questionStats()->whereNotNull('due_at')->where('due_at', '<=', now())->count(),
            'wallet_balance' => $user->wallet?->balance ?? 0,
        ];

        // Silnik szansy zdania (Etap 2): prognoza Monte Carlo + kolejka ryzyka.
        // Kategoria pochodzi z konta — nie zgadujemy już „pewnie B" i nie pytamy
        // o nią przy każdej sesji.
        $examCategory = $user->drivingCategory;

        // Czy pokazać onboarding? (użytkownik nie ukończył jeszcze Diagnozy)
        $needsDiagnosis = ! $user->examSessions()
            ->where('mode', ExamMode::Diagnosis->value)
            ->where('status', ExamStatus::Completed->value)
            ->exists();

        // Ile pytań kategorii jest jeszcze nowych (bez stanu FSRS) — pod przycisk
        // „Tylko nowe". „Widziane" liczymy tak samo jak plan (stability > 0).
        $newCount = $examCategory
            ? Question::query()->active()->forCategory($examCategory->id)
                ->whereNotIn('id', QuestionUserStat::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('stability')->where('stability', '>', 0)
                    ->select('question_id'))
                ->count()
            : 0;

        $forecastResult = $examCategory ? $forecast->for($user, $examCategory) : null;

        // Historia szansy zdania: migawkę zapisujemy leniwie, raz na dobę,
        // przy wejściu na pulpit — bez osobnego zadania cron.
        if ($examCategory && $forecastResult) {
            $historian->record($user, $examCategory, $forecastResult);
        }

        $plan = ($examCategory && $forecastResult) ? $planner->build($user, $examCategory, $forecastResult) : null;

        // Tryb ekspresowy — dostępny w oknie planu tylko w ostatnim tygodniu przed
        // egzaminem; działa jak „lepki" przełącznik konta. „Do zrobienia" = pytania
        // kategorii jeszcze nie utrwalone ani odpowiedziane poprawnie (streak < 1).
        $expressAvailable = $examCategory && $plan && $plan->hasDate() && $plan->status !== 'past'
            && $plan->daysLeft <= (int) config('prawko.express.available_within_days');

        $express = null;
        if ($expressAvailable) {
            $unsatisfied = Question::query()->active()->forCategory($examCategory->id)
                ->whereNotIn('id', QuestionUserStat::query()
                    ->where('user_id', $user->id)->where('streak', '>=', 1)
                    ->select('question_id'))
                ->count();

            $express = [
                'total' => $plan->total,
                'satisfied' => max(0, $plan->total - $unsatisfied),
                'unsatisfied' => $unsatisfied,
                'per_day' => (int) ceil($unsatisfied / max(1, $plan->daysLeft)),
                'size' => max(1, min($unsatisfied ?: 1, (int) config('prawko.express.session_size'))),
            ];
        }

        $expressOn = $expressAvailable && (bool) $user->express_mode;

        $inProgress = $user->examSessions()
            ->where('status', ExamStatus::InProgress->value)
            ->latest()->first();

        // Banner „Wznów sesję": dla egzaminu bez zapasu czasu (≤ próg lub po limicie)
        // wznowienie i tak domknie sesję — pokazujemy wprost „Zobacz wynik".
        $resumeLocked = $inProgress
            && $inProgress->mode === ExamMode::Exam
            && ($inProgress->time_limit_seconds ?: (int) config('prawko.exam.time_limit_seconds'))
                - (int) $inProgress->started_at->diffInSeconds(now())
                <= (int) config('prawko.exam.min_resume_seconds');

        return view('dashboard', [
            'forecastHistory' => $examCategory
                ? $historian->history($user, $examCategory)
                : collect(),
            'stats' => $stats,
            'examCategory' => $examCategory,
            'newCount' => $newCount,
            // Rozmiar sesji „tylko nowe" = tyle, ile pytań na egzaminie (32).
            'examSize' => collect(config('prawko.exam.composition'))->sum(fn ($l) => $l[2]),
            'needsDiagnosis' => $needsDiagnosis,
            'forecast' => $forecastResult,
            'plan' => $plan,
            'express' => $express,
            'expressAvailable' => $expressAvailable,
            'expressOn' => $expressOn,
            'riskQuestions' => $examCategory ? $risk->topQuestions($user, $examCategory, 6) : collect(),
            'inProgress' => $inProgress,
            'resumeLocked' => $resumeLocked,
            'recentSessions' => $user->examSessions()
                ->completed()->with('category')->latest('completed_at')->limit(6)->get(),
        ]);
    }
}
