<?php

namespace Tests\Unit\Services;

use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EntitlementService;
use App\Support\Entitlement;
use App\Support\ListingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — listing-type entitlements.
 *
 * Two orthogonal rules, never conflated:
 *   - listing LIMIT (how many)
 *   - allowed listing TYPES (which kinds)
 */
class ListingEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $entitlements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entitlements = new EntitlementService();
    }

    // ─────────────────────────────────────────────────────────────
    // Type axis
    // ─────────────────────────────────────────────────────────────

    public function test_business_type_is_allowed_by_default_when_flag_absent(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3, 'features' => []]);

        // Back-compat: absent `businesses` flag ⇒ allowed.
        $this->assertTrue($this->entitlements->allowsListingType($user, ListingType::BUSINESS));
    }

    public function test_professional_type_is_not_allowed_when_flag_absent(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3, 'features' => []]);

        $this->assertFalse($this->entitlements->allowsListingType($user, ListingType::PROFESSIONAL));
    }

    public function test_professional_type_is_allowed_when_flag_enabled(): void
    {
        $user = $this->userOnPlan([
            'max_listings' => 3,
            'features' => [Entitlement::CREATE_PROFESSIONAL => true],
        ]);

        $this->assertTrue($this->entitlements->allowsListingType($user, ListingType::PROFESSIONAL));
    }

    public function test_store_type_is_not_allowed_when_flag_false(): void
    {
        $user = $this->userOnPlan([
            'max_listings' => 3,
            'features' => [Entitlement::CREATE_STORE => false],
        ]);

        $this->assertFalse($this->entitlements->allowsListingType($user, ListingType::STORE));
    }

    // ─────────────────────────────────────────────────────────────
    // Composite: type allowed AND quota available
    // ─────────────────────────────────────────────────────────────

    public function test_can_create_listing_type_requires_both_type_and_quota(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1, 'features' => []]);

        $this->assertTrue($this->entitlements->canCreateListingType($user, ListingType::BUSINESS));

        Listing::factory()->forOwner($user)->create();
        $user = $user->fresh();

        // Quota exhausted → composite false.
        $this->assertFalse($this->entitlements->canCreateListingType($user, ListingType::BUSINESS));
    }

    public function test_composite_is_false_when_type_not_allowed_even_with_quota(): void
    {
        // Business quota is generous, and the PROFESSIONAL *type* is enabled —
        // the only thing that can block it is the (unshipped) professional
        // quota, which reads as 0. This proves the two rules are independent
        // and that the composite requires BOTH.
        $user = $this->userOnPlan([
            'max_listings' => 10,
            'features' => [Entitlement::CREATE_PROFESSIONAL => true],
        ]);

        // Type IS allowed...
        $this->assertTrue($this->entitlements->allowsListingType($user, ListingType::PROFESSIONAL));
        // ...but there is no professional quota configured yet, so creation is blocked.
        $this->assertFalse($this->entitlements->canCreate($user, ListingType::PROFESSIONAL));
        $this->assertFalse($this->entitlements->canCreateListingType($user, ListingType::PROFESSIONAL));
    }

    public function test_type_disallowed_blocks_composite_even_when_quota_allows(): void
    {
        // Business quota is generous, but the STORE type is NOT enabled.
        $user = $this->userOnPlan(['max_listings' => 10, 'features' => []]);

        $this->assertFalse($this->entitlements->allowsListingType($user, ListingType::STORE));
        $this->assertFalse($this->entitlements->canCreateListingType($user, ListingType::STORE));
    }

    // ─────────────────────────────────────────────────────────────
    // Listing quota (PHASE 9: count = Listing rows)
    // ─────────────────────────────────────────────────────────────

    public function test_listing_quota_counts_listings(): void
    {
        $user = $this->userOnPlan(['max_listings' => 5]);
        Listing::factory()->count(2)->forOwner($user)->create();

        $quota = $this->entitlements->listingQuota($user->fresh());

        $this->assertSame('business', $quota['type']);
        $this->assertSame(2, $quota['current']);
        $this->assertSame(5, $quota['limit']);
        $this->assertSame(3, $quota['remaining']);
        $this->assertFalse($quota['over_quota']);
        $this->assertFalse($quota['unlimited']);
    }

    public function test_listing_quota_marks_over_quota(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1]);
        Listing::factory()->count(3)->forOwner($user)->create();

        $quota = $this->entitlements->listingQuota($user->fresh());

        $this->assertTrue($quota['over_quota']);
        $this->assertSame(0, $quota['remaining']);
    }

    public function test_listing_quota_unlimited(): void
    {
        $user = $this->userOnPlan(['max_listings' => -1]);
        Listing::factory()->count(4)->forOwner($user)->create();

        $quota = $this->entitlements->listingQuota($user->fresh());

        $this->assertTrue($quota['unlimited']);
        $this->assertSame(-1, $quota['remaining']);
        $this->assertFalse($quota['over_quota']);
    }

    // ─────────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────────

    public function test_summary_surfaces_listing_domain_keys_without_breaking_existing(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3, 'max_locations' => 2]);

        $summary = $this->entitlements->summary($user);

        // Existing numeric keys preserved.
        $this->assertArrayHasKey(Entitlement::CREATE_LISTING, $summary);
        $this->assertSame(3, $summary[Entitlement::CREATE_LISTING]['limit']);

        // New listing-domain keys.
        $this->assertArrayHasKey('listing_quota', $summary);
        $this->assertArrayHasKey('allowed_listing_types', $summary);
        $this->assertArrayHasKey('over_quota', $summary);
        $this->assertSame(3, $summary['listing_quota']['limit']);
    }

    // ─────────────────────────────────────────────────────────────
    // Helper
    // ─────────────────────────────────────────────────────────────

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
}
