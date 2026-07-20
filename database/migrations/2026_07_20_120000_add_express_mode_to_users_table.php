<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tryb ekspresowy jako „lepki" przełącznik konta (końcówka przed egzaminem).
     * Włączony zmienia dawkę na sesje z pytań jeszcze nie utrwalonych/poprawnych.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('express_mode')->default(false)->after('exam_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('express_mode');
        });
    }
};
