<?php

namespace App\Models;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'driving_category_id', 'mode', 'locale', 'status',
        'total_questions', 'correct_count', 'score', 'max_score',
        'passed', 'time_limit_seconds', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => ExamMode::class,
            'status' => ExamStatus::class,
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrivingCategory::class, 'driving_category_id');
    }

    /** Odpowiedzi w tej sesji (uporządkowane wg pozycji pytania). */
    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class)->orderBy('position');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ExamStatus::Completed->value);
    }

    public function isExam(): bool
    {
        return $this->mode === ExamMode::Exam;
    }

    public function isCompleted(): bool
    {
        return $this->status === ExamStatus::Completed;
    }

    /** Procentowy wynik sesji (0–100). */
    public function scorePercent(): int
    {
        if (! $this->max_score) {
            return 0;
        }

        return (int) round(($this->score ?? 0) / $this->max_score * 100);
    }
}
