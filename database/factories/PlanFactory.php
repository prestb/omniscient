<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' Plan',
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'price_monthly' => fake()->randomFloat(2, 5000, 20000),
            'price_yearly' => fake()->randomFloat(2, 50000, 200000),
            'currency' => 'XAF',
            'max_branches' => fake()->numberBetween(1, 5),
            'max_businesses' => fake()->numberBetween(1, 3),
            'max_images' => fake()->numberBetween(5, 20),
            'is_featured' => fake()->boolean(20),
            'is_active' => true,
        ];
    }
}