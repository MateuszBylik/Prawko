<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleAndCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $code = 'B'): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => $code],
            ['name' => 'Kategoria '.$code, 'is_active' => true, 'sort_order' => 1]
        );
    }

    // ————————————————— Języki —————————————————

    public function test_strona_glowna_tlumaczy_sie_na_wybrany_jezyk(): void
    {
        $this->get(route('locale.update', 'en'))->assertRedirect(route('welcome'));
        $this->get('/')->assertOk()->assertSee('Log in', false)->assertDontSee('Zaloguj się', false);

        $this->get(route('locale.update', 'de'));
        $this->get('/')->assertOk()->assertSee('Anmelden', false);

        $this->get(route('locale.update', 'uk'));
        $this->get('/')->assertOk()->assertSee('Увійти', false);

        $this->get(route('locale.update', 'pl'));
        $this->get('/')->assertOk()->assertSee('Zaloguj się', false);
    }

    /** Kod języka pochodzi z adresu, więc może być czymkolwiek — nie wolno go ufać. */
    public function test_nieznany_jezyk_daje_404_i_nie_zmienia_ustawienia(): void
    {
        $this->get(route('locale.update', 'en'));
        $this->get('/jezyk/xx')->assertNotFound();

        $this->get('/')->assertOk()->assertSee('Log in', false);
    }

    public function test_atrybut_lang_dokumentu_podaza_za_jezykiem(): void
    {
        $this->get(route('locale.update', 'uk'));

        $this->assertStringContainsString('<html lang="uk"', $this->get('/')->getContent());
    }

    public function test_strona_glowna_ma_przelacznik_jezyka_i_motywu(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['pl', 'en', 'de', 'uk'] as $code) {
            $this->assertStringContainsString(route('locale.update', $code), $html);
        }

        $this->assertStringContainsString('themeSwitcher', $html, 'Bez tego motywu nie da się tu zmienić.');
    }

    // ————————————————— Kategoria —————————————————

    public function test_konto_bez_kategorii_trafia_na_ekran_wyboru(): void
    {
        $user = User::factory()->withoutCategory()->create(['role' => UserRole::Student]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('category.show'));
        $this->actingAs($user)->get(route('category.show'))->assertOk()->assertSee('Na jaką kategorię zdajesz?', false);
    }

    public function test_wybor_kategorii_zapisuje_sie_i_odblokowuje_pulpit(): void
    {
        $user = User::factory()->withoutCategory()->create(['role' => UserRole::Student]);
        $category = $this->category('C');

        $this->actingAs($user)->post(route('category.store'), ['driving_category_id' => $category->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($category->id, $user->fresh()->driving_category_id);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
    }

    public function test_kto_juz_wybral_nie_wraca_na_ekran_wyboru(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($user)->get(route('category.show'))->assertRedirect(route('dashboard'));
    }

    /** Nieaktywna kategoria nie ma pytań — wybór byłby pułapką. */
    public function test_nie_mozna_wybrac_nieaktywnej_kategorii(): void
    {
        $user = User::factory()->withoutCategory()->create(['role' => UserRole::Student]);
        $inactive = DrivingCategory::create(['code' => 'PT', 'name' => 'Tramwaj', 'is_active' => false, 'sort_order' => 9]);

        $this->actingAs($user)->post(route('category.store'), ['driving_category_id' => $inactive->id])
            ->assertSessionHasErrors('driving_category_id');

        $this->assertNull($user->fresh()->driving_category_id);
    }

    public function test_kategorie_zmienia_sie_w_profilu(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);
        $c = $this->category('C');

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'driving_category_id' => $c->id,
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame($c->id, $user->fresh()->driving_category_id);
    }

    /**
     * Kategoria jest cechą konta — dosłanie jej przy starcie sesji nie może nic zmienić,
     * bo StartSessionRequest jej nie waliduje, a kontroler czyta ją z użytkownika.
     */
    public function test_pulpit_nie_pozwala_wybrac_kategorii(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="driving_category_id"', $html);
        $this->assertStringNotContainsString('<select id="driving_category_id"', $html);
    }

    // ————————————————— Admin nie uczy się —————————————————

    public function test_admin_nie_ma_dostepu_do_nauki(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Wejście GET-em na ekran nauki to nie błąd, tylko zła strona domowa —
        // odsyłamy bez komunikatu.
        foreach ([route('dashboard'), route('losses.index')] as $url) {
            $this->actingAs($admin)->get($url)
                ->assertRedirect(route('admin.dashboard'))
                ->assertSessionMissing('error');
        }

        // Próba ZROBIENIA czegoś zasługuje na wyjaśnienie — cisza byłaby myląca.
        $this->actingAs($admin)->post(route('sessions.store'), ['mode' => 'practice'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->assertSame(0, $admin->examSessions()->count());
    }

    /** Admin musi móc weryfikować pytania — baza zostaje dostępna. */
    public function test_admin_ma_dostep_do_bazy_pytan(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('questions.index'))->assertOk();
    }

    public function test_admin_z_korzenia_ladzie_w_panelu_a_kursant_na_pulpicie(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get('/')->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->create(['role' => UserRole::Student]))
            ->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_nawigacja_admina_nie_prowadzi_do_pulpitu(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('questions.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('dashboard'), $html, 'Link do zablokowanego ekranu to martwy link.');
        $this->assertStringContainsString(route('admin.users.index'), $html);
    }

    public function test_admin_ma_wlasny_pulpit_ze_statystykami(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->count(2)->create(['role' => UserRole::Student]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Ogólny stan serwisu', $html);
        $this->assertStringContainsString('Odpowiedzi dziennie', $html);
        $this->assertStringContainsString('Baza pytań', $html);
        // Wykres musi mieć dostępny odpowiednik liczbowy.
        $this->assertStringContainsString('Pokaż jako tabelę', $html);
    }

    public function test_kursant_nie_wejdzie_na_pulpit_admina(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Student]))
            ->get(route('admin.dashboard'))->assertForbidden();
    }

    /** Logowanie admina nie może kończyć się odbiciem przez pulpit nauki i komunikatem. */
    public function test_admin_po_zalogowaniu_ladzie_wprost_na_swoim_pulpicie(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'password' => bcrypt('Tajne-Haslo-123')]);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'Tajne-Haslo-123'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('error');
    }

    public function test_kursant_po_zalogowaniu_ladzie_na_pulpicie_nauki(): void
    {
        $user = User::factory()->create(['role' => UserRole::Student, 'password' => bcrypt('Tajne-Haslo-123')]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Tajne-Haslo-123'])
            ->assertRedirect(route('dashboard'));
    }

    /**
     * Flagi muszą być SVG, nie emoji: Windows nie ma czcionki z flagami i degraduje
     * je do liter kraju („PL"), czyli ikona nie działa u dużej części użytkowników.
     */
    public function test_flagi_sa_svg_a_nie_emoji(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['🇵🇱', '🇬🇧', '🇩🇪', '🇺🇦'] as $emoji) {
            $this->assertStringNotContainsString($emoji, $html, 'Emoji flag nie renderuje się na Windowsie.');
        }

        // Charakterystyczne kolory: biało-czerwona, Union Jack, czarno-czerwono-złota, niebiesko-żółta.
        foreach (['#DC143C', '#012169', '#FFCE00', '#005BBB'] as $color) {
            $this->assertStringContainsString($color, $html);
        }
    }

    /** Union Jack używa clipPath — zduplikowane `id` rozjechałoby drugą flagę. */
    public function test_flagi_nie_duplikuja_identyfikatorow_w_dom(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/<clipPath id="([^"]+)"/', $html, $m);

        $this->assertSame($m[1], array_unique($m[1]), 'Powtórzony id clipPath psuje flagę.');
    }

    /** Pulpit to strona domowa roli — w pasku stoi pierwszy, tak samo dla obu ról. */
    public function test_pulpit_jest_pierwszym_linkiem_w_nawigacji_dla_obu_rol(): void
    {
        $cases = [
            [User::factory()->create(['role' => UserRole::Admin]), route('admin.dashboard'), route('questions.index')],
            [User::factory()->create(['role' => UserRole::Student]), route('dashboard'), route('questions.index')],
        ];

        foreach ($cases as [$user, $home, $questions]) {
            $html = $this->actingAs($user)->get(route('questions.index'))->assertOk()->getContent();

            $this->assertLessThan(
                strpos($html, 'href="'.$questions.'"'),
                strpos($html, 'href="'.$home.'"'),
                'Pulpit musi stać przed bazą pytań.'
            );
        }
    }
}
