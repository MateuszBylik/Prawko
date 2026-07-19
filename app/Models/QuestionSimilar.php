<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Skojarzenie „pytanie → podobne pytanie" (para myląca) wraz z wynikiem
 * podobieństwa i opcjonalnym wyjaśnieniem różnicy (AI).
 */
class QuestionSimilar extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id', 'similar_question_id', 'score',
        'difference', 'difference_model', 'difference_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'difference_generated_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function similarQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'similar_question_id');
    }

    /** Podobieństwo jako procent (do wyświetlenia). */
    public function scorePercent(): int
    {
        return (int) round($this->score * 100);
    }
}
