<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_can_be_created()
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create(['owner_id' => $owner->id]);
        $plan = Plan::factory()->create();

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_subscription_is_active()
    {
        $subscription = Subscription::factory()->create(['status' => 'active']);
        $this->assertTrue($subscription->isActive());

        $subscription = Subscription::factory()->create(['status' => 'expired']);
        $this->assertFalse($subscription->isActive());
    }

    public function test_subscription_has_days_remaining()
    {
        $subscription = Subscription::factory()->create([
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertEquals(30, $subscription->days_remaining);
    }

    public function test_subscription_with_expired_date_has_zero_days()
    {
        $subscription = Subscription::factory()->create([
            'end_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertEquals(0, $subscription->days_remaining);
    }

    public function test_subscription_can_be_renewed()
    {
        $subscription = Subscription::factory()->create(['status' => 'expired']);
        $subscription->renew(now()->addYear()->toDateString());

        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(now()->addYear()->toDateString(), $subscription->end_date->toDateString());
    }
}