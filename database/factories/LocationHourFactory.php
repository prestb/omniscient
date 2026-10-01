<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * LocationHourFactory (Phase 10).
 *
 * Each generated row gets a DISTINCT `day_of_week` via a monotonic cursor so
 * `->count(N)` (which shares one location_id) can never collide on the
 * `(location_id, day_of_week, sort_order)` unique key. Tests that need a
 * SPECIFIC day use `->forDay($day)`.
 */
class LocationHourFactory extends Factory
{
    protected $model = LocationHour::class;

    private static int $dayCursor = 0;

    public function definition(): array
    {
        $day = self::$dayCursor % 7;
        self::$dayCursor++;

        return [
            'location_id' => Location::factory(),
            'day_of_week' => $day,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
            'is_24h' => false,
            'sort_order' => 0,
        ];
    }

    public function forDay(int $day): static
    {
        return $this->state(fn () => ['day_of_week' => $day]);
    }

    /**
     * Force an explicit open window (overrides the default 09:00–17:00).
     */
    public function openBetween(string $opens, string $closes): static
    {
    return $this->state(fn () => [
    'opens_at' => $opens,
    'closes_at' => $closes,
    'is_closed' => false,
    'is_24h' => false,
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
