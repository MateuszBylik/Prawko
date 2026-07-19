<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/** Panel administratora: podgląd statystyk użytkowników i zakładanie kont. */
class UserController extends Controller
{
    /** Lista użytkowników z zagregowanymi statystykami nauki. */
    public function index(): View
    {
        $completed = ExamStatus::Completed->value;

        $users = User::query()
            ->select('users.*')
            // Podzapytania zamiast N+1 — jedno zapytanie na całą tabelę.
            ->selectSub(
                ExamSession::selectRaw('count(*)')->whereColumn('user_id', 'users.id')->where('status', $completed),
                'sessions_done'
            )
            ->selectSub(
                ExamSession::selectRaw('count(*)')->whereColumn('user_id', 'users.id')
                    ->where('status', $completed)->where('mode', ExamMode::Exam->value),
                'exams_taken'
            )
            ->selectSub(
                ExamSession::selectRaw('count(*)')->whereColumn('user_id', 'users.id')
                    ->where('status', $completed)->where('mode', ExamMode::Exam->value)->where('passed', true),
                'exams_passed'
            )
            ->selectSub(
                ExamSession::selectRaw('avg(score / max_score * 100)')->whereColumn('user_id', 'users.id')
                    ->where('status', $completed)->where('max_score', '>', 0),
                'avg_percent'
            )
            ->selectSub(
                UserAnswer::selectRaw('count(*)')->whereColumn('user_id', 'users.id')->whereNotNull('answered_at'),
                'answers_count'
            )
            ->selectSub(
                UserAnswer::selectRaw('max(answered_at)')->whereColumn('user_id', 'users.id'),
                'last_active_at'
            )
            ->selectSub(
                DB::table('question_user_stats')->selectRaw('count(*)')
                    ->whereColumn('user_id', 'users.id')->where('streak', '>=', 2),
                'mastered'
            )
            // Trofea: zdane państwowe egzaminy. Ładujemy hurtem, żeby lista mogła
            // pokazać plakietkę kategorii bez zapytania na każdy wiersz.
            ->with(['examAttempts' => fn ($q) => $q->where('passed', true)->with('category:id,code,name')])
            ->orderByDesc('last_active_at')
            ->orderBy('id')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'totals' => [
                'users' => $users->count(),
                'answers' => (int) $users->sum('answers_count'),
                'sessions' => (int) $users->sum('sessions_done'),
                'active_7d' => $users->filter(
                    fn (User $u) => $u->last_active_at && $u->last_active_at >= now()->subDays(7)
                )->count(),
            ],
        ]);
    }

    /** Formularz zakładania konta. */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => UserRole::cases(),
            'categories' => DrivingCategory::active()->orderBy('sort_order')->get(),
        ]);
    }

    /** Zapisuje nowe konto (od razu z potwierdzonym adresem — zakłada je admin). */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        // `email_verified_at` jest poza #[Fillable], więc mass assignment by je pominął,
        // a konto utknęłoby na middlewarze `verified`.
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.users.index')
            ->with('success', __('app.admin.user_created', ['email' => $user->email, 'role' => $user->role->label()]));
    }
}
