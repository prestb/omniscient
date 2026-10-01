<?php

namespace Tests\Feature\Architecture;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 §7 — data safety: subscription changes must NEVER destroy listings.
 *
 * These tests assert the canonical invariant: when an account is at or
 * beyond its entitlement, the surplus listings still EXIST in the database.
 * Quota enforcement may hide them (via `hidden_at`) but must not delete them.
 */
class PlanDowngradeSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_downgrading_plan_does_not_delete_existing_listings(): void
    {
        $user = User::factory()->owner()->create();

        // Three listings, on a plan that allows three.
        $oldPlan = Plan::factory()->create(['max_listings' => 3]);
        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);

        Business::factory()->count(3)->create(['owner_id' => $user->id]);

        $this->assertSame(3, $user->businesses()->count());

        // Downgrade to a plan allowing only one.
        $newPlan = Plan::factory()->create(['max_listings' => 1]);
        $sub->update([
            'plan_id' => $newPlan->id,
            'downgrade_grace_ends_at' => now()->addDays(7)->toDateString(),
        ]);

        // DATA SAFETY: all three still exist. Nothing was deleted.
        $this->assertSame(3, Business::where('owner_id', $user->id)->count());
        $this->assertSame(0, Business::onlyTrashed()->count());
    }

    public function test_hidden_listing_is_preserved_and_can_be_counted(): void
    {
        $user = User::factory()->owner()->create();

        $business = Business::factory()->create([
            'owner_id' => $user->id,
            'hidden_at' => now(), // quota-hidden, not deleted
        ]);

        $this->assertTrue($business->isHidden());
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'owner_id' => $user->id,
        ]);
        // Still soft-deletable by the owner to fall back under quota.
        $this->assertSame(1, Business::where('owner_id', $user->id)->count());
    }

    public function test_hidden_listing_is_not_publicly_visible(): void
    {
        $user = User::factory()->owner()->create();
        Plan::factory()->create(['max_listings' => 1]);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);

        $business = Business::factory()->published()->create([
            'owner_id' => $user->id,
            'hidden_at' => now(),
        ]);

        $this->get('/business/' . $business->slug)->assertNotFound();
    }
}
