<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_admin_widzi_panel_uzytkownikow(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Użytkownicy', false);
    }

    public function test_kursant_nie_ma_dostepu_do_panelu(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);

        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_gosc_jest_przekierowany_na_logowanie(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_tworzy_konto(): void
    {
        $category = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochody osobowe do 3,5 t', 'is_active' => true, 'sort_order' => 1]
        );

        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Nowy Kursant',
            'email' => 'nowy@przyklad.pl',
            'password' => 'Tajne-Haslo-123',
            'password_confirmation' => 'Tajne-Haslo-123',
            'role' => UserRole::Student->value,
            // Kategoria jest teraz cechą konta i formularz zawsze ją wysyła.
            'driving_category_id' => $category->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'nowy@przyklad.pl')->first();
        $this->assertNotNull($created);
        $this->assertSame(UserRole::Student, $created->role);
        $this->assertSame($category->id, $created->driving_category_id);
        // Konto zakładane przez admina jest od razu potwierdzone (brak wysyłki maila).
        $this->assertNotNull($created->email_verified_at);
        $this->assertNotNull($created->wallet, 'Obserwator powinien utworzyć portfel.');
    }

    public function test_tworzenie_konta_waliduje_duplikat_emaila(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Ktoś',
            'email' => $admin->email,
            'password' => 'Tajne-Haslo-123',
            'password_confirmation' => 'Tajne-Haslo-123',
            'role' => UserRole::Student->value,
        ])->assertSessionHasErrors('email');
    }
}
