<?php

use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Region;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 22A — LOCATION OWNERSHIP FOUNDATION.
 *
 *     USER owns LOCATION
 *     BUSINESS optionally contextualizes LOCATION
 *     LISTING references LOCATION
 *     LOCATION may serve multiple LISTINGS
 *
 * Helper names are prefixed: Pest loads every test file into one process, and a
 * duplicate global helper fatally aborts the full suite while the focused run
 * still passes.
 */

function locOwner(int $maxLocations = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 10,
        'max_locations' => $maxLocations,
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

/** A valid geographic payload: the owner routes require country/region/city. */
function locGeo(): array
{
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    // PHASE 22 audit: countries -> regions.country_id -> cities.region_id ->
    // areas.city_id. `cities` has NO country_id column.
    $city = City::factory()->create(['region_id' => $region->id]);

    return [
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'status' => 'active',
    ];
}

function locListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ A. PROFESSIONAL CREATES A LOCATION ═════════════════════════════════════

test('a business-less professional can create a location it owns', function () {
    $owner = locOwner();

    $this->actingAs($owner)
        ->post('/owner/locations', array_merge(locGeo(), ['name' => 'Professional Workshop']))
        ->assertRedirect(route('owner.locations.index'));

    $location = Location::firstOrFail();

    expect($location->owner_id)->toBe($owner->id);
    // The whole point: no Business is required.
    expect($location->business_id)->toBeNull();
    // And none was invented to satisfy the model.
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

test('the owner locations index lists account-owned locations', function () {
    $owner = locOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Workshop']));

    $props = $this->actingAs($owner)->get('/owner/locations')->assertOk()->viewData('page')['props'];

    expect(collect($props['locations'])->pluck('name')->all())->toContain('Workshop');
});

// ═══ B/C/D. ATTACH · EDIT · DETACH ══════════════════════════════════════════

test('a professional attaches its owned location to its listing', function () {
    $owner = locOwner();
    $listing = locListing($owner);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Workshop']));
    $location = Location::firstOrFail();

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertRedirect();

    expect($listing->fresh()->location_id)->toBe($location->id);
});

test('a professional edits its own location', function () {
    $owner = locOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Old name']));
    $location = Location::firstOrFail();

    $this->actingAs($owner)
        ->put("/owner/locations/{$location->id}", array_merge(locGeo(), ['name' => 'New name']))
        ->assertRedirect(route('owner.locations.index'));

    expect($location->fresh()->name)->toBe('New name');
});

test('a professional can detach a location from its listing', function () {
    $owner = locOwner();
    $listing = locListing($owner);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Workshop']));
    $location = Location::firstOrFail();
    $listing->update(['location_id' => $location->id]);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => $listing->name,
        'location_id' => null,
    ])->assertRedirect();

    expect($listing->fresh()->location_id)->toBeNull();
    // Detaching does NOT destroy the Location.
    expect(Location::find($location->id))->not->toBeNull();
});

// ═══ E/F. BUSINESS-BACKED ═══════════════════════════════════════════════════

test('a business-backed owner creates a location with correct owner and context', function () {
    $owner = locOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), [
        'name' => 'Downtown Store',
        'business_id' => $business->id,
    ]))->assertRedirect();

    $location = Location::firstOrFail();

    expect($location->owner_id)->toBe($owner->id);
    expect($location->business_id)->toBe($business->id);
});

test('a business-backed listing attaches a location', function () {
    $owner = locOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = locListing($owner, ['business_id' => $business->id]);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), [
        'name' => 'Store', 'business_id' => $business->id,
    ]));
    $location = Location::firstOrFail();

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'business',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertRedirect();

    expect($listing->fresh()->location_id)->toBe($location->id);
});

test('the legacy business-scoped route also records the canonical owner', function () {
    // Retained for Business-backed UX, but it must not create a Location with no
    // account owner.
    $owner = locOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->post("/owner/businesses/{$business->id}/locations", array_merge(locGeo(), ['name' => 'Legacy']))
        ->assertRedirect();

    $location = Location::firstOrFail();
    expect($location->owner_id)->toBe($owner->id);
    expect($location->business_id)->toBe($business->id);
});

