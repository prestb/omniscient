<?php

use App\Models\Business;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingService;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanEnforcementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-4 — QUOTA AUTHORITY.
 *
 *   max_listings  -> Listings      (Listings are the quota unit)
 *   max_locations -> Locations
 *   max_services  -> Business aggregate across its Listings
 *   max_images    -> Business aggregate across its Listings
 *
 * A Business is an optional ORGANIZATION and is NOT a quota unit.
 */

/** An owner with an elapsed downgrade grace, so enforcement actually hides. */
function enforceFor(array $planLimits): array
{
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($planLimits);

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    app(PlanEnforcementService::class)->enforce($sub);

    return [$user, $sub];
}

$limits = fn (array $over = []) => array_merge([
    'max_listings' => 5,
    'max_locations' => 5,
    'max_services' => 5,
    'max_images' => 5,
    'max_coupons' => 5,
], $over);

// ── A. Businesses are not quota units ───────────────────────────────────────
test('the listing quota does not constrain business count', function () use ($limits) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($limits(['max_listings' => 2]));

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    // 3 Businesses, each with ZERO Listings.
    Business::factory()->count(3)->create(['owner_id' => $user->id]);

    app(PlanEnforcementService::class)->enforce($sub);

    expect(Business::where('owner_id', $user->id)->count())->toBe(3);
    expect(Business::where('owner_id', $user->id)->whereNotNull('hidden_at')->count())->toBe(0);
});

// ── B. Listings are quota units ─────────────────────────────────────────────
test('the listing quota hides surplus listings and keeps the oldest visible', function () use ($limits) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($limits(['max_listings' => 2]));

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    $business = Business::factory()->create(['owner_id' => $user->id]);
    $listings = Listing::factory()->count(3)->forBusiness($business)->forOwner($user)->create();

    app(PlanEnforcementService::class)->enforce($sub);

    // Nothing deleted.
    expect(Listing::where('owner_id', $user->id)->count())->toBe(3);

    $hidden = Listing::where('owner_id', $user->id)->whereNotNull('hidden_at')->pluck('id');
    expect($hidden)->toHaveCount(1);
    expect($hidden->first())->toBe($listings->last()->id);   // oldest two stay
});

test('an unlimited plan restores everything and hides nothing', function () use ($limits) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($limits(['max_listings' => -1]));

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    $business = Business::factory()->create(['owner_id' => $user->id]);
    Listing::factory()->count(3)->forBusiness($business)->forOwner($user)
        ->create(['hidden_at' => now()]);

    app(PlanEnforcementService::class)->enforce($sub);

    expect(Listing::where('owner_id', $user->id)->whereNotNull('hidden_at')->count())->toBe(0);
});

// ── C. Locations remain the Location quota ──────────────────────────────────
test('max_locations still governs physical locations', function () use ($limits) {
    [$user] = enforceFor($limits(['max_locations' => 1]));

    $business = Business::factory()->create(['owner_id' => $user->id]);
    Location::factory()->count(3)->create(['business_id' => $business->id]);

    $sub = $user->activeSubscription;
    app(PlanEnforcementService::class)->enforce($sub);

    expect(Location::where('business_id', $business->id)->count())->toBe(3);
    expect(Location::where('business_id', $business->id)->whereNotNull('hidden_at')->count())->toBe(2);
});

// ── D. Services remain a Business aggregate across its Listings ─────────────
test('max_services aggregates services across the business listings', function () use ($limits) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($limits(['max_services' => 2]));

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    $business = Business::factory()->create(['owner_id' => $user->id]);
    $a = Listing::factory()->forBusiness($business)->forOwner($user)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($user)->create();

    // Spread across TWO Listings of the same Business. No factory exists for
    // these Listing-owned child models, so rows are created directly.
    foreach ([$a, $b] as $listing) {
        foreach ([1, 2] as $i) {
            ListingService::create([
                'listing_id' => $listing->id,
                'name' => "Service {$listing->id}-{$i}",
                'sort_order' => $i,
            ]);
        }
    }

    app(PlanEnforcementService::class)->enforce($sub);

    // 4 services across the Business, limit 2 → 2 visible, 2 hidden.
    $all = ListingService::whereIn('listing_id', [$a->id, $b->id])->get();
    expect($all)->toHaveCount(4);
    expect($all->whereNotNull('hidden_at'))->toHaveCount(2);
});

// ── E. Images remain a Business aggregate across its Listings ───────────────
test('max_images aggregates images across the business listings', function () use ($limits) {
    $user = User::factory()->owner()->create();
    $plan = Plan::factory()->create($limits(['max_images' => 2]));

    $sub = Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'downgrade_grace_ends_at' => now()->subDay(),
    ]);

    $business = Business::factory()->create(['owner_id' => $user->id]);
    $a = Listing::factory()->forBusiness($business)->forOwner($user)->create();
    $b = Listing::factory()->forBusiness($business)->forOwner($user)->create();

    foreach ([$a, $b] as $listing) {
        foreach ([1, 2] as $i) {
            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => "listings/{$listing->id}/img-{$i}.jpg",
                'type' => 'gallery',
                'sort_order' => $i,
            ]);
        }
    }

    app(PlanEnforcementService::class)->enforce($sub);

    $all = ListingImage::whereIn('listing_id', [$a->id, $b->id])->get();
    expect($all)->toHaveCount(4);
    expect($all->whereNotNull('hidden_at'))->toHaveCount(2);
});

// ── F. Coupons unchanged ────────────────────────────────────────────────────
test('coupon enforcement is unchanged by the listing quota change', function () {
    // No Coupon factory exists and coupons carry required columns, so this is a
    // source-level regression assertion rather than a fixture-driven one: the
    // coupon branch must still be scoped by `business_id` and must not have been
    // pulled into this pass.
    $code = File::get(app_path('Services/PlanEnforcementService.php'));

    expect(str_contains($code, "Coupon::where('business_id', \$business->id)"))
        ->toBeTrue('Coupon enforcement must remain Business-scoped and untouched.');

    // And it must not consult the Listing quota.
    $couponMethod = substr($code, strpos($code, 'private function enforceCoupons'));
    $couponMethod = substr($couponMethod, 0, strpos($couponMethod, 'private function', 10) ?: null);
    expect(str_contains($couponMethod, 'max_listings'))->toBeFalse();
});

// ── Guard: the obsolete surface is gone ─────────────────────────────────────
test('the obsolete business quota surface no longer exists', function () {
    expect(method_exists(Business::class, 'canCreateBusiness'))->toBeFalse();
    expect(method_exists(Business::class, 'getRemainingBusinessSlots'))->toBeFalse();
    expect(method_exists(User::class, 'canCreateBusiness'))->toBeFalse();
    expect(method_exists(User::class, 'getRemainingBusinessSlots'))->toBeFalse();
});
