<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use App\Support\ListingType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'owner_id' => User::factory()->owner(),
            'business_id' => null,
            'location_id' => null,
            'type' => ListingType::BUSINESS->value,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 99999),
            'description' => $this->faker->paragraph(),
            'status' => Listing::STATUS_DRAFT,
            'is_featured' => false,
            'published_at' => null,
            'hidden_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn() => ['hidden_at' => now()]);
    }

    public function ofType(ListingType $type): static
    {
        return $this->state(fn() => ['type' => $type->value]);
    }

    public function professional(): static
    {
        return $this->ofType(ListingType::PROFESSIONAL);
    }

    public function ofStoreType(): static
    {
        return $this->ofType(ListingType::STORE);
    }

    public function forOwner(User $owner): static
    {
        return $this->state(fn() => ['owner_id' => $owner->id]);
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(fn() => [
            'business_id' => $business->id,
            'owner_id' => $business->owner_id,
        ]);
    }

    public function atLocation(Location $location): static
    {
        return $this->state(fn() => ['location_id' => $location->id]);
    }
}
