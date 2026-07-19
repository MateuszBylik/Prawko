<?php

namespace App\Services;

use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use App\Models\UserAnswer;
use App\Support\ExamForecast;
use App\Support\StudyPlan;
use Carbon\CarbonImmutable;

/**
 * Plan nauki pod datę egzaminu (Etap 8): z daty, pokrycia puli i terminów FSRS
 * wyznacza dzienną dawkę (nowe pytania + powtórki) i status („na dobrej drodze"
 * vs „przyspiesz"), by dojść do docelowej szansy zdania na dzień egzaminu.
 */
class StudyPlanService
{
    public function build(User $user, DrivingCategory $category, ExamForecast $forecast): StudyPlan
    {
        $target = (float) config('prawko.plan.target_pass');
        $maxSession = (int) config('prawko.plan.max_session');

        $examDate = $user->exam_date ? CarbonImmutable::parse($user->exam_date)->startOfDay() : null;
        $today = CarbonImmutable::now()->startOfDay();
        $daysLeft = $examDate ? (int) $today->diffInDays($examDate, false) : 0;

        // Pokrycie puli: ile pytań kategorii ma już realny stan FSRS.
        $total = Question::query()->active()->forCategory($category->id)->count();
        $seen = min($total, QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->count());
        $unseen = max(0, $total - $seen);

        // Powtórki, których termin już nadszedł.
        $dueToday = QuestionUserStat::query()->where('user_id', $user->id)->due()->count();

        // Tempo z ostatnich 7 dni (odpowiedzi/dzień), licząc dzisiaj jako jeden z nich.
        //
        // Dzielimy przez REALNIE przepracowane dni, nie przez sztywne 7. Dzielenie
        // przez 7 zakłada, że uczysz się od tygodnia — komu zaczął dwa dni temu,
        // zaniżało tempo 3,5× (487 odpowiedzi w 2 dni pokazywało 69,6/dzień zamiast
        // 243,5). To nie była kosmetyka: `status()` porównuje tempo z celem, więc
        // realnie nadrabiający użytkownik dostawał „Trzeba przyspieszyć".
        $windowStart = $today->subDays(6);
        $recentAnswers = UserAnswer::query()
            ->where('user_id', $user->id)
            ->whereNotNull('answered_at')
            ->where('answered_at', '>=', $windowStart)
            ->count();

        $firstAnswerAt = UserAnswer::query()
            ->where('user_id', $user->id)
            ->whereNotNull('answered_at')
            ->min('answered_at');

        $daysLearning = $firstAnswerAt
            ? CarbonImmutable::parse($firstAnswerAt)->startOfDay()->diffInDays($today) + 1
            : 1;
        $paceDays = max(1, min(7, (int) $daysLearning));
        $recentPace = $recentAnswers / $paceDays;

        // Nowe pytania odkryte DZIŚ. Wiersz stanu FSRS powstaje przy pierwszej
        // odpowiedzi na dane pytanie, więc jego `created_at` to moment pierwszego
        // spotkania — dokładnie to, co chcemy policzyć.
        $newDoneToday = QuestionUserStat::query()
            ->where('user_id', $user->id)
            ->whereNotNull('stability')->where('stability', '>', 0)
            ->where('created_at', '>=', $today)
            ->count();

        // Cel na dziś liczymy z zaległości sprzed dzisiejszej nauki, a nie z bieżącej.
        //
        // Inaczej licznik mierzy co innego, niż głosi etykieta: `ceil($unseen / $daysLeft)`
        // to ŚREDNIE TEMPO na dzień, więc każde zrobione pytanie zdejmuje z niego tylko
        // 1/$daysLeft. Przy 7 dniach do egzaminu 233 przerobione pytania obniżały „Dziś"
        // z 283 na 249 — praca całego dnia wyglądała jak brak postępu.
        $unseenAtDayStart = $unseen + $newDoneToday;
        $newPerDay = $daysLeft > 0 ? (int) ceil($unseenAtDayStart / $daysLeft) : $unseenAtDayStart;
        $newRemaining = max(0, $newPerDay - $newDoneToday);

        // Powtórki nie potrzebują takiej korekty — zrobiona powtórka dostaje nowy
        // termin FSRS i sama znika z `due`.
        $dailyDose = $newRemaining + $dueToday;
        $sessionSize = max(1, min($maxSession, $dailyDose > 0 ? $dailyDose : $maxSession));

        return new StudyPlan(
            examDate: $examDate,
            daysLeft: max(0, $daysLeft),
            passProbability: $forecast->passProbability,
            targetPass: $target,
            total: $total,
            seen: $seen,
            unseen: $unseen,
            dueToday: $dueToday,
            newPerDay: $newPerDay,
            newDoneToday: $newDoneToday,
            newRemaining: $newRemaining,
            dailyDose: $dailyDose,
            sessionSize: $sessionSize,
            recentPace: round($recentPace, 1),
            paceDays: $paceDays,
            status: $this->status($examDate, $daysLeft, $forecast->passProbability, $target, $unseen, $newPerDay, $recentPace),
        );
    }

    private function status(?CarbonImmutable $examDate, int $daysLeft, float $pass, float $target, int $unseen, int $newPerDay, float $recentPace): string
    {
        if (! $examDate) {
            return 'no_date';
        }
        if ($daysLeft < 0) {
            return 'past';
        }
        if ($pass >= $target) {
            return 'ready';
        }
        if ($unseen === 0) {
            return 'on_track'; // pokrycie zrobione — powtórki dokończą resztę
        }

        // Czy rzeczywiste tempo nadąża za wymaganym (z lekką tolerancją)?
        return $recentPace + 1e-9 >= $newPerDay * 0.85 ? 'on_track' : 'behind';
    }
}
