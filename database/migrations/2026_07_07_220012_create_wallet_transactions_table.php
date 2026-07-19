<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Księga operacji portfela (pełny audyt). Każda zmiana salda to jeden wiersz;
 * balance_after zapisuje saldo po operacji, dzięki czemu historia jest
 * odtwarzalna i weryfikowalna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();        // App\Enums\TransactionType
            $table->integer('amount');              // dodatnia = wpływ, ujemna = wydatek
            $table->integer('balance_after');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
