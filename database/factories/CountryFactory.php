<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        $name = fake()->unique()->country();

        return [
            'name' => $name,
            // ✅ `code` is unique (max 3 chars) — generate a random 3-letter code
            'code' => strtoupper(Str::random(3)),
            'is_active' => true,
        ];
    }
}