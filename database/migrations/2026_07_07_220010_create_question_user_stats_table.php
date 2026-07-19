<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zagregowany stan wiedzy użytkownika o danym pytaniu — fundament pod
 * spaced repetition (algorytm SM-2). Aktualizowany po każdej odpowiedzi.
 * due_at wyznacza termin kolejnej powtórki, ease_factor/interval_days/repetitions
 * to parametry SM-2, streak to seria poprawnych odpowiedzi z rzędu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('times_seen')->default(0);
            $table->unsignedInteger('times_correct')->default(0);
            $table->unsignedInteger('times_incorrect')->default(0);
            $table->integer('streak')->default(0);
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_user_stats');
    }
};
