<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sesja rozwiązywania pytań — zarówno nauka, jak i symulacja egzaminu.
 * Pola zbiorcze (correct_count, score, ...) są denormalizowane dla szybkich
 * statystyk na dashboardzie bez liczenia po całej historii odpowiedzi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driving_category_id')->constrained();
            $table->string('mode')->index();                                // App\Enums\ExamMode
            $table->string('status')->default('in_progress')->index();      // App\Enums\ExamStatus
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('score')->nullable();              // punkty zdobyte
            $table->unsignedSmallInteger('max_score')->nullable();          // punkty możliwe do zdobycia
            $table->boolean('passed')->nullable();
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
