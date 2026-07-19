<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategorie prawa jazdy (A, A1, B, C, D, T, ...).
 * Pytania są przypisane do kategorii relacją wiele-do-wielu, bo jedno pytanie
 * (np. podstawowe o zasadach ruchu) obowiązuje dla wielu kategorii.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driving_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driving_categories');
    }
};
