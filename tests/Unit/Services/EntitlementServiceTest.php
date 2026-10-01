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
 * Phase 1 — regression coverage for the canonical entitlement API.
 *
 * Verifies that entitlement checks are expressed as *questions* (canUse /
 * canAdd / canCreate) and never depend on plan names.
 */
class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private EntitlementService $entitlements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entitlements = new EntitlementService();
    }

    // ─────────────────────────────────────────────────────────────
    // Feature entitlements (boolean)
    // ─────────────────────────────────────────────────────────────

    public function test_can_use_returns_true_when_feature_enabled(): void
    {
        $user = $this->userOnPlan([
            'features' => [Entitlement::LEAD_CAPTURE => true],
        ]);

        $this->assertTrue($this->entitlements->canUse($user, Entitlement::LEAD_CAPTURE));
    }

    public function test_can_use_returns_false_when_feature_absent(): void
    {
        $user = $this->userOnPlan(['features' => []]);

        $this->assertFalse($this->entitlements->canUse($user, Entitlement::LEAD_CAPTURE));
    }

    // ─────────────────────────────────────────────────────────────
    // Quota entitlements (numeric)
    // ─────────────────────────────────────────────────────────────

    public function test_can_add_true_when_below_limit(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3]);

        $this->assertTrue($this->entitlements->canAdd($user, Entitlement::CREATE_LISTING));
    }

    public function test_can_add_false_when_at_limit(): void
    {
        // PHASE 9 — quota counts LISTINGS, not organizations.
        $user = $this->userOnPlan(['max_listings' => 1]);
        Listing::factory()->forOwner($user)->create();

        $this->assertFalse($this->entitlements->canAdd($user, Entitlement::CREATE_LISTING));
    }

    public function test_unlimited_plan_always_allows_more(): void
    {
        $user = $this->userOnPlan(['max_listings' => -1]);
        Listing::factory()->forOwner($user)->count(5)->create();

        $this->assertTrue($this->entitlements->canAdd($user, Entitlement::CREATE_LISTING));
    }

    public function test_remaining_reports_correct_slots(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3]);
        Listing::factory()->forOwner($user)->create();

        $this->assertSame(2, $this->entitlements->remaining($user, Entitlement::CREATE_LISTING));
    }

    public function test_limit_returns_plan_value(): void
    {
        $user = $this->userOnPlan(['max_listings' => 7]);

        $this->assertSame(7, $this->entitlements->limit($user, Entitlement::CREATE_LISTING));
    }

    // ─────────────────────────────────────────────────────────────
    // Listing-type aware entry point
    // ─────────────────────────────────────────────────────────────

    public function test_can_create_business_listing_matches_quota(): void
    {
        $user = $this->userOnPlan(['max_listings' => 1]);

        $this->assertTrue($this->entitlements->canCreate($user, ListingType::BUSINESS));

        Listing::factory()->forOwner($user)->create();

        $this->assertFalse($this->entitlements->canCreate($user->fresh(), ListingType::BUSINESS));
    }

    public function test_summary_lists_known_quota_keys(): void
    {
        $user = $this->userOnPlan(['max_listings' => 3, 'max_locations' => 2]);

        $summary = $this->entitlements->summary($user);

        $this->assertArrayHasKey(Entitlement::CREATE_LISTING, $summary);
        $this->assertArrayHasKey(Entitlement::CREATE_LOCATION, $summary);
        $this->assertSame(3, $summary[Entitlement::CREATE_LISTING]['limit']);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a user with an ACTIVE subscription on a plan with the given
     * attributes. Entitlement checks resolve through the active subscription.
     */
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
