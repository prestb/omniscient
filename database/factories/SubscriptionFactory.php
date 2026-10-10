<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween($startDate, '+1 year');

        return [
            // PHASE 24-R1 — a subscription belongs to the ACCOUNT.
            'user_id' => User::factory()->owner(),
            'plan_id' => Plan::factory(),
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_trial' => false,
            'trial_end_date' => null,
        ];
    }
}