// ═══ G. SHARED LOCATION ═════════════════════════════════════════════════════

test('one location may serve multiple listings owned by the same account', function () {
    $owner = locOwner();
    $a = locListing($owner, ['name' => 'Listing A']);
    $b = locListing($owner, ['name' => 'Listing B']);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Shared Place']));
    $location = Location::firstOrFail();

    foreach ([$a, $b] as $listing) {
        $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
            'type' => 'professional',
            'name' => $listing->name,
            'location_id' => $location->id,
        ])->assertRedirect();
    }

    // Cardinality is Location -> many Listings. No unique constraint may prevent it.
    expect($location->listings()->count())->toBe(2);
    expect($a->fresh()->location_id)->toBe($location->id);
    expect($b->fresh()->location_id)->toBe($location->id);
});

// ═══ H. CROSS-OWNER PROTECTION ══════════════════════════════════════════════

test('a stranger cannot edit or delete another accounts location', function () {
    $owner = locOwner();
    $stranger = locOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Mine']));
    $location = Location::firstOrFail();

    $this->actingAs($stranger)
        ->put("/owner/locations/{$location->id}", array_merge(locGeo(), ['name' => 'Hijack']))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete("/owner/locations/{$location->id}")
        ->assertForbidden();

    expect($location->fresh()->name)->toBe('Mine');
});

test('a stranger cannot attach another accounts location to its listing', function () {
    $owner = locOwner();
    $stranger = locOwner();
    $strangerListing = locListing($stranger);

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Not yours']));
    $location = Location::firstOrFail();

    $this->actingAs($stranger)->put("/owner/listings/{$strangerListing->id}", [
        'type' => 'professional',
        'name' => $strangerListing->name,
        'location_id' => $location->id,
    ])->assertSessionHasErrors('location_id');

    expect($strangerListing->fresh()->location_id)->toBeNull();
});

test('a stranger cannot edit another accounts location through the owner edit page', function () {
    $owner = locOwner();
    $stranger = locOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Mine']));
    $location = Location::firstOrFail();

    $this->actingAs($stranger)
        ->get("/owner/locations/{$location->id}/edit")
        ->assertForbidden();
});

// ═══ I. NULLABLE LOCATION ═══════════════════════════════════════════════════

test('a professional listing without a location still works', function () {
    $owner = locOwner();
    $listing = locListing($owner, ['location_id' => null]);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => 'No place',
        'location_id' => null,
    ])->assertRedirect();

    expect($listing->fresh()->location_id)->toBeNull();
});

// ═══ J. QUOTA IS ACCOUNT-SCOPED ═════════════════════════════════════════════

test('location quota counts by owner not by business association', function () {
    $owner = locOwner(maxLocations: 1);

    // One account-owned Location with no Business consumes the entitlement.
    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'First']))
        ->assertRedirect();

    expect($owner->fresh()->canAdd('locations'))->toBeFalse();

    $this->actingAs($owner)
        ->post('/owner/locations', array_merge(locGeo(), ['name' => 'Second']))
        ->assertSessionHasNoErrors();
});

test('a business-less and a business-backed location consume the same quota', function () {
    $owner = locOwner(maxLocations: 1);
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    // Business-backed first...
    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), [
        'name' => 'Business place', 'business_id' => $business->id,
    ]))->assertRedirect();

    // ...then a Business-less one must be refused on the SAME account entitlement.
    expect($owner->fresh()->canAdd('locations'))->toBeFalse();
});

// ═══ K. REGRESSION — BUSINESS LOCATION FLOWS ════════════════════════════════

test('the business location index still works for its owner', function () {
    $owner = locOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->get("/owner/businesses/{$business->id}/locations")
        ->assertOk();
});

test('a stranger cannot reach another owners business location index', function () {
    $owner = locOwner();
    $stranger = locOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($stranger)
        ->get("/owner/businesses/{$business->id}/locations")
        ->assertForbidden();
});

test('the location owner relationship is canonical', function () {
    $owner = locOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(locGeo(), ['name' => 'Mine']));
    $location = Location::firstOrFail();

    expect($location->owner->id)->toBe($owner->id);
    expect($owner->locations()->count())->toBe(1);
});
