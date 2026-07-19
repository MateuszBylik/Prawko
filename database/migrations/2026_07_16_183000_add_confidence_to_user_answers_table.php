<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deklarowana pewność odpowiedzi („wiem" / „strzelam") — zbierana tylko w trybie
 * nauki. Daje silnikowi sygnał, którego dotąd nie miał: poprawna odpowiedź ze
 * strzału to szczęście, nie wiedza, i nie powinna podbijać stabilności FSRS tak
 * samo jak realne przypomnienie.
 *
 * Nullable, bo: (1) historyczne odpowiedzi jej nie mają, (2) egzamin próbny musi
 * zostać 1:1 z arkuszem PORD, a diagnoza to kalibrowany instrument pomiaru θ —
 * w obu pewności nie pytamy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->string('confidence', 8)->nullable()->after('is_correct');

            // Zapytania kalibracyjne filtrują po (użytkownik, pewność).
            $table->index(['user_id', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::table('user_answers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'confidence']);
            $table->dropColumn('confidence');
        });
    }
};
