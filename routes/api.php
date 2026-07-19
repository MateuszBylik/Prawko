<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ExamSessionController;
use App\Http\Controllers\Api\V1\QuestionController;
use Illuminate\Support\Facades\Route;

/*
 * REST API v1 — fundament pod aplikację mobilną (Flutter).
 * Uwierzytelnianie tokenowe przez Laravel Sanctum. Wersjonowanie w prefiksie URL
 * (/api/v1) pozwala rozwijać API bez psucia starszych klientów.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Publiczne
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // Chronione tokenem
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');

        Route::get('/sessions', [ExamSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [ExamSessionController::class, 'store'])->name('sessions.store');
        Route::get('/sessions/{session}', [ExamSessionController::class, 'show'])->name('sessions.show');
    });
});
