<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Pulpit administratora — ogólny stan serwisu.
 *
 * Świadomie NIE liczy tu niczego z silnika (prognozy, kolejki ryzyka): to Monte Carlo
 * po całej puli na użytkownika, więc dla listy kont byłoby to kilkanaście sekund
 * pracy na jedno wejście. Te liczby są od agregatów, nie od modelowania.
 */
class DashboardController extends Controller
{
    /** Ile dni pokazuje wykres aktywności. */
    private const ACTIVITY_DAYS = 14;

    public function index(Request $request): View
    {
        $today = CarbonImmutable::now()->startOfDay();
        $completed = ExamStatus::Completed->value;

        $examQuery = ExamSession::where('status', $completed)->where('mode', ExamMode::Exam->value);
        $examsTaken = (clone $examQuery)->count();
        $examsPassed = (clone $examQuery)->where('passed', true)->count();

        return view('admin.dashboard', [
            'users' => [
                'total' => User::count(),
                'students' => User::where('role', UserRole::Student->value)->count(),
                'admins' => User::where('role', UserRole::Admin->value)->count(),
                // „Aktywny" = odpowiedział na pytanie. Liczymy unikalne konta, nie odpowiedzi.
                'active_7d' => $this->activeUsersSince($today->subDays(6)),
                'active_today' => $this->activeUsersSince($today),
            ],
            'answers' => [
                'total' => UserAnswer::whereNotNull('answered_at')->count(),
                'today' => UserAnswer::whereNotNull('answered_at')->where('answered_at', '>=', $today)->count(),
                'week' => UserAnswer::whereNotNull('answered_at')->where('answered_at', '>=', $today->subDays(6))->count(),
            ],
            'sessions' => [
                'completed' => ExamSession::where('status', $completed)->count(),
                'in_progress' => ExamSession::where('status', ExamStatus::InProgress->value)->count(),
                'exams_taken' => $examsTaken,
                'exams_passed' => $examsPassed,
                'pass_rate' => $examsTaken > 0 ? (int) round($examsPassed / $examsTaken * 100) : null,
            ],
            'base' => [
                'total' => Question::count(),
                'active' => Question::active()->count(),
                'video' => Question::active()->where('media_type', 'video')->count(),
                'explained' => Question::whereNotNull('explanation')->count(),
                'disputed' => count(config('prawko.disputed', [])),
                'categories' => \App\Models\DrivingCategory::active()->count(),
            ],
            'activity' => $this->activity($today),
            'recentSessions' => ExamSession::with(['user:id,name,email', 'category:id,code'])
                ->where('status', $completed)
                ->latest('completed_at')->limit(8)->get(),
        ]);
    }

    private function activeUsersSince(CarbonImmutable $since): int
    {
        return UserAnswer::whereNotNull('answered_at')
            ->where('answered_at', '>=', $since)
            ->distinct()->count('user_id');
    }

    /**
     * Odpowiedzi na dzień za ostatnie N dni. Jedno grupowane zapytanie, a dni bez
     * aktywności dopełniamy zerami — inaczej wykres skłamałby, ściskając przerwy
     * i sugerując ciągłą naukę.
     *
     * @return array<int, array{date: CarbonImmutable, count: int}>
     */
    private function activity(CarbonImmutable $today): array
    {
        $from = $today->subDays(self::ACTIVITY_DAYS - 1);

        $counts = UserAnswer::query()
            ->whereNotNull('answered_at')
            ->where('answered_at', '>=', $from)
            ->selectRaw('DATE(answered_at) as day, COUNT(*) as n')
            ->groupBy('day')
            ->pluck('n', 'day');

        $out = [];
        for ($i = 0; $i < self::ACTIVITY_DAYS; $i++) {
            $day = $from->addDays($i);
            $out[] = [
                'date' => $day,
                'count' => (int) ($counts[$day->toDateString()] ?? 0),
            ];
        }

        return $out;
    }
}
