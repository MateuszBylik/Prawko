<?php

namespace App\Models;

use App\Enums\Confidence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pojedyncza odpowiedź w sesji. Rdzeń historii odpowiedzi wykorzystywanej
 * później przez algorytmy analizy postępów (spaced repetition, słabe punkty).
 */
class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id', 'user_id', 'question_id', 'selected_answer_id',
        'position', 'is_correct', 'confidence', 'points_awarded', 'time_spent_ms', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'confidence' => Confidence::class,
            'answered_at' => 'datetime',
            'points_awarded' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedAnswer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'selected_answer_id');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
