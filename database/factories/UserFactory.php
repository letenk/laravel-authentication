<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'email'      => fake()->unique()->safeEmail(),
            'phone'      => null,
            'password'   => static::$password ??= Hash::make('password'),
            'login_type' => 'email',
            'is_verified' => false,
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function phone(): static
    {
        return $this->state(fn () => [
            'email'      => null,
            'phone'      => fake()->unique()->numerify('+628##########'),
            'login_type' => 'phone',
        ]);
    }
}
