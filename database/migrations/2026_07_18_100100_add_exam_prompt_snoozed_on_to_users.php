<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * „Będę mieć dzisiaj" — użytkownik odkłada pytanie o wynik na jutro. Trzymamy DZIEŃ
 * odłożenia (nie boolean), bo drzemka ma wygasać: nazajutrz egzamin jest już „za
 * nami" i pytamy ponownie, jak poszło.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('exam_prompt_snoozed_on')->nullable()->after('exam_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('exam_prompt_snoozed_on');
        });
    }
};
