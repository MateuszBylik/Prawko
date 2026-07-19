<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Szkoła jazdy — fundament panelu B2B. Na razie bez UI; model i relacje
 * są gotowe pod przyszły panel instruktora.
 */
class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'nip', 'city', 'email', 'phone', 'owner_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Instructor->value);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Student->value);
    }
}
