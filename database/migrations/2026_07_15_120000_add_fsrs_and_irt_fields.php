<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etap 1 — silnik wiedzy. Rozszerza spaced repetition z SM-2 na FSRS oraz
 * dokłada warstwę IRT/Rasch (prior dla pytań jeszcze nierozwiązanych):
 *
 *  question_user_stats: stability/difficulty/fsrs_state/lapses (FSRS) + avg_time_ms
 *    (średni czas odpowiedzi — do modelu „zdążę w limicie"). Kolumny SM-2 zostają,
 *    ale silnik liczy na polach FSRS.
 *  users.skill_theta:  θ_u — utajona umiejętność użytkownika (skala logit).
 *  questions.irt_b:    b_q — trudność pytania (skala logit); bez danych = 0 (neutralnie),
 *    dostrajana online z odpowiedzi. Prior p = σ(θ_u − b_q).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_user_stats', function (Blueprint $table) {
            $table->decimal('stability', 12, 6)->nullable()->after('repetitions');   // dni
            $table->decimal('difficulty', 8, 6)->nullable()->after('stability');      // FSRS D (1–10)
            $table->unsignedTinyInteger('fsrs_state')->default(0)->after('difficulty'); // 0 new,1 learning,2 review,3 relearning
            $table->unsignedInteger('lapses')->default(0)->after('fsrs_state');
            $table->unsignedInteger('avg_time_ms')->nullable()->after('lapses');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('skill_theta', 8, 6)->default(0)->after('remember_token');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('irt_b', 8, 6)->default(0)->after('difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('question_user_stats', function (Blueprint $table) {
            $table->dropColumn(['stability', 'difficulty', 'fsrs_state', 'lapses', 'avg_time_ms']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('skill_theta');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('irt_b');
        });
    }
};
