<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pary „mylące": dla pytania trzymamy jego najbardziej podobne pytania (trigramy).
 * `difference` to opcjonalne wyjaśnienie AI, czym się różnią (generowane leniwie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_similars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('similar_question_id')->constrained('questions')->cascadeOnDelete();
            $table->decimal('score', 6, 4); // podobieństwo Jaccarda 0..1
            $table->text('difference')->nullable();
            $table->string('difference_model', 64)->nullable();
            $table->timestamp('difference_generated_at')->nullable();

            $table->unique(['question_id', 'similar_question_id']);
            $table->index(['question_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_similars');
    }
};
