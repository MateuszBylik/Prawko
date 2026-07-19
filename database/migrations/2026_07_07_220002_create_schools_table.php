<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Szkoły jazdy (fundament panelu B2B). Instruktorzy i kursanci należą do szkoły
 * przez users.school_id, a przypisania instruktor↔kursant trzyma tabela pivot
 * instructor_student.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('nip', 15)->nullable();
            $table->string('city')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
