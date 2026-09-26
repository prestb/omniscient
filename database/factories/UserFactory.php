<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // ✅ Default role/status so tests don't get null values
            'role' => 'user',
            'status' => 'active',
        ];
    }

    /**
     * Owner role — for tests that need an owner.
     */
    public function owner(): static
    {
        return $this->state(fn () => ['role' => 'owner']);
    }

    /**
     * Admin role.
     */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    /**
     * Super admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => 'super_admin']);
    }

    /**
     * Suspended status.
     */
    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }

    /**
     * Unverified email.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}