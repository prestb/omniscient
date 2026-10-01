<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * LocationFactory (Phase 10).
 *
 * Defaults to an INDEPENDENT location (`business_id = null`) to make the core
 * invariant — a Location does NOT require a Business — the path of least
 * resistance in tests. Use `->forBusiness()` when an organization link is
 * actually wanted.
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'business_id' => null,
            'name' => fake()->city() . ' Location',
            'is_primary' => false,
            'country_id' => Country::factory(),
            'region_id' => Region::factory(),
            'city_id' => City::factory(),
            'area_id' => null,
            'address' => fake()->streetAddress(),
            'landmark' => null,
            'postal_code' => null,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => null,
            'status' => Location::STATUS_ACTIVE,
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

    public function standalone(): static
    {
        return $this->state(fn () => ['business_id' => null]);
    }

    public function inCity(City $city): static
    {
        return $this->state(fn () => [
            'city_id' => $city->id,
            'region_id' => $city->region_id,
        ]);
    }
}
