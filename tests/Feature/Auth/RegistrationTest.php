<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Samodzielna rejestracja jest w tej aplikacji wyłączona — konta zakłada
 * administrator (patrz AdminPanelTest). Testy pilnują, by nie wróciła po cichu.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekran_rejestracji_informuje_o_wylaczeniu(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_nie_mozna_zalozyc_konta_samodzielnie(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(405);

        $this->assertGuest();
        $this->assertNull(User::where('email', 'test@example.com')->first());
    }

    public function test_helper_route_has_register_jest_falszywy(): void
    {
        // Dzięki temu widoki Breeze same ukrywają linki „Zarejestruj się".
        $this->assertFalse(Route::has('register'));
    }
}
