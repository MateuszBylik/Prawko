<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Szczegółowa historia odpowiedzi — jeden wiersz na (sesja, pytanie).
 * To jest surowe źródło danych dla przyszłych algorytmów analizy postępów
 * (m.in. spaced repetition). user_id jest zdenormalizowany, aby zapytania
 * analityczne per-użytkownik nie wymagały joinu przez sesję.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_answer_id')->nullable()->constrained('answers')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_correct')->nullable();            // null = jeszcze nieudzielona
            $table->unsignedTinyInteger('points_awarded')->default(0);
            $table->unsignedInteger('time_spent_ms')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['exam_session_id', 'position']);
            $table->index(['user_id', 'question_id']);
            $table->index(['user_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
