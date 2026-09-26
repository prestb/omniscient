<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->city() . ' Branch',
            'is_primary' => false,
            'country_id' => Country::factory(),
            'region_id' => Region::factory(),
            'city_id' => City::factory(),
            'area_id' => null,
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => null,
            'status' => Branch::STATUS_ACTIVE,
            'sort_order' => 0,
            'hidden_at' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(fn () => ['business_id' => $business->id]);
    }

    public function inCity(City $city): static
    {
        return $this->state(fn () => [
            'city_id' => $city->id,
            'region_id' => $city->region_id,
        ]);
    }
}