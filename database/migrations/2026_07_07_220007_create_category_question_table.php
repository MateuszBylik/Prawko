<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot kategoria ↔ pytanie (zakres pytania). Jedno pytanie może obowiązywać
 * dla wielu kategorii prawa jazdy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driving_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            $table->unique(['driving_category_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_question');
    }
};
