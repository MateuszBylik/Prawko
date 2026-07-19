<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oficjalna baza pytań (XLSX) niesie więcej niż samo pytanie po polsku:
 * - tłumaczenia treści i odpowiedzi na EN / DE / UA,
 * - nazwy plików wideo w Polskim Języku Migowym (PJM).
 * Trzymamy je w jednej kolumnie JSON `translations`, żeby nic nie zgubić,
 * bez rozdymania schematu — rdzeń nauki i tak korzysta z pól PL.
 *
 * `source` odróżnia pytania z arkusza „katalog" (published) od tych
 * „W trakcie weryfikacji" (review) — te drugie importujemy jako is_active=false.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('explanation');
            $table->string('source', 32)->default('catalog')->index()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['translations', 'source']);
        });
    }
};
