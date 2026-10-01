<?php

namespace Tests\Feature\Listing;

use App\Models\Location;
use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EntitlementService;
use App\Support\ListingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — entitlement quota counts LISTINGS, not organizations or locations.
 */
class ListingQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function userOnPlan(array $planAttributes): User
    {
        $user = User::factory()->owner()->create();
        $plan = Plan::factory()->create($planAttributes);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);

        return $user->fresh();
    }

    public function test_organizations_and_locations_do_not_count_against_listing_quota(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1]);

        // One organization with two locations — but only ONE listing.
        $business = Business::factory()->create(['owner_id' => $user->id]);
        Location::factory()->forBusiness($business)->count(2)->create();
        Listing::factory()->forBusiness($business)->create();

        $quota = app(EntitlementService::class)->listingQuota($user->fresh());

        $this->assertSame(1, $quota['current']);
        $this->assertSame(1, $quota['limit']);
        $this->assertFalse($quota['over_quota']);
    }

    public function test_listing_count_is_authoritative(): void
    {
        $user = $this->userOnPlan(['max_listings' => 5]);
        Listing::factory()->forOwner($user)->count(3)->create();

        $this->assertSame(3, Listing::countFor($user));
        $this->assertSame(3, $user->fresh()->listingsCount());

        $quota = app(EntitlementService::class)->listingQuota($user->fresh());
        $this->assertSame(3, $quota['current']);
        $this->assertSame(5, $quota['limit']);
    }

    public function test_over_quota_is_reported_against_listings(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1]);
        Listing::factory()->forOwner($user)->count(4)->create();

        $quota = app(EntitlementService::class)->listingQuota($user->fresh());

        $this->assertTrue($quota['over_quota']);
        $this->assertSame(0, $quota['remaining']);
    }

    public function test_can_create_listing_type_gates_on_listing_quota(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1, 'features' => []]);
        $entitlements = app(EntitlementService::class);

        $this->assertTrue($entitlements->canCreateListingType($user, ListingType::BUSINESS));

        Listing::factory()->forOwner($user)->create();

        $this->assertFalse($entitlements->canCreateListingType($user->fresh(), ListingType::BUSINESS));
    }

    public function test_rejected_and_deleted_listings_excluded_from_count(): void
    {
        $user = $this->userOnPlan(['max_listings' => 5]);
        Listing::factory()->forOwner($user)->count(2)->create();
        Listing::factory()->forOwner($user)->create(['status' => 'rejected']);
        Listing::factory()->forOwner($user)->create(['status' => 'deleted']);

        $this->assertSame(2, Listing::countFor($user));
    }
}
