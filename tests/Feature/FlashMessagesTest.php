<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
    }

    public function test_zapis_profilu_pokazuje_polski_komunikat_zamiast_klucza(): void
    {
        $user = $this->user();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nowa Nazwa',
            'email' => $user->email,
        ])->assertSessionHas('status', 'profile-updated');

        // Po przekierowaniu użytkownik widzi tłumaczenie, a nie klucz techniczny.
        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('Zapisano zmiany w profilu.', $html);
        $this->assertStringNotContainsString('profile-updated', $html);
    }

    public function test_zmiana_hasla_pokazuje_polski_komunikat(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'Nowe-Haslo-123',
            'password_confirmation' => 'Nowe-Haslo-123',
        ])->assertSessionHas('status', 'password-updated');

        $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();

        $this->assertStringContainsString('Hasło zostało zmienione.', $html);
        $this->assertStringNotContainsString('password-updated', $html);
    }

    public function test_zapis_daty_egzaminu_pokazuje_komunikat_dokladnie_raz(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post(route('plan.update'), [
            'exam_date' => now()->addDays(30)->toDateString(),
        ])->assertSessionHas('success', 'Zapisano datę egzaminu.');

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Zapisano datę egzaminu.'),
            'Komunikat musi być renderowany tylko przez layout (nie duplikowany przez widok).'
        );
    }

    public function test_nieznany_klucz_statusu_nie_wycieka_do_widoku(): void
    {
        $html = $this->actingAs($this->user())
            ->withSession(['status' => 'jakis-nieznany-klucz'])
            ->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('jakis-nieznany-klucz', $html);
    }
}
