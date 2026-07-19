<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Przypisania instruktor ↔ kursant (wiele-do-wielu). Dzięki temu instruktor
 * widzi w panelu B2B postępy swoich kursantów, a kursant może mieć kilku
 * instruktorów (np. teoria/praktyka).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['instructor_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_student');
    }
};
