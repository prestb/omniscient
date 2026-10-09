<?php

use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Region;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * An owner WITH an active subscription.
 *
 * The Business-scoped Location store enforces the account Location quota, so
 * a bare `User::factory()->owner()` cannot create a Location.
 */
function lcPlanOwner(): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 10,
        'max_locations' => 10,
        'max_images' => 10,
        'features' => ['lead_capture' => true],
    ]);

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

/**
 * PHASE 22C — REMAINING LEGACY CLEANUP: CONTRACT PINS.
 *
 * Every symbol below was audited and found to have ACTIVE consumers, so it was
 * RETAINED rather than removed. These tests pin the behaviour that justifies
 * keeping it, so a future "cleanup" cannot delete a live contract silently.
 *
 * The one genuinely dead symbol — the `primary_branch` serialization alias —
 * was removed and is pinned as absent below.
 *
 * Helper names are prefixed: Pest shares one process across every test file.
 */

// ═══ is_primary — RETAINED (live product feature) ═══════════════════════════

test('is_primary remains a supported Location column and cast', function () {
    expect(Schema::hasColumn('locations', 'is_primary'))->toBeTrue();

    $owner = User::factory()->owner()->create();
    $location = Location::factory()->forOwner($owner)->standalone()->create(['is_primary' => true]);

    expect($location->fresh()->is_primary)->toBeTrue();
});

test('the first location of a business becomes primary automatically', function () {
    // Owner/LocationController::store sets is_primary for the first Location.
    // This is why the column cannot be dropped as "Branch residue".
    // The store enforces the account Location quota, so a plan is required.
    $owner = lcPlanOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['region_id' => $region->id]);

    $this->actingAs($owner)->post("/owner/businesses/{$business->id}/locations", [
        'name' => 'First',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'status' => 'active',
    ])->assertRedirect();

    expect(Location::where('business_id', $business->id)->firstOrFail()->is_primary)->toBeTrue();
});

test('scopePrimary selects the primary location', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $plain = Location::factory()->forOwner($owner)->forBusiness($business)->create(['is_primary' => false]);
    $primary = Location::factory()->forOwner($owner)->forBusiness($business)->create(['is_primary' => true]);

    $found = Location::primary()->pluck('id')->all();

    expect($found)->toContain($primary->id);
    expect($found)->not->toContain($plain->id);
});

// ═══ sort_order — RETAINED (live ordering) ══════════════════════════════════

test('sort_order remains a supported Location column', function () {
    expect(Schema::hasColumn('locations', 'sort_order'))->toBeTrue();

    $owner = User::factory()->owner()->create();
    $location = Location::factory()->forOwner($owner)->standalone()->create(['sort_order' => 7]);

    expect($location->fresh()->sort_order)->toBe(7);
});

test('scopeOrdered orders by sort_order', function () {
    // LocationsSection.vue sorts publicly by sort_order, and the controller
    // assigns it on create, so the column is live ordering data.
    $owner = User::factory()->owner()->create();

    $second = Location::factory()->forOwner($owner)->standalone()->create(['sort_order' => 2, 'name' => 'B']);
    $first = Location::factory()->forOwner($owner)->standalone()->create(['sort_order' => 1, 'name' => 'A']);

    $ordered = Location::where('owner_id', $owner->id)->ordered()->pluck('id')->all();

    expect($ordered)->toBe([$first->id, $second->id]);
});

// ═══ primaryLocation() — RETAINED (public + admin + owner consumers) ════════

test('the primaryLocation relationship resolves the primary location', function () {
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    Location::factory()->forOwner($owner)->forBusiness($business)->create(['is_primary' => false]);
    $primary = Location::factory()->forOwner($owner)->forBusiness($business)->create(['is_primary' => true]);

    expect($business->fresh()->primaryLocation?->id)->toBe($primary->id);
});

test('the directory resources no longer emit the dead primary_branch alias', function () {
    // `primary_branch` was emitted by both resources and read by NOTHING in
    // app/, tests/ or resources/js. It was the last dead Branch-era alias.
    $businessResource = file_get_contents(app_path('Http/Resources/BusinessDirectoryResource.php'));
    $listingResource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));

    // Compare against the emitted key, not the explanatory comment.
    expect($businessResource)->not->toContain("'primary_branch' =>");
    expect($listingResource)->not->toContain("'primary_branch' =>");

    // The replacement alias must survive.
    expect($businessResource)->toContain("'primary_location' =>");
    expect($listingResource)->toContain("'primary_location' =>");
});

test('the branches alias is RETAINED because a consumer still reads it', function () {
    // RelatedBusinesses.vue: biz.locations || biz.branches || []
    // ListingCard.vue:       props.listing.locations || props.listing.branches
    // Removing this key while those fallbacks exist would blank those cards.
    $listingResource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));
    $businessResource = file_get_contents(app_path('Http/Resources/BusinessDirectoryResource.php'));

    expect($listingResource)->toContain("'branches' =>");
    expect($businessResource)->toContain("'branches' =>");
});

// ═══ phone / whatsapp — SEMANTICS ESTABLISHED, RETAINED ════════════════════

test('locations keep phone and whatsapp as location-specific contact fields', function () {
    expect(Schema::hasColumn('locations', 'phone'))->toBeTrue();
    expect(Schema::hasColumn('locations', 'whatsapp'))->toBeTrue();

    $owner = User::factory()->owner()->create();
    $location = Location::factory()->forOwner($owner)->standalone()->create([
        'phone' => '+237600000000',
        'whatsapp' => '+237611111111',
    ]);

    expect($location->fresh()->phone)->toBe('+237600000000');
    expect($location->fresh()->whatsapp)->toBe('+237611111111');
});

test('the business directory resource carries the primary location contact numbers', function () {
    // BusinessProfile.vue resolves contactPhone / contactWhatsApp by PREFERRING
    // `primaryLocation.phone` / `.whatsapp` over Business-level contacts, so the
    // location numbers must survive serialization.
    //
    // The public route 404s unless the Business is publicly reachable, which is
    // a separate contract; this pins the payload the page actually consumes.
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    Location::factory()->forOwner($owner)->forBusiness($business)->create([
        'is_primary' => true,
        'phone' => '+237622222222',
        'whatsapp' => '+237633333333',
    ]);

    $payload = (new \App\Http\Resources\BusinessDirectoryResource(
        $business->fresh()->load('locations.city', 'locations.country', 'locations.region')
    ))->resolve();

    // `primary_location` is the live key; `primary_branch` was removed.
    expect($payload)->toHaveKey('primary_location');
    expect($payload['primary_location']['phone'] ?? null)->toBe('+237622222222');
    expect($payload['primary_location']['whatsapp'] ?? null)->toBe('+237633333333');
});
