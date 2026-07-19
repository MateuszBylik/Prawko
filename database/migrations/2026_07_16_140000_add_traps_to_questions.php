<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tagi pułapek (App\Enums\TrapType) wykryte w treści pytania — tablica JSON,
 * np. ["negation","number"]. Wypełniane komendą prawko:tag-traps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->json('traps')->nullable()->after('explanation_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('traps');
        });
    }
};
