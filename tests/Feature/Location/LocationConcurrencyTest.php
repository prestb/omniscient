<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * PHASE 22C — CONCURRENCY: DELETION vs ATTACHMENT / CREATION / REASSIGNMENT.
 *
 * The §3 integrity guard is a check-then-act sequence:
 *
 *     if (location is referenced) reject;   <-- check
 *     delete the location;                  <-- act
 *
 * Without a row lock a concurrent writer can interleave between the two, so a
 * Listing ends up pointing at a Location that is being deleted. PR #1 closes
 * this by taking `lockForUpdate()` on the Location rows inside the transaction,
 * and by rechecking after the lock is held.
 *
 * Pest runs in a single process, so these tests cannot interleave two real
 * requests. They instead verify the two properties that make the fix work:
 *
 *   1. BEHAVIOURAL — the sequential equivalents of each interleaving are
 *      rejected, and nothing is half-mutated.
 *   2. STRUCTURAL — the locking primitive is actually present on the path, so
 *      the fix cannot be silently removed.
 *
 * No test here claims to have executed genuinely simultaneous requests.
 */

function cxOwner(int $maxLocations = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 20,
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

function cxListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

function cxBusiness(User $owner): Business
{
    return Business::factory()->create(['owner_id' => $owner->id]);
}

/**
 * A COHERENT country -> region -> city triple.
 *
 * LocationFactory builds each level independently, so ids taken straight
 * from a factory-made Location are geometrically incoherent and are correctly
 * rejected by the Phase 22B coherence rule.
 */
function cxGeo(): array
{
    $country = \App\Models\Country::factory()->create();
    $region = \App\Models\Region::factory()->create(['country_id' => $country->id]);
    $city = \App\Models\City::factory()->create(['region_id' => $region->id]);

    return ['country_id' => $country->id, 'region_id' => $region->id, 'city_id' => $city->id];
}

// ═══ DELETION vs ATTACHMENT ═════════════════════════════════════════════════

test('attaching to a location deleted after validation is rejected', function () {
    // The `Rule::exists` table check can pass for a soft-deleted row, so the
    // controller must recheck under the Location row lock before writing.
    $owner = cxOwner();
    $listing = cxListing($owner);
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $location->delete();

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertSessionHasErrors('location_id');

    expect($listing->fresh()->location_id)->toBeNull();
});

test('attaching to a live location immediately before deletion blocks that deletion', function () {
    // The opposite interleaving: the attach wins the race, so the subsequent
    // deletion must be refused rather than orphaning the Listing.
    $owner = cxOwner();
    $location = Location::factory()->forOwner($owner)->standalone()->create();
    $listing = cxListing($owner);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertRedirect();

    $this->actingAs($owner)
        ->delete("/owner/locations/{$location->id}")
        ->assertStatus(422);

    expect(Location::find($location->id))->not->toBeNull();
    expect($listing->fresh()->location_id)->toBe($location->id);
});

// ═══ DELETION vs CREATION ═══════════════════════════════════════════════════

test('creating a location for a business deleted beforehand is rejected', function () {
    $owner = cxOwner();
    $business = cxBusiness($owner);
    $business->delete();

    $this->actingAs($owner)
        ->post("/owner/businesses/{$business->id}/locations", [
            'name' => 'Too late',
            'country_id' => \App\Models\Country::factory()->create()->id,
            'region_id' => \App\Models\Region::factory()->create()->id,
            'city_id' => \App\Models\City::factory()->create()->id,
            'status' => 'active',
        ]);

    // A Location must not be created against a Business that no longer exists.
    expect(Location::where('business_id', $business->id)->count())->toBe(0);
});

test('creating a location for a live business then deleting it is coherent', function () {
    $owner = cxOwner();
    $business = cxBusiness($owner);

    $country = \App\Models\Country::factory()->create();
    $region = \App\Models\Region::factory()->create(['country_id' => $country->id]);
    $city = \App\Models\City::factory()->create(['region_id' => $region->id]);

    $this->actingAs($owner)->post("/owner/businesses/{$business->id}/locations", [
        'name' => 'Store',
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'status' => 'active',
    ])->assertRedirect();

    $location = Location::where('business_id', $business->id)->firstOrFail();
    expect($location->owner_id)->toBe($owner->id);

    // Unreferenced, so the Business deletion is allowed and takes it along.
    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertRedirect();

    expect(Business::find($business->id))->toBeNull();
});

// ═══ DELETION vs REASSIGNMENT ═══════════════════════════════════════════════

test('reassigning a location to a business that was deleted is rejected', function () {
    $owner = cxOwner();
    $business = cxBusiness($owner);
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $business->delete();

    $this->actingAs($owner)->put("/owner/locations/{$location->id}", [
        'name' => 'Orphan attempt',
        'business_id' => $business->id,
        ...cxGeo(),
        'status' => 'active',
    ])->assertSessionHasErrors('business_id');

    // The contextual association must not be pointed at a deleted Business.
    expect($location->fresh()->business_id)->toBeNull();
});

test('a location keeps its owner through reassignment', function () {
    $owner = cxOwner();
    $business = cxBusiness($owner);
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($owner)->put("/owner/locations/{$location->id}", [
        'name' => 'Reassigned',
        'business_id' => $business->id,
        ...cxGeo(),
        'status' => 'active',
    ])->assertRedirect();

    // Business context changes; ownership never does.
    expect($location->fresh()->owner_id)->toBe($owner->id);
    expect($location->fresh()->business_id)->toBe($business->id);
});

test('reassignment cannot transfer ownership to another account', function () {
    $owner = cxOwner();
    $stranger = cxOwner();
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($stranger)->put("/owner/locations/{$location->id}", [
        'name' => 'Hijack',
        'owner_id' => $stranger->id,
        ...cxGeo(),
        'status' => 'active',
    ])->assertForbidden();

    expect($location->fresh()->owner_id)->toBe($owner->id);
});

// ═══ REJECTED OPERATIONS LEAVE NO PARTIAL STATE ═════════════════════════════

test('a blocked business deletion leaves every related row untouched', function () {
    $owner = cxOwner();
    $business = cxBusiness($owner);
    $used = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    $unused = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    $listing = cxListing($owner, ['business_id' => $business->id, 'location_id' => $used->id]);

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertSessionHas('error');

    expect(Business::find($business->id))->not->toBeNull();
    expect(Location::find($used->id))->not->toBeNull();
    expect(Location::find($unused->id))->not->toBeNull();
    expect($listing->fresh()->location_id)->toBe($used->id);
    expect($used->fresh()->owner_id)->toBe($owner->id);
});

// ═══ STRUCTURAL: THE LOCKING PRIMITIVE IS PRESENT ═══════════════════════════

test('the deletion and attachment paths actually take a row lock', function () {
    // A single-process test cannot interleave requests, so the fix is pinned
    // structurally: if the lock is removed, the race returns silently.
    $sources = [
        'business deletion' => file_get_contents(app_path('Http/Controllers/Owner/BusinessController.php')),
        'location deletion' => file_get_contents(app_path('Http/Controllers/Owner/LocationController.php')),
        'listing attachment' => file_get_contents(app_path('Http/Controllers/Owner/ListingController.php')),
    ];

    foreach ($sources as $label => $source) {
        // Pest's `toContain` is VARIADIC: a second argument is another needle,
        // not a message. Assert the boolean so the label is actually reported.
        expect(str_contains($source, 'lockForUpdate'))
            ->toBeTrue("The {$label} path must lock Location rows.");
        expect(str_contains($source, 'DB::transaction'))
            ->toBeTrue("The {$label} path must run transactionally.");
    }
});

test('the integrity guard is evaluated before any destructive mutation', function () {
    $source = file_get_contents(app_path('Http/Controllers/Owner/BusinessController.php'));

    // The reference check must appear BEFORE the bulk Location delete in source
    // order; otherwise a rejection would already have mutated state.
    $checkPos = strpos($source, 'locationsInUse');
    $deletePos = strpos($source, "->locations()->delete();");

    expect($checkPos)->not->toBeFalse();
    expect($deletePos)->not->toBeFalse();
    expect($checkPos)->toBeLessThan($deletePos);
});
