<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dzienna migawka prognozy (Etap 9 — historia szansy zdania). */
#[Fillable(['user_id', 'driving_category_id', 'snapshot_date', 'pass_probability', 'coverage_percent', 'skill_theta', 'answered_count'])]
class ForecastSnapshot extends Model
{
    protected function casts(): array
    {
        return [
            // Jawny format: samo 'date' serializuje się jako 'Y-m-d H:i:s', przez co
            // updateOrCreate nie trafia w istniejący wiersz i próbuje wstawić duplikat.
            'snapshot_date' => 'date:Y-m-d',
            'pass_probability' => 'float',
            'coverage_percent' => 'float',
            'skill_theta' => 'float',
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

    /** Migawki z ostatnich N dni, od najstarszej (kolejność dla wykresu). */
    public function scopeRecent(Builder $query, int $days = 90): Builder
    {
        return $query->where('snapshot_date', '>=', Carbon::today()->subDays($days)->toDateString())
            ->orderBy('snapshot_date');
    }

    public function passPercent(): float
    {
        return round($this->pass_probability * 100, 1);
    }
}
