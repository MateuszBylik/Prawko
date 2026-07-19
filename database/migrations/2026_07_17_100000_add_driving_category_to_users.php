<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategoria prawa jazdy przypięta do konta.
 *
 * Do tej pory wybierało się ją przy każdym starcie sesji, co było bez sensu:
 * nikt nie zdaje raz na B, raz na C. Teraz to cecha konta — pytamy raz, przy
 * pierwszym logowaniu, i zmienia się ją w profilu.
 *
 * `nullOnDelete`, a nie `cascadeOnDelete`: usunięcie kategorii z katalogu nie może
 * kasować kont użytkowników. Brak kategorii oznacza „jeszcze nie wybrano" i wysyła
 * na ekran wyboru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('driving_category_id')->nullable()->after('school_id')
                ->constrained('driving_categories')->nullOnDelete();
        });

        // Istniejące konta uczyły się dotąd na kat. B — przypisujemy ją wprost,
        // żeby nikt po wdrożeniu nie zobaczył nagle ekranu wyboru kategorii.
        $b = DB::table('driving_categories')->where('code', 'B')->value('id');

        if ($b) {
            DB::table('users')->whereNull('driving_category_id')->update(['driving_category_id' => $b]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driving_category_id');
        });
    }
};
