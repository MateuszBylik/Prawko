<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedno podejście do państwowego egzaminu teoretycznego (WORD) — zdane lub nie.
 * Zdane podejście jest podstawą „trofeum" kategorii w profilu i panelu admina.
 */
class ExamAttempt extends Model
{
    protected $fillable = ['user_id', 'driving_category_id', 'passed', 'taken_on'];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'taken_on' => 'date',
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
}
