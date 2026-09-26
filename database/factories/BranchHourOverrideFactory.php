<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchHourOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchHourOverrideFactory extends Factory
{
    protected $model = BranchHourOverride::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'date' => today(),
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
            'note' => null,
            'created_by' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ]);
    }

    public function specialHours(string $opens, string $closes): static
    {
        return $this->state(fn () => [
            'is_closed' => false,
            'opens_at' => $opens,
            'closes_at' => $closes,
        ]);
    }

    public function onDate(\Carbon\Carbon $date): static
    {
        return $this->state(fn () => ['date' => $date]);
    }
}