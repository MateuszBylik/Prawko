<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CategoryChoiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamDayController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\ExplanationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LossBreakdownController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudyPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    // Admin nie ma pulpitu nauki — kierujemy go wprost do panelu, zamiast odbijać
    // go przez /dashboard i middleware (łańcuch przekierowań widać w pasku adresu).
    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard');
})->name('welcome');

// Zmiana języka. Dotyczy na razie tylko strony głównej — reszta aplikacji jest po
// polsku (patrz config/locales.php), więc wracamy tam, skąd przełącznik jest widoczny.
Route::get('/jezyk/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// Wybór kategorii przy pierwszym logowaniu. CELOWO poza grupą `category` —
// inaczej middleware odsyłałby na ten ekran w kółko. Także poza `no-admin`:
// admin i tak tu nie trafi, bo `needsCategoryChoice()` jest dla niego fałszem.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/kategoria', [CategoryChoiceController::class, 'show'])->name('category.show');
    Route::post('/kategoria', [CategoryChoiceController::class, 'store'])->name('category.store');
});

// Baza pytań — dostępna także dla admina (musi móc weryfikować pytania).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pytania', [QuestionController::class, 'index'])->name('questions.index');
    Route::get('/pytania/{question}', [QuestionController::class, 'show'])
        ->whereNumber('question')->name('questions.show');

    // Wyjaśnienie pytania (AI) — leniwie generowane i zapisywane.
    Route::post('/pytania/{question}/wyjasnienie', [ExplanationController::class, 'show'])
        ->whereNumber('question')->name('questions.explain');

    // Różnica w parze mylącej (AI, Etap 6) — leniwie generowana.
    Route::post('/pary/{pair}/roznica', [ExplanationController::class, 'difference'])
        ->whereNumber('pair')->name('twins.difference');
});

// Nauka: wymaga wybranej kategorii i konta, które się uczy (nie admina).
Route::middleware(['auth', 'verified', 'no-admin', 'category'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Plan nauki: zapis/usunięcie daty egzaminu.
    Route::post('/plan', [StudyPlanController::class, 'update'])->name('plan.update');

    // Dzień egzaminu: „jak poszło?" (zdałem / nie zdałem / będę dziś / zmiana terminu).
    Route::post('/egzamin-panstwowy', [ExamDayController::class, 'respond'])->name('exam.day.respond');

    // „Gdzie tracisz punkty" — rozbicie prognozy na warstwy, pułapki i kalibrację.
    Route::get('/straty', [LossBreakdownController::class, 'index'])->name('losses.index');

    // Sesje nauki i symulacji egzaminu.
    Route::post('/sesje', [ExamSessionController::class, 'store'])->name('sessions.store');
    Route::get('/sesje/{session}', [ExamSessionController::class, 'show'])->name('sessions.show');
    Route::get('/sesje/{session}/pytania/{position}', [ExamSessionController::class, 'question'])
        ->whereNumber('position')->name('sessions.question');
    Route::post('/sesje/{session}/pytania/{position}', [ExamSessionController::class, 'answer'])
        ->whereNumber('position')->name('sessions.answer');
    Route::post('/sesje/{session}/zakoncz', [ExamSessionController::class, 'complete'])->name('sessions.complete');
    Route::get('/sesje/{session}/wynik', [ExamSessionController::class, 'result'])->name('sessions.result');
});

// Panel administratora: statystyki użytkowników i zakładanie kont.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/uzytkownicy', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/uzytkownicy/nowy', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/uzytkownicy', [AdminUserController::class, 'store'])->name('users.store');

    // Wejście w podgląd cudzego konta.
    Route::post('/uzytkownicy/{user}/podglad', [ImpersonationController::class, 'store'])
        ->whereNumber('user')->name('users.impersonate');
});

// Wyjście z podglądu — CELOWO poza grupą `admin`: w trakcie podglądu jesteś
// zalogowany jako zwykły użytkownik, więc middleware `admin` zatrzasnąłby
// jedyne drzwi wyjściowe.
Route::middleware('auth')->post('/podglad/koniec', [ImpersonationController::class, 'destroy'])
    ->name('impersonate.stop');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Serwowanie materiałów (zdjęcia/wideo) spoza public/. Nazwa pliku bez slasha.
    Route::get('/media/{name}', [MediaController::class, 'show'])
        ->where('name', '[^/]+')->name('media.show');
});

require __DIR__.'/auth.php';
