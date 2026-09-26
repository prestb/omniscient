<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->unique()->state(),
            'is_active' => true,
        ];
    }
}