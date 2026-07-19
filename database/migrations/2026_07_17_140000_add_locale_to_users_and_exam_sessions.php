<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Język interfejsu w dwóch miejscach, bo odpowiadają na dwa różne pytania.
 *
 * users.locale — „w jakim języku chcę korzystać z aplikacji". W bazie, a nie
 * w localStorage jak motyw: język to cecha OSOBY (kto czyta po ukraińsku, czyta
 * tak samo na telefonie i na komputerze), a motyw to cecha urządzenia i pory dnia.
 * Null = jeszcze nie wybrano → bierzemy domyślny z configu.
 *
 * exam_sessions.locale — „w jakim języku ten egzamin został rozpoczęty". Egzamin
 * jest pomiarem: przełączenie języka w połowie zmienia warunki (inne sformułowania,
 * inny czas czytania) i wynik przestaje być porównywalny. Zapisujemy język na starcie
 * i trzymamy go do końca. Nauka i diagnoza takiej blokady nie mają.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('driving_category_id');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
