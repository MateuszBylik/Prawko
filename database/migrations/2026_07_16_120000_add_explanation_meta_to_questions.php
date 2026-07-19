<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadane wyjaśnień pytań. Kolumna `explanation` (treść) już istnieje — tu tylko
 * dopisujemy skąd pochodzi (gov/ai), jakim modelem wygenerowana i kiedy, żeby móc
 * odróżnić wyjaśnienia AI od źródłowych i w razie potrzeby je regenerować.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('explanation_source', 16)->nullable()->after('explanation');
            $table->string('explanation_model', 64)->nullable()->after('explanation_source');
            $table->timestamp('explanation_generated_at')->nullable()->after('explanation_model');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['explanation_source', 'explanation_model', 'explanation_generated_at']);
        });
    }
};
