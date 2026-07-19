<?php

namespace Tests\Feature;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blokada języka na czas egzaminu.
 *
 * Egzamin jest POMIAREM: przełączenie języka w połowie zmienia warunki (inne
 * sformułowania, inny czas czytania) i wynik przestaje być porównywalny
 * z poprzednimi podejściami oraz z prognozą. Nauka i diagnoza blokady nie mają.
 */
class LocaleLockTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Student]);
    }

    private function makeSession(User $user, ExamMode $mode, string $locale, ExamStatus $status = ExamStatus::InProgress): ExamSession
    {
        return ExamSession::create([
            'user_id' => $user->id,
            'driving_category_id' => $user->driving_category_id,
            'mode' => $mode, 'locale' => $locale, 'status' => $status,
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(),
        ]);
    }

    public function test_egzamin_w_toku_blokuje_zmiane_jezyka(): void
    {
        $user = $this->user();
        $user->forceFill(['locale' => 'pl'])->save();
        $this->makeSession($user, ExamMode::Exam, 'pl');

        $this->actingAs($user)->get(route('locale.update', 'en'))->assertSessionHas('error');

        $this->assertSame('pl', $user->fresh()->locale, 'Język konta nie może się zmienić w trakcie egzaminu.');
    }

    /** Blokada musi trzymać język egzaminu nawet wbrew ustawieniu konta. */
    public function test_egzamin_wymusza_swoj_jezyk_ponad_ustawieniem_konta(): void
    {
        $user = $this->user();
        $user->forceFill(['locale' => 'en'])->save();
        $this->makeSession($user, ExamMode::Exam, 'de');

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame('de', app()->getLocale());
    }

    public function test_nauka_w_toku_nie_blokuje_jezyka(): void
    {
        $user = $this->user();
        $this->makeSession($user, ExamMode::Practice, 'pl');

        $this->actingAs($user)->get(route('locale.update', 'en'))->assertSessionMissing('error');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_zakonczony_egzamin_juz_nie_blokuje(): void
    {
        $user = $this->user();
        $this->makeSession($user, ExamMode::Exam, 'pl', ExamStatus::Completed);

        $this->actingAs($user)->get(route('locale.update', 'uk'))->assertSessionMissing('error');

        $this->assertSame('uk', $user->fresh()->locale);
    }

    /** Cudzy egzamin nie może zamrozić języka niepowiązanemu użytkownikowi. */
    public function test_egzamin_innego_uzytkownika_nie_blokuje(): void
    {
        $this->makeSession($this->user(), ExamMode::Exam, 'pl');
        $other = $this->user();

        $this->actingAs($other)->get(route('locale.update', 'de'))->assertSessionMissing('error');

        $this->assertSame('de', $other->fresh()->locale);
    }

    public function test_start_egzaminu_zapisuje_biezacy_jezyk(): void
    {
        $user = $this->user();
        $user->forceFill(['locale' => 'uk'])->save();

        $cat = DrivingCategory::find($user->driving_category_id);
        foreach (config('prawko.exam.composition') as [$type, $points, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $q = Question::create([
                    'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
                    'type' => $type->value, 'points' => $points, 'text' => 'P?',
                    'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
                ]);
                $q->categories()->attach($cat->id);
                Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);
            }
        }

        $this->actingAs($user)->post(route('sessions.store'), ['mode' => 'exam']);

        $this->assertSame('uk', ExamSession::where('user_id', $user->id)->latest('id')->first()->locale);
    }

    /** Język konta przeżywa wylogowanie — inaczej niż motyw, który jest per urządzenie. */
    public function test_jezyk_zapisuje_sie_na_koncie(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('locale.update', 'de'));

        $this->assertSame('de', $user->fresh()->locale);
    }
}
