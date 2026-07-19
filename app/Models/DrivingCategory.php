<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrivingCategory extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Nazwa kategorii w języku interfejsu.
     *
     * Nazwy to dane słownikowe (kolumna `name` jest po polsku), więc tłumaczenia
     * trzymamy w lang/{locale}/categories.php pod kodem kategorii. Brak wpisu →
     * odbicie na polską nazwę z bazy (nowy kod prawka nie wywali ekranu).
     */
    public function localizedName(): string
    {
        $key = 'categories.'.$this->code;

        return \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $this->name;
    }

    /** Pytania obowiązujące dla tej kategorii (relacja wiele-do-wielu). */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'category_question');
    }

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
