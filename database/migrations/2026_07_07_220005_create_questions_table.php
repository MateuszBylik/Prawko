<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank pytań. Obsługuje oba typy egzaminacyjne (podstawowe TAK/NIE oraz
 * specjalistyczne A/B/C), punktację 1/2/3, materiał multimedialny
 * (zdjęcie / wideo) oraz wyjaśnienie pokazywane w trybie nauki.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();   // numer z oficjalnej bazy pytań
            $table->string('type')->index();                        // App\Enums\QuestionType
            $table->unsignedTinyInteger('points')->default(1);      // waga punktowa: 1 / 2 / 3
            $table->text('text');
            $table->text('explanation')->nullable();
            $table->string('media_type')->default('none');          // App\Enums\MediaType
            $table->string('media_path')->nullable();
            $table->unsignedSmallInteger('difficulty')->nullable(); // rezerwa pod analitykę
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
