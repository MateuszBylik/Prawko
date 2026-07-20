<?php

namespace App\Services;

use App\Enums\Confidence;
use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logika sesji rozwiązywania pytań: budowanie zestawu, zapis odpowiedzi
 * i podsumowanie wyniku. Kontrolery pozostają cienkie, a reguły egzaminu
 * są w jednym miejscu.
 */
class ExamService
{
    public function __construct(
        private readonly KnowledgeService $knowledge,
        private readonly RiskQueueService $risk,
        private readonly AbilityService $ability,
    ) {}

    /** Rozpoczyna sesję i generuje dla niej zestaw pytań (jako wiersze user_answers). */
    public function start(User $user, DrivingCategory $category, ExamMode $mode, ?int $size = null, ?string $scope = null): ExamSession
    {
        $questions = match ($mode) {
            ExamMode::Exam => $this->buildExamSheet($category),
            ExamMode::Diagnosis => $this->buildDiagnosisSheet($category),
            default => $scope === 'new'
                ? $this->buildNewSheet($user, $category, $size)
                : $this->buildPracticeSheet($user, $category, $size),
        };

        if ($questions->isEmpty()) {
            throw new RuntimeException(__('enums.errors.no_questions'));
        }

        return DB::transaction(function () use ($user, $category, $mode, $questions) {
            $session = ExamSession::create([
                'user_id' => $user->id,
                'driving_category_id' => $category->id,
                'mode' => $mode,
                // Język startu. Dla egzaminu jest wiążący do końca (patrz LocaleLock):
                // zmiana w połowie zmieniłaby warunki pomiaru. Dla nauki i diagnozy
                // to tylko informacja.
                'locale' => app()->getLocale(),
                'status' => ExamStatus::InProgress,
                'total_questions' => $questions->count(),
                'max_score' => (int) $questions->sum('points'),
                // Egzamin: globalny limit 25 min (odliczany w nagłówku) OBOK czasu
                // per-pytanie (faza czytania/odpowiedzi). Nauka i diagnoza — bez limitu.
                'time_limit_seconds' => $mode === ExamMode::Exam ? (int) config('prawko.exam.time_limit_seconds') : null,
                'started_at' => now(),
            ]);

            $position = 1;
            foreach ($questions as $question) {
                $session->answers()->create([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                    'position' => $position++,
                ]);
            }

            return $session;
        });
    }

    /** Zapisuje odpowiedź na jedno pytanie i aktualizuje statystyki powtórek. */
    public function answer(UserAnswer $userAnswer, ?Answer $selected, ?int $timeSpentMs = null, ?Confidence $confidence = null): UserAnswer
    {
        $question = $userAnswer->question()->first();
        $isCorrect = $selected !== null && $selected->is_correct;

        $userAnswer->update([
            'selected_answer_id' => $selected?->id,
            'is_correct' => $isCorrect,
            'confidence' => $confidence,
            'points_awarded' => $isCorrect ? $question->points : 0,
            'time_spent_ms' => $timeSpentMs,
            'answered_at' => now(),
        ]);

        $this->knowledge->record($userAnswer->user_id, $question, $isCorrect, $timeSpentMs, $confidence);

        return $userAnswer;
    }

    /** Kończy sesję: sumuje punkty i wyznacza zaliczenie (dla trybu egzaminu). */
    public function complete(ExamSession $session): ExamSession
    {
        $answers = $session->answers()->get();

        $passed = $session->mode === ExamMode::Exam
            ? (int) $answers->sum('points_awarded') >= (int) config('prawko.exam.pass_threshold')
            : null;

        $session->update([
            'status' => ExamStatus::Completed,
            'correct_count' => $answers->where('is_correct', true)->count(),
            'score' => (int) $answers->sum('points_awarded'),
            'passed' => $passed,
            'completed_at' => now(),
        ]);

        // Diagnoza: z całego wzorca odpowiedzi szacujemy θ_u (jednorazowa, dokładna miara).
        if ($session->mode === ExamMode::Diagnosis) {
            $this->finalizeDiagnosis($session);
        }

        return $session->refresh();
    }

    /**
     * Kończy Diagnozę: estymuje θ_u metodą EAP z całego wzorca odpowiedzi i
     * nadpisuje nią umiejętność użytkownika (dokładniejszą niż wolne dogrywanie
     * online w trakcie sesji). Unieważnia cache silnika, by prognoza od razu
     * odzwierciedlała nowy punkt startowy.
     */
    private function finalizeDiagnosis(ExamSession $session): void
    {
        $responses = $session->answers()
            ->whereNotNull('answered_at')
            ->with('question:id,irt_b')
            ->get()
            ->map(fn (UserAnswer $a) => [
                'b' => (float) ($a->question->irt_b ?? 0),
                'correct' => (bool) $a->is_correct,
            ])
            ->all();

        if (empty($responses)) {
            return;
        }

        [$theta] = $this->ability->estimate($responses);

        if ($user = $session->user()->first()) {
            $user->forceFill(['skill_theta' => round($theta, 6)])->save();
            EngineCache::bump($user->id);
        }
    }

