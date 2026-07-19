<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(UserObserver::class)]
#[Fillable(['name', 'email', 'password', 'role', 'school_id', 'driving_category_id', 'locale', 'phone', 'exam_date'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'skill_theta' => 'float', // θ_u — utajona umiejętność (silnik wiedzy)
            'exam_date' => 'date',
            'exam_prompt_snoozed_on' => 'date',
        ];
    }

    // ————————————————— Relacje domenowe —————————————————

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    /** Podejścia do państwowego egzaminu (WORD) — nie egzaminy próbne w apce. */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function questionStats(): HasMany
    {
        return $this->hasMany(QuestionUserStat::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Kategoria, na którą użytkownik zdaje — cecha konta, nie wybór przy sesji. */
    public function drivingCategory(): BelongsTo
    {
        return $this->belongsTo(DrivingCategory::class, 'driving_category_id');
    }

    /** Kursanci przypisani do tego instruktora (panel B2B). */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'instructor_student', 'instructor_id', 'student_id')
            ->withTimestamps();
    }

    /** Instruktorzy tego kursanta. */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'instructor_student', 'student_id', 'instructor_id')
            ->withTimestamps();
    }

    // ————————————————— Pomocnicze —————————————————

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isInstructor(): bool
    {
        return $this->role === UserRole::Instructor;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Czy konto służy do nauki. Administrator wyłącznie zarządza — nie rozwiązuje
     * testów, więc nie ma dla niego ani prognozy, ani planu, ani kategorii.
     */
    public function learns(): bool
    {
        return ! $this->isAdmin();
    }

    /** Czy trzeba jeszcze zapytać o kategorię (pierwsze logowanie). */
    public function needsCategoryChoice(): bool
    {
        return $this->learns() && $this->driving_category_id === null;
    }

    /**
     * Czy pokazać pytanie „jak poszedł egzamin?".
     *
     * Warunki: konto się uczy, ma ustawioną datę egzaminu, ta data jest DZIŚ albo
     * już minęła (egzamin się odbył lub powinien był), i użytkownik nie odłożył
     * pytania na dziś („będę mieć dzisiaj"). Po odpowiedzi datę zerujemy, więc
     * pytanie znika samo.
     */
    public function hasExamDayPrompt(): bool
    {
        if (! $this->learns() || $this->exam_date === null) {
            return false;
        }

        $today = today();

        if ($this->exam_date->gt($today)) {
            return false; // egzamin jeszcze przed nami — to plan, nie podsumowanie
        }

        return $this->exam_prompt_snoozed_on === null
            || $this->exam_prompt_snoozed_on->lt($today);
    }

    /**
     * Trofea — zdane państwowe egzaminy, po jednym (najnowszym) na kategorię.
     * Działa na już wczytanej relacji (lista admina ładuje ją hurtem), więc nie
     * dokłada zapytań w pętli.
     *
     * @return \Illuminate\Support\Collection<int, ExamAttempt>
     */
    public function trophies(): \Illuminate\Support\Collection
    {
        return $this->examAttempts
            ->where('passed', true)
            ->sortByDesc('taken_on')
            ->unique('driving_category_id')
            ->values();
    }
}
