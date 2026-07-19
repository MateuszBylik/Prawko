<?php

namespace Database\Factories;

use App\Models\DrivingCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Konto bez kategorii jest odsyłane przez middleware `category` na ekran
            // wyboru, więc domyślny użytkownik z fabryki musi ją mieć — inaczej każdy
            // test nauki dostawałby 302 zamiast strony.
            // `firstOrCreate`, a nie `DrivingCategory::factory()`: to jeden słownik na
            // całą bazę, a nie osobna kategoria dla każdego konta.
            'driving_category_id' => fn () => DrivingCategory::firstOrCreate(
                ['code' => 'B'],
                ['name' => 'Samochody osobowe do 3,5 t', 'is_active' => true, 'sort_order' => 1],
            )->id,
        ];
    }

    /** Konto, które jeszcze nie wybrało kategorii (pierwsze logowanie). */
    public function withoutCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'driving_category_id' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
