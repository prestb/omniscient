<?php

use App\Helpers\SubscriptionHelper;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WAVE A1 - ENTITLEMENT SEMANTICS CONVERGENCE.
 *
 * Before this wave the repository carried FOUR mutually inconsistent readings of an
 * unlimited plan limit:
 *
 *   Plan::isUnlimited()                    -1 || null
 *   SubscriptionHelper::hasReachedLimit()  -1 only
 *   Business / EntitlementService /        -1 || 999
 *   PlanEnforcementService / SubscriptionService /
 *   ListingState / BusinessController / DashboardController
 *   (frontend)                             mostly -1, some -1 || 999
 *
 * The consequence was concrete: a plan storing 999 was UNLIMITED to the services and
 * models but a hard 999 limit to SubscriptionHelper. The same plan granted and denied
 * the same capability depending on which layer answered.
 *
 * CANONICAL POLICY: Plan::isUnlimitedLimit() is the single predicate; -1 is the
 * documented sentinel, 999 is a legacy alias accepted everywhere, and null is
 * UNCONFIGURED and fails CLOSED (never unlimited).
 */

// ═══ The predicate itself ═══════════════════════════════════════════════════

test('the canonical predicate classifies every limit value', function (int|string|null $limit, bool $expected) {
    expect(Plan::isUnlimitedLimit($limit))->toBe($expected);
})->with([
    'canonical sentinel -1' => [-1, true],
    'legacy sentinel 999' => [999, true],
    'unconfigured null' => [null, false],
    'zero allowance' => [0, false],
    'finite limit 5' => [5, false],
    'empty string' => ['', false],
    'numeric string -1' => ['-1', true],
]);

test('normalisation yields a comparable ceiling and never fails open', function (int|string|null $limit, int $expected) {
    expect(Plan::normaliseLimit($limit))->toBe($expected);
})->with([
    'canonical sentinel -1' => [-1, PHP_INT_MAX],
    'legacy sentinel 999' => [999, PHP_INT_MAX],
    'unconfigured null' => [null, 0],
    'zero allowance' => [0, 0],
    'finite limit 5' => [5, 5],
]);

// ═══ Plan model agrees with the predicate ═══════════════════════════════════

test('Plan::isUnlimited agrees with the canonical predicate for every resource', function (int $limit, bool $expected) {
    $plan = Plan::factory()->create([
        'max_listings' => $limit,
        'max_locations' => $limit,
        'max_services' => $limit,
        'max_images' => $limit,
        'max_coupons' => $limit,
    ]);

    foreach (['listings', 'locations', 'services', 'images', 'coupons'] as $resource) {
        expect($plan->isUnlimited($resource))->toBe($expected);
    }
})->with([
    'sentinel -1' => [-1, true],
    'legacy 999' => [999, true],
    'zero' => [0, false],
    'finite 5' => [5, false],
]);

test('an unconfigured limit is NOT unlimited', function () {
    // The columns are NOT NULL, so null can only mean "not loaded". It must fail
    // closed rather than granting an unloaded plan unlimited access.
    $plan = new Plan(['max_listings' => null]);

    expect($plan->isUnlimited('listings'))->toBeFalse()
        ->and($plan->getLimit('listings'))->toBe(0);
});

test('an unknown resource is not unlimited', function () {
    $plan = Plan::factory()->create(['max_listings' => -1]);

    expect($plan->isUnlimited('nonexistent'))->toBeFalse();
});

// ═══ The consumer that actually diverged ════════════════════════════════════

test('SubscriptionHelper treats the legacy 999 sentinel as unlimited', function () {
    // This is the regression: it previously tested `=== -1` only, so 999 was read as
    // a hard 999 limit here while the services treated it as unlimited.
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => 999]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    expect(SubscriptionHelper::hasReachedLimit(User::find($user->id), 'listings'))->toBeFalse();
});

test('SubscriptionHelper treats the canonical sentinel as unlimited', function () {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => -1]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    expect(SubscriptionHelper::hasReachedLimit(User::find($user->id), 'listings'))->toBeFalse();
});

test('SubscriptionHelper still enforces a finite listing limit', function () {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => 1]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    // Below the limit.
    expect(SubscriptionHelper::hasReachedLimit(User::find($user->id), 'listings'))->toBeFalse();

    Listing::factory()->published()->create(['owner_id' => $user->id, 'business_id' => null]);

    // At the limit.
    expect(SubscriptionHelper::hasReachedLimit(User::find($user->id), 'listings'))->toBeTrue();
});

test('a zero allowance is not mistaken for unlimited', function () {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => 0]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    expect(SubscriptionHelper::hasReachedLimit(User::find($user->id), 'listings'))->toBeTrue();
});

// ═══ HasPlanFeatures, the other policy boundary ════════════════════════════

test(
    'an unlimited plan always allows adding one more listing',
    function (int $limit) {
        $user = User::factory()->owner()->create();
        $plan = Plan::factory()->create(['max_listings' => $limit]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Usage deliberately pushed AT the notional ceiling: an unlimited plan
        // must still permit another Listing.
        Listing::factory()->count(3)->published()->create(['owner_id' => $user->id, 'business_id' => null]);

        expect(User::find($user->id)->canAdd('listings'))->toBeTrue();
    }
)->with([-1, 999]);

test('a finite plan stops allowing additions at its limit', function () {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => 1]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    expect(User::find($user->id)->canAdd('listings'))->toBeTrue();

    Listing::factory()->published()->create(['owner_id' => $user->id, 'business_id' => null]);

    expect(User::find($user->id)->canAdd('listings'))->toBeFalse();
});

test('HasPlanFeatures reports unlimited remaining for both sentinels', function (int $limit) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create(['max_listings' => $limit]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    // ESTABLISHED CONVENTION: remaining() propagates the RAW sentinel, so an
    // unlimited plan reports -1. The frontend depends on exactly this (Usage/Index
    // renders `limit === -1 ? '∞'`, Dashboard checks `usage[key].limit === -1`), so
    // it is pinned as observed rather than changed in this wave.
    //
    // RESIDUAL RISK (documented, not fixed here): a caller that treats a negative
    // `remaining()` as "over quota" would misread an unlimited plan. No such caller
    // exists today; normaliseLimit() is available if one is ever introduced.
    expect(User::find($user->id)->remaining('listings'))->toBe(-1);
})->with([-1, 999]);
