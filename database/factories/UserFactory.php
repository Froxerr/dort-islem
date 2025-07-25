<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
             // Faker kütüphanesi ile rastgele ve benzersiz veriler üretiyoruz
             'username' => $this->faker->unique()->userName(),
             'email' => $this->faker->unique()->safeEmail(),
             'password' => Hash::make('password'), // Tüm sahte kullanıcıların şifresi 'password' olsun
             'level' => $this->faker->numberBetween(1, 15),
             'xp' => $this->faker->numberBetween(100, 25000),
        ];
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
