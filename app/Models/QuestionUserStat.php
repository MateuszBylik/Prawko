<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zagregowany stan wiedzy użytkownika o danym pytaniu (spaced repetition, SM-2).
 * Aktualizowany przez App\Services\SpacedRepetitionService po każdej odpowiedzi.
 */
class QuestionUserStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'question_id', 'times_seen', 'times_correct', 'times_incorrect',
        'streak', 'ease_factor', 'interval_days', 'repetitions', 'last_reviewed_at', 'due_at',
        'stability', 'difficulty', 'fsrs_state', 'lapses', 'avg_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'ease_factor' => 'float',
            'times_seen' => 'integer',
            'times_correct' => 'integer',
            'times_incorrect' => 'integer',
            'streak' => 'integer',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'last_reviewed_at' => 'datetime',
            'due_at' => 'datetime',
            'stability' => 'float',
            'difficulty' => 'float',
            'fsrs_state' => 'integer',
            'lapses' => 'integer',
            'avg_time_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** Pytania, których termin powtórki już nadszedł. */
    public function scopeDue(Builder $query, ?\DateTimeInterface $at = null): Builder
    {
        return $query->whereNotNull('due_at')->where('due_at', '<=', $at ?? now());
    }

    public function accuracy(): float
    {
        return $this->times_seen > 0
            ? round($this->times_correct / $this->times_seen * 100, 1)
            : 0.0;
    }
}
