<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Podejścia do PAŃSTWOWEGO egzaminu — to, jak poszło w WORD, a nie egzamin próbny
 * w aplikacji (te siedzą w `exam_sessions`). Zdane podejście to trofeum kategorii.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Kategoria może zniknąć ze słownika — trofeum zostaje, ale bez odnośnika.
            $table->foreignId('driving_category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('passed');
            $table->date('taken_on'); // dzień egzaminu (zwykle = zaplanowana data)
            $table->timestamps();

            $table->index(['user_id', 'passed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
