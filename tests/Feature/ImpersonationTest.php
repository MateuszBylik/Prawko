<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ForecastSnapshot;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use App\Support\Impersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
    }

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    // ————————————————— Wejście i wyjście —————————————————

    public function test_admin_wchodzi_w_podglad_i_wraca_na_swoje_konto(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $student))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertSame($student->id, Auth::id(), 'Sesja musi być przełączona na podglądane konto.');
        $this->assertTrue(Impersonation::active());
        $this->assertSame($admin->id, Impersonation::impersonatorId());

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users.index'));

        $this->assertSame($admin->id, Auth::id(), 'Wyjście musi przywrócić konto administratora.');
        $this->assertFalse(Impersonation::active());
    }

    // ————————————————— Granice uprawnień —————————————————

    public function test_zwykly_uzytkownik_nie_wejdzie_w_podglad(): void
    {
        $this->actingAs($this->student())
            ->post(route('admin.users.impersonate', $this->student()))
            ->assertForbidden();

        $this->assertFalse(Impersonation::active());
    }

    /** Podgląd admina otwierałby drogę do przejęcia cudzych uprawnień. */
    public function test_nie_mozna_wejsc_w_podglad_innego_admina(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.impersonate', $this->admin()))
            ->assertForbidden();

        $this->assertFalse(Impersonation::active());
    }

    public function test_nie_mozna_wejsc_w_podglad_samego_siebie(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $admin))->assertForbidden();
    }

    /** Zagnieżdżanie rozspójniłoby powrót — nie wiadomo by było, kogo przywracać. */
    public function test_nie_mozna_zagniezdzic_podgladu(): void
    {
        $admin = $this->admin();
        $a = $this->student();
        $b = $this->student();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $a));
        $this->post(route('admin.users.impersonate', $b))->assertForbidden();

        $this->assertSame($a->id, Auth::id());
        $this->assertSame($admin->id, Impersonation::impersonatorId());
    }

    /** W podglądzie admin nie ma uprawnień admina — bo ma je konto, na którym siedzi. */
    public function test_w_podgladzie_panel_admina_jest_niedostepny(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.impersonate', $this->student()));

        $this->get(route('admin.users.index'))->assertForbidden();
    }

    // ————————————————— Podgląd nie zostawia śladu —————————————————

    /**
     * Sedno wymagania „nie pojawia się w aktywności". Aktywność liczy się z zapisów
     * (`last_active_at` to `max(user_answers.answered_at)`), więc blokujemy zapisy
     * u źródła zamiast filtrować ślady po fakcie.
     */
    public function test_podglad_nie_pozwala_zapisywac(): void
    {
        $student = $this->student();
        $cat = $this->category();

        $q = Question::create([
            'external_id' => '5555', 'type' => 'basic', 'points' => 3, 'text' => 'Czy wolno?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($cat->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);

        $this->actingAs($this->admin())->post(route('admin.users.impersonate', $student));

        // Start sesji nauki — najbardziej szkodliwy zapis: rusza θ i stan FSRS.
        $this->post(route('sessions.store'), [
            'driving_category_id' => $cat->id,
            'mode' => 'practice',
        ])->assertForbidden();

        $this->post(route('plan.update'), ['exam_date' => now()->addDays(7)->toDateString()])
            ->assertForbidden();

        $this->assertSame(0, UserAnswer::where('user_id', $student->id)->count());
        $this->assertNull($student->fresh()->exam_date);
    }

    /**
     * Wyciek, którego blokada zapisów NIE łapie: migawka prognozy powstaje przy
     * zwykłym GET-cie pulpitu. Bez wyjątku wejście w podgląd dopisywałoby punkt
     * na wykresie użytkownika w dniu, w którym się nie uczył.
     */
    public function test_podglad_pulpitu_nie_zapisuje_migawki_prognozy(): void
    {
        $student = $this->student();
        $cat = $this->category();

        // Dość odpowiedzi, żeby prognoza miała sygnał (inaczej migawki i tak by nie było).
        $session = $student->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => 'practice', 'status' => 'completed',
            'total_questions' => 12, 'max_score' => 36, 'started_at' => now(), 'completed_at' => now(),
        ]);
        foreach (range(1, 12) as $i) {
            $q = Question::create([
                'external_id' => (string) (7000 + $i), 'type' => 'basic', 'points' => 3, 'text' => "P{$i}?",
                'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
            ]);
            $q->categories()->attach($cat->id);
            Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);
            $session->answers()->create([
                'user_id' => $student->id, 'question_id' => $q->id, 'position' => $i,
                'is_correct' => true, 'answered_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())->post(route('admin.users.impersonate', $student));
        $this->get(route('dashboard'))->assertOk();

        $this->assertSame(
            0,
            ForecastSnapshot::where('user_id', $student->id)->count(),
            'Podgląd nie może dopisać użytkownikowi punktu na wykresie szansy zdania.'
        );
    }

    // ————————————————— Interfejs —————————————————

    public function test_w_podgladzie_wyloguj_zamienia_sie_w_wyjdz_z_podgladu(): void
    {
        $student = $this->student();

        $normal = $this->actingAs($student)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Wyloguj', $normal);
        $this->assertStringNotContainsString('Wyjdź z podglądu', $normal);

        $this->actingAs($this->admin())->post(route('admin.users.impersonate', $student));
        $preview = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Wyjdź z podglądu', $preview);
        $this->assertStringNotContainsString('>Wyloguj', $preview, 'Wylogowanie wyrzuciłoby admina z aplikacji.');
        $this->assertStringContainsString('Podgląd konta', $preview, 'Pas ostrzegawczy musi być widoczny.');
        $this->assertStringContainsString($student->email, $preview);
    }

    public function test_lista_kont_ma_przycisk_podgladu_tylko_przy_zwyklych_kontach(): void
    {
        $admin = $this->admin();
        $student = $this->student();

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

        // Przycisk kieruje do trasy z id użytkownika — sprawdzamy dokładny adres.
        $this->assertStringContainsString(route('admin.users.impersonate', $student), $html);
        $this->assertStringNotContainsString(route('admin.users.impersonate', $admin), $html,
            'Nie oferujemy akcji, która i tak skończy się 403.');
    }
}