    /** Arkusz egzaminu zgodny z oficjalną strukturą punktową (z config/prawko.php). */
    private function buildExamSheet(DrivingCategory $category): Collection
    {
        $sheet = collect();

        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            $sheet = $sheet->concat(
                Question::query()
                    ->active()
                    ->ofType($type)
                    ->where('points', $points)
                    ->forCategory($category->id)
                    ->inRandomOrder()
                    ->limit($count)
                    ->get()
            );
        }

        return $sheet;
    }

    /** Arkusz Diagnozy: stratyfikowany jak egzamin (z config/prawko.php), suma = 40. */
    private function buildDiagnosisSheet(DrivingCategory $category): Collection
    {
        $sheet = collect();

        foreach (config('prawko.diagnosis.composition') as [$type, $points, $count]) {
            $sheet = $sheet->concat(
                Question::query()
                    ->active()
                    ->ofType($type)
                    ->where('points', $points)
                    ->forCategory($category->id)
                    ->inRandomOrder()
                    ->limit($count)
                    ->get()
            );
        }

        return $sheet;
    }

    /**
     * Zestaw do nauki, w trzech warstwach:
     *   1) odrobina NOWYCH pytań (config: practice.new_ratio) — inaczej kolejka
     *      ryzyka (wysokie p_correct → niska strata) spycha niewidziane na dół
     *      i pokrycie puli stoi w miejscu,
     *   2) POWTÓRKI, których termin FSRS nadszedł (żeby nie zapomnieć),
     *   3) reszta z kolejki ryzyka (najsłabsze/najważniejsze).
     * Rozmiar domyślny z configu albo z „dziennej dawki" planu (parametr $size).
     */
    private function buildPracticeSheet(User $user, DrivingCategory $category, ?int $size = null): Collection
    {
        $size = max(1, min(100, $size ?? (int) config('prawko.practice.default_size')));

        $seenIds = QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->pluck('question_id')->all();

        // 1) Odrobina nowych. Gdy nowych brak, warstwa jest pusta i sesja wygląda
        //    jak dawniej (powtórki + ryzyko).
        $newTarget = (int) round($size * (float) config('prawko.practice.new_ratio'));
        $new = $newTarget > 0
            ? Question::query()->active()->forCategory($category->id)
                ->when($seenIds, fn ($q) => $q->whereNotIn('id', $seenIds))
                ->inRandomOrder()->limit($newTarget)->get()
            : collect();
        $newIds = $new->pluck('id')->all();

        // 2) Powtórki do zrobienia (spaced repetition) — od najdawniej zaległych.
        $roomAfterNew = $size - $new->count();
        $dueIds = $roomAfterNew > 0
            ? QuestionUserStat::query()->where('user_id', $user->id)->due()
                ->orderBy('due_at')->limit($roomAfterNew)->pluck('question_id')->all()
            : [];
        $due = empty($dueIds)
            ? collect()
            : Question::query()->active()->forCategory($category->id)->whereIn('id', $dueIds)->get();

        // 3) Reszta z kolejki ryzyka (z losowaniem, by sesje nie były identyczne),
        //    z pominięciem już wybranych (nowych i powtórek).
        $exclude = array_merge($newIds, $dueIds);
        $remaining = $size - $new->count() - $due->count();
        $fill = collect();
        if ($remaining > 0) {
            $riskIds = array_values(array_diff(
                $this->risk->topQuestionIds($user, $category, $remaining * 4 + count($exclude)),
                $exclude
            ));

            $fill = empty($riskIds)
                ? Question::query()->active()->forCategory($category->id)
                    ->whereNotIn('id', $exclude)->inRandomOrder()->limit($remaining)->get()
                : Question::query()->whereIn('id', collect($riskIds)->shuffle()->take($remaining))->get();
        }

        return $new->concat($due)->concat($fill)->shuffle()->values();
    }

    /**
     * Zestaw „tylko nowe": pytania kategorii, na które użytkownik jeszcze nie
     * odpowiadał (brak stanu FSRS). Domyślny silnik nauki dobiera wg ryzyka +
     * powtórek, przez co nowe pytania z przyzwoitym priorytetem IRT prawie nie
     * wychodzą — ten tryb pozwala świadomie domykać POKRYCIE puli.
     */
    private function buildNewSheet(User $user, DrivingCategory $category, ?int $size = null): Collection
    {
        $size = max(1, min(100, $size ?? (int) config('prawko.practice.default_size')));

        $seenIds = QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->pluck('question_id')->all();

        $sheet = Question::query()->active()->forCategory($category->id)
            ->when($seenIds, fn ($q) => $q->whereNotIn('id', $seenIds))
            ->inRandomOrder()->limit($size)->get();

        // Pusto = całą pulę tej kategorii masz już przerobioną. Osobny komunikat,
        // bo „brak pytań w kategorii" (no_questions) sugerowałby pustą bazę.
        if ($sheet->isEmpty()) {
            throw new RuntimeException(__('enums.errors.no_new_questions'));
        }

        return $sheet;
    }
}
