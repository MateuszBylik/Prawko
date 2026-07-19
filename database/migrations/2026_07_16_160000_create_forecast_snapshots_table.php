<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dzienne migawki prognozy — źródło wykresu „szansa zdania w czasie".
 * Jeden wiersz na użytkownika, kategorię i dzień (nadpisywany w ciągu dnia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driving_category_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');

            $table->decimal('pass_probability', 6, 5);   // 0..1
            $table->decimal('coverage_percent', 5, 2);   // 0..100 — ile puli zobaczone
            $table->decimal('skill_theta', 9, 6)->nullable();
            $table->unsignedInteger('answered_count')->default(0);

            $table->timestamps();

            // Jedna migawka na dzień; indeks obsługuje też pobieranie serii.
            $table->unique(['user_id', 'driving_category_id', 'snapshot_date'], 'forecast_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_snapshots');
    }
};
