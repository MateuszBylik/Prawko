<?php

namespace Database\Seeders;

use App\Enums\ExamMode;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\School;
use App\Models\User;
use App\Services\ExamService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

/**
 * Dane demonstracyjne: konto kursanta z historią sesji (żeby dashboard nie był
 * pusty), instruktor + szkoła (fundament B2B) oraz operacje na portfelu.
 * Sesje są generowane przez prawdziwy ExamService — więc od razu wypełniają
 * także statystyki spaced repetition.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $exam = app(ExamService::class);
        $wallets = app(WalletService::class);

        // Instruktor + szkoła jazdy (panel B2B — na razie tylko dane).
        $instructor = User::firstOrCreate(
            ['email' => 'instruktor@prawko.test'],
            [
                'name' => 'Anna Instruktor',
                'password' => 'password',
                'role' => UserRole::Instructor,
                'email_verified_at' => now(),
            ],
        );

        $school = School::firstOrCreate(
            ['slug' => 'szkola-jazdy-przyklad'],
            [
                'name' => 'Szkoła Jazdy Przykład',
                'city' => 'Warszawa',
                'nip' => '5252525252',
                'email' => 'kontakt@szkola-przyklad.test',
                'owner_id' => $instructor->id,
            ],
        );
        $instructor->update(['school_id' => $school->id]);

        // Konto demonstracyjne kursanta.
        $student = User::firstOrCreate(
            ['email' => 'kursant@prawko.test'],
            [
                'name' => 'Jan Kursant',
                'password' => 'password',
                'role' => UserRole::Student,
                'email_verified_at' => now(),
            ],
        );
        $student->update(['school_id' => $school->id]);

        // Przypisanie kursanta do instruktora (relacja B2B).
        $instructor->students()->syncWithoutDetaching([$student->id]);

        // Dodatkowe doładowanie portfela — pokazuje historię operacji.
        if ($student->wallet) {
            $wallets->credit($student->wallet, 50, TransactionType::Topup, 'Doładowanie startowe (demo)');
        }

        // Historia sesji, by pulpit zawierał realne statystyki.
        $categoryB = DrivingCategory::where('code', 'B')->first();
        if (! $categoryB) {
            return;
        }

        foreach ([ExamMode::Practice, ExamMode::Practice, ExamMode::Exam] as $mode) {
            $session = $exam->start($student, $categoryB, $mode);

            foreach ($session->answers()->with('question.answers')->get() as $userAnswer) {
                $options = $userAnswer->question->answers;
                $correct = $options->firstWhere('is_correct', true);

                // ~78% trafień — realistyczny wynik osoby uczącej się.
                $pick = random_int(1, 100) <= 78
                    ? $correct
                    : $options->where('is_correct', false)->random();

                $exam->answer($userAnswer, $pick, random_int(1500, 9000));
            }

            $exam->complete($session);
        }
    }
}
