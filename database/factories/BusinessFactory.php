<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'owner_id' => User::factory()->owner(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'status' => 'draft',
            'is_featured' => false,
            'published_at' => null,
            'submitted_at' => null,
            'hidden_at' => null,
        ];
    }

    public function withName(string $name): self
    {
        return $this->state([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    // ✅ States for common test scenarios
    public function published(): self
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function submitted(): self
    {
        return $this->state([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function featured(): self
    {
        return $this->state([
            'is_featured' => true,
        ]);
    }
}