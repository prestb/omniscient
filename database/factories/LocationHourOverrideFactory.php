<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationHourOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * LocationHourOverrideFactory (Phase 10).
 */
class LocationHourOverrideFactory extends Factory
{
    protected $model = LocationHourOverride::class;

    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
            'note' => null,
            'created_by' => null,
        ];
    }

    public function specialHours(string $opens = '10:00', string $closes = '14:00'): static
    {
    return $this->state(fn () => [
    'is_closed' => false,
    'opens_at' => $opens,
    'closes_at' => $closes,
    ]);
    }

    /**
     * Pin the override to a specific calendar date.
     */
    public function onDate($date): static
    {
    return $this->state(fn () => [
    'date' => $date instanceof \DateTimeInterface
    ? $date->format('Y-m-d')
    : (string) $date,
    ]);
    }

    /**
     * Mark the day as fully closed.
     */
    public function closed(): static
    {
    return $this->state(fn () => [
    'is_closed' => true,
    'opens_at' => null,
    'closes_at' => null,
    ]);
    }
    }
