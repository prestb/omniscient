<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BusinessHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessHourFactory extends Factory
{
    protected $model = BusinessHour::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
            'is_24h' => false,
            'sort_order' => 0,
        ];
    }

    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn () => ['day_of_week' => $dayOfWeek]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }

    public function hours24(): static
    {
        return $this->state(fn () => [
            'is_24h' => true,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }

    public function openBetween(string $opens, string $closes): static
    {
        return $this->state(fn () => [
            'opens_at' => $opens,
            'closes_at' => $closes,
            'is_closed' => false,
        ]);
    }
}