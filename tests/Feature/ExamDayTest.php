<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dzień egzaminu: pytanie „jak poszło?", zapis podejść i trofea.
 */
class ExamDayTest extends TestCase
{
    use RefreshDatabase;

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    private function student(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => UserRole::Student,
            'driving_category_id' => $this->category()->id,
        ], $attrs));
    }

    // ————————————————— Widoczność pytania —————————————————

    public function test_pytanie_pokazuje_sie_w_dniu_egzaminu(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('exam_day.title'));
    }

    public function test_pytanie_pokazuje_sie_gdy_termin_juz_minal(): void
    {
        $user = $this->student(['exam_date' => today()->subDays(3)]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('exam_day.title'));
    }

    public function test_pytania_nie_ma_przed_dniem_egzaminu(): void
    {
        $user = $this->student(['exam_date' => today()->addDays(5)]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('exam_day.title'));
    }

    public function test_pytania_nie_ma_bez_ustawionej_daty(): void
    {
        $user = $this->student(['exam_date' => null]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('exam_day.title'));
    }

    // ————————————————— Cztery wyjścia —————————————————

    public function test_zdalem_zapisuje_trofeum_i_zeruje_date(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->post(route('exam.day.respond'), ['outcome' => 'passed'])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'user_id' => $user->id,
            'driving_category_id' => $user->driving_category_id,
            'passed' => true,
        ]);
        $this->assertNull($user->fresh()->exam_date);
    }

    public function test_niezdalem_zapisuje_podejscie_i_zeruje_date(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->post(route('exam.day.respond'), ['outcome' => 'failed'])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'user_id' => $user->id,
            'passed' => false,
        ]);
        $this->assertNull($user->fresh()->exam_date);
    }

    public function test_bede_dzis_odklada_pytanie_do_jutra(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->post(route('exam.day.respond'), ['outcome' => 'later'])
            ->assertRedirect();

        $fresh = $user->fresh();
        // Data zostaje, ale pytanie znika na dziś.
        $this->assertTrue($fresh->exam_date->isToday());
        $this->assertTrue($fresh->exam_prompt_snoozed_on->isToday());
        $this->assertFalse($fresh->hasExamDayPrompt());
        $this->assertDatabaseCount('exam_attempts', 0);

        // Nazajutrz drzemka wygasa (egzamin jest już „za nami").
        $fresh->forceFill(['exam_date' => today()->subDay(), 'exam_prompt_snoozed_on' => today()->subDay()])->save();
        $this->assertTrue($fresh->fresh()->hasExamDayPrompt());
    }

    public function test_zmiana_terminu_ustawia_nowa_date_bez_podejscia(): void
    {
        $user = $this->student(['exam_date' => today()]);
        $newDate = today()->addDays(10)->toDateString();

        $this->actingAs($user)->post(route('exam.day.respond'), [
            'outcome' => 'reschedule',
            'exam_date' => $newDate,
        ])->assertRedirect();

        $this->assertSame($newDate, $user->fresh()->exam_date->toDateString());
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_zmiana_terminu_wymaga_daty(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->post(route('exam.day.respond'), ['outcome' => 'reschedule'])
            ->assertSessionHasErrors('exam_date');
    }

    public function test_odrzuca_nieznane_wyjscie(): void
    {
        $user = $this->student(['exam_date' => today()]);

        $this->actingAs($user)->post(route('exam.day.respond'), ['outcome' => 'whatever'])
            ->assertSessionHasErrors('outcome');
    }

    // ————————————————— Trofea —————————————————

    public function test_trofeum_pojawia_sie_w_profilu(): void
    {
        $user = $this->student();
        ExamAttempt::create([
            'user_id' => $user->id,
            'driving_category_id' => $user->driving_category_id,
            'passed' => true,
            'taken_on' => today(),
        ]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('profile.trophies.title'))
            ->assertSee(__('profile.trophies.category', ['code' => 'B']));
    }

    public function test_niezdane_podejscie_nie_daje_trofeum(): void
    {
        $user = $this->student();
        ExamAttempt::create([
            'user_id' => $user->id,
            'driving_category_id' => $user->driving_category_id,
            'passed' => false,
            'taken_on' => today(),
        ]);

        $this->assertTrue($user->trophies()->isEmpty());
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee(__('profile.trophies.title'));
    }

    public function test_trofeum_widac_na_liscie_admina(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $student = $this->student(['name' => 'Kursant Trofeowy']);
        ExamAttempt::create([
            'user_id' => $student->id,
            'driving_category_id' => $student->driving_category_id,
            'passed' => true,
            'taken_on' => today(),
        ]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('admin.users.trophy_title', ['code' => 'B']));
    }

    // ————————————————— Podgląd (impersonacja) —————————————————

    public function test_w_podgladzie_pytanie_widac_ale_zapis_jest_zablokowany(): void
    {
        $student = $this->student(['exam_date' => today()]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.users.impersonate', $student));

        // Ekran się pokazuje (odczyt).
        $this->get(route('dashboard'))->assertOk()->assertSee(__('exam_day.title'));

        // Ale odpowiedź to zapis — blokada podglądu ją odrzuca.
        $this->post(route('exam.day.respond'), ['outcome' => 'passed'])->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertTrue($student->fresh()->exam_date->isToday());
    }
}
