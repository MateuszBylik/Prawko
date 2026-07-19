<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warianty odpowiedzi. Model jest jednolity dla obu typów pytań:
 *  - podstawowe  → dwa wiersze: „Tak" (T) i „Nie" (N),
 *  - specjalist. → trzy wiersze: A / B / C.
 * Dokładnie jeden wariant ma is_correct = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 4)->nullable();     // T/N lub A/B/C
            $table->text('text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
