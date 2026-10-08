<?php

use App\Models\Area;
use App\Models\Business;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\Plan;
use App\Models\Region;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 22B — LOCATION LIFECYCLE & INTEGRITY.
 *
 *   owner_id is the SOLE authoritative ownership boundary.
 *   business_id is optional context and never an authorization check.
 *
 * Helper names are prefixed: Pest loads every test file into one process, and a
 * duplicate global helper fatally aborts the full suite while the focused run
 * still passes.
 */

function lcOwner(int $maxLocations = 10): User
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

/** A coherent country -> region -> city chain. */
function lcGeo(): array
{
    $country = Country::factory()->create();
    $region = Region::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['region_id' => $region->id]);

    return [
        'country_id' => $country->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'status' => 'active',
    ];
}

function lcListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

function lcLocation(User $owner, array $attrs = []): Location
{
    return Location::factory()->forOwner($owner)->standalone()->create($attrs);
}

// ═══ OWNERSHIP ══════════════════════════════════════════════════════════════

test('a created location is owned by the authenticated account', function () {
    $owner = lcOwner();

    $this->actingAs($owner)
        ->post('/owner/locations', array_merge(lcGeo(), ['name' => 'Mine']))
        ->assertRedirect();

    expect(Location::firstOrFail()->owner_id)->toBe($owner->id);
});

test('a submitted owner_id cannot be forged', function () {
    $owner = lcOwner();
    $victim = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Mine',
        // An attacker tries to assign ownership to someone else.
        'owner_id' => $victim->id,
    ]))->assertRedirect();

    expect(Location::firstOrFail()->owner_id)->toBe($owner->id);
});

test('a submitted business the account does not own is rejected', function () {
    $owner = lcOwner();
    $stranger = lcOwner();
    $foreignBusiness = Business::factory()->create(['owner_id' => $stranger->id]);

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Sneaky',
        'business_id' => $foreignBusiness->id,
    ]))->assertForbidden();

    expect(Location::count())->toBe(0);
});

test('cross-owner edit is rejected', function () {
    $owner = lcOwner();
    $stranger = lcOwner();
    $location = lcLocation($owner, ['name' => 'Mine']);

    $this->actingAs($stranger)
        ->put("/owner/locations/{$location->id}", array_merge(lcGeo(), ['name' => 'Hijack']))
        ->assertForbidden();

    expect($location->fresh()->name)->toBe('Mine');
});

test('cross-owner delete is rejected', function () {
    $owner = lcOwner();
    $stranger = lcOwner();
    $location = lcLocation($owner);

    $this->actingAs($stranger)->delete("/owner/locations/{$location->id}")->assertForbidden();

    expect(Location::find($location->id))->not->toBeNull();
});

test('cross-owner attach is rejected', function () {
    $owner = lcOwner();
    $stranger = lcOwner();
    $strangerListing = lcListing($stranger);
    $ownerLocation = lcLocation($owner);

    $this->actingAs($stranger)->put("/owner/listings/{$strangerListing->id}", [
        'type' => 'professional',
        'name' => $strangerListing->name,
        'location_id' => $ownerLocation->id,
    ])->assertSessionHasErrors('location_id');

    expect($strangerListing->fresh()->location_id)->toBeNull();
});

// ═══ ATTACHMENT ═════════════════════════════════════════════════════════════

test('a business-less professional attaches an owned location', function () {
    $owner = lcOwner();
    $listing = lcListing($owner);
    $location = lcLocation($owner);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertRedirect();

    expect($listing->fresh()->location_id)->toBe($location->id);
});

test('a business-backed listing attaches its own account location', function () {
    $owner = lcOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = lcListing($owner, ['business_id' => $business->id]);
    $location = lcLocation($owner, ['business_id' => null]);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'business',
        'name' => $listing->name,
        'location_id' => $location->id,
    ])->assertRedirect();

    // Business context does NOT require the Location to carry a business_id.
    expect($listing->fresh()->location_id)->toBe($location->id);
});

test('a location can be shared by multiple listings', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);

    $listings = collect(range(1, 3))->map(function ($n) use ($owner, $location) {
        $l = lcListing($owner, ['name' => "Listing {$n}"]);

        $this->actingAs($owner)->put("/owner/listings/{$l->id}", [
            'type' => 'professional',
            'name' => $l->name,
            'location_id' => $location->id,
        ])->assertRedirect();

        return $l->fresh();
    });

    expect($location->listings()->count())->toBe(3);
    expect($listings->pluck('location_id')->unique()->all())->toBe([$location->id]);
});

// ═══ DETACH LIFECYCLE ═══════════════════════════════════════════════════════

test('detaching one listing leaves its siblings and the location untouched', function () {
    $owner = lcOwner();
    $location = lcLocation($owner, ['name' => 'Shared Place']);

    $a = lcListing($owner, ['name' => 'A', 'location_id' => $location->id]);
    $b = lcListing($owner, ['name' => 'B', 'location_id' => $location->id]);

    $this->actingAs($owner)->put("/owner/listings/{$a->id}", [
        'type' => 'professional',
        'name' => 'A',
        'location_id' => null,
    ])->assertRedirect();

    // A detached; B untouched; the Location survives intact.
    expect($a->fresh()->location_id)->toBeNull();
    expect($b->fresh()->location_id)->toBe($location->id);
    expect(Location::find($location->id))->not->toBeNull();
    expect(Location::find($location->id)->name)->toBe('Shared Place');
});

// ═══ DELETION PROTECTION ════════════════════════════════════════════════════

test('a referenced location cannot be deleted', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);
    lcListing($owner, ['location_id' => $location->id]);

    $this->actingAs($owner)
        ->delete("/owner/locations/{$location->id}")
        ->assertStatus(422);

    expect(Location::find($location->id))->not->toBeNull();
});

test('the deletion error names how many listings are using it', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);
    lcListing($owner, ['location_id' => $location->id]);
    lcListing($owner, ['location_id' => $location->id]);

    $response = $this->actingAs($owner)->delete("/owner/locations/{$location->id}");

    $response->assertStatus(422);
    expect($response->exception->getMessage())->toContain('2 Listings');
});

test('an unused location can be deleted', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);

    $this->actingAs($owner)
        ->delete("/owner/locations/{$location->id}")
        ->assertRedirect(route('owner.locations.index'));

    expect(Location::find($location->id))->toBeNull();
});

test('deleting a listing never deletes its location', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);
    $listing = lcListing($owner, ['location_id' => $location->id]);

    $listing->delete();

    expect(Location::find($location->id))->not->toBeNull();
});

test('the full lifecycle ends with an unused location that can then be deleted', function () {
    $owner = lcOwner();
    $location = lcLocation($owner);

    $a = lcListing($owner, ['location_id' => $location->id]);
    $b = lcListing($owner, ['location_id' => $location->id]);
    $c = lcListing($owner, ['location_id' => $location->id]);

    // Detach B; A and C keep it.
    $this->actingAs($owner)->put("/owner/listings/{$b->id}", [
        'type' => 'professional', 'name' => $b->name, 'location_id' => null,
    ])->assertRedirect();

    expect($a->fresh()->location_id)->toBe($location->id);
    expect($c->fresh()->location_id)->toBe($location->id);

    // Remove A and C; the Location still exists.
    $a->delete();
    $c->delete();
    expect(Location::find($location->id))->not->toBeNull();
    expect($location->listings()->count())->toBe(0);

    // Now it may be deleted.
    $this->actingAs($owner)
        ->delete("/owner/locations/{$location->id}")
        ->assertRedirect();

    expect(Location::find($location->id))->toBeNull();
});

// ═══ GEOGRAPHY COHERENCE ════════════════════════════════════════════════════

test('coherent geography is accepted', function () {
    $owner = lcOwner();

    $this->actingAs($owner)
        ->post('/owner/locations', array_merge(lcGeo(), ['name' => 'Fine']))
        ->assertSessionHasNoErrors();

    expect(Location::count())->toBe(1);
});

test('a city that does not belong to the submitted region is rejected', function () {
    $owner = lcOwner();

    $countryA = Country::factory()->create();
    $regionA = Region::factory()->create(['country_id' => $countryA->id]);
    $cityA = City::factory()->create(['region_id' => $regionA->id]);

    $otherRegion = Region::factory()->create(['country_id' => Country::factory()->create()->id]);

    $this->actingAs($owner)->post('/owner/locations', [
        'name' => 'Incoherent',
        'country_id' => $countryA->id,
        'region_id' => $otherRegion->id,   // region belongs elsewhere
        'city_id' => $cityA->id,           // city belongs to regionA
        'status' => 'active',
    ])->assertSessionHasErrors('city_id');

    expect(Location::count())->toBe(0);
});

test('a region that does not belong to the submitted country is rejected', function () {
    $owner = lcOwner();

    $countryA = Country::factory()->create();
    $countryB = Country::factory()->create();
    $regionOfB = Region::factory()->create(['country_id' => $countryB->id]);
    $cityOfB = City::factory()->create(['region_id' => $regionOfB->id]);

    $this->actingAs($owner)->post('/owner/locations', [
        'name' => 'Incoherent',
        'country_id' => $countryA->id,   // does not own regionOfB
        'region_id' => $regionOfB->id,
        'city_id' => $cityOfB->id,
        'status' => 'active',
    ])->assertSessionHasErrors('region_id');

    expect(Location::count())->toBe(0);
});

test('an area that does not belong to the submitted city is rejected', function () {
    $owner = lcOwner();
    $geo = lcGeo();

    // No AreaFactory exists in this repository, so the row is created directly.
    $otherCity = City::factory()->create(['region_id' => Region::factory()->create()->id]);
    $foreignArea = Area::create([
        'city_id' => $otherCity->id,
        'name' => 'Foreign Area',
        'is_active' => true,
    ]);

    $this->actingAs($owner)->post('/owner/locations', array_merge($geo, [
        'name' => 'Incoherent',
        'area_id' => $foreignArea->id,
    ]))->assertSessionHasErrors('area_id');

    expect(Location::count())->toBe(0);
});

// ═══ COORDINATE INTEGRITY ═══════════════════════════════════════════════════

test('both coordinates absent is accepted', function () {
    $owner = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'No coords',
    ]))->assertSessionHasNoErrors();
});

test('both coordinates valid is accepted', function () {
    $owner = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'With coords',
        'latitude' => 4.0511,
        'longitude' => 9.7679,
    ]))->assertSessionHasNoErrors();
});

test('latitude without longitude is rejected', function () {
    $owner = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Half a pair',
        'latitude' => 4.0511,
        'longitude' => null,
    ]))->assertSessionHasErrors('longitude');
});

test('longitude without latitude is rejected', function () {
    $owner = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Half a pair',
        'latitude' => null,
        'longitude' => 9.7679,
    ]))->assertSessionHasErrors('latitude');
});

test('out of range coordinates are rejected', function () {
    $owner = lcOwner();

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Too far north',
        'latitude' => 91,
        'longitude' => 9.7679,
    ]))->assertSessionHasErrors('latitude');

    $this->actingAs($owner)->post('/owner/locations', array_merge(lcGeo(), [
        'name' => 'Too far east',
        'latitude' => 4.0511,
        'longitude' => 181,
    ]))->assertSessionHasErrors('longitude');
});

// ═══ HOURS ══════════════════════════════════════════════════════════════════

test('a business-less professional can manage the hours of its own location', function () {
    // PHASE 22B resolved this: hours authorization is LOCATION-owner based, so
    // a Location with business_id = NULL is no longer a blocker.
    $owner = lcOwner();
    $location = lcLocation($owner);          // business_id = NULL
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    // The route still names a Business for navigation; ownership of the
    // LOCATION is what authorizes.
    $this->actingAs($owner)
        ->get("/owner/businesses/{$business->id}/locations/{$location->id}/hours")
        ->assertOk();
});

test('a stranger cannot reach another accounts location hours', function () {
    $owner = lcOwner();
    $stranger = lcOwner();
    $location = lcLocation($owner);
    $strangerBusiness = Business::factory()->create(['owner_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->get("/owner/businesses/{$strangerBusiness->id}/locations/{$location->id}/hours")
        ->assertForbidden();
});

// ═══ QUOTA ══════════════════════════════════════════════════════════════════

test('attachment and sharing do not consume location quota', function () {
    $owner = lcOwner(maxLocations: 1);
    $location = lcLocation($owner);

    expect($owner->fresh()->canAdd('locations'))->toBeFalse();

    // Sharing it across three Listings consumes nothing extra.
    foreach (range(1, 3) as $n) {
        $listing = lcListing($owner, ['name' => "L{$n}"]);
        $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
            'type' => 'professional', 'name' => $listing->name,
            'location_id' => $location->id,
        ])->assertRedirect();
    }

    expect($owner->fresh()->getCurrentUsage('locations'))->toBe(1);
});

test('detaching does not free quota and deleting does', function () {
    $owner = lcOwner(maxLocations: 1);
    $location = lcLocation($owner);
    $listing = lcListing($owner, ['location_id' => $location->id]);

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional', 'name' => $listing->name, 'location_id' => null,
    ])->assertRedirect();

    // The Location row still exists, so capacity is still consumed.
    expect($owner->fresh()->canAdd('locations'))->toBeFalse();

    $this->actingAs($owner)->delete("/owner/locations/{$location->id}")->assertRedirect();

    expect($owner->fresh()->canAdd('locations'))->toBeTrue();
});

test('a refused deletion does not alter quota', function () {
    $owner = lcOwner(maxLocations: 1);
    $location = lcLocation($owner);
    lcListing($owner, ['location_id' => $location->id]);

    $this->actingAs($owner)->delete("/owner/locations/{$location->id}")->assertStatus(422);

    expect($owner->fresh()->getCurrentUsage('locations'))->toBe(1);
});

// ═══ PUBLIC SAFETY ══════════════════════════════════════════════════════════

test('a public listing is safe with no location', function () {
    $owner = lcOwner();
    $listing = lcListing($owner, ['status' => Listing::STATUS_PUBLISHED, 'location_id' => null]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['id'])->toBe($listing->id);
});

test('a public listing is safe with a standalone location', function () {
    $owner = lcOwner();
    $location = lcLocation($owner, ['business_id' => null]);
    $listing = lcListing($owner, [
        'status' => Listing::STATUS_PUBLISHED,
        'location_id' => $location->id,
    ]);

    $this->get('/listing/' . $listing->slug)->assertOk();
});

test('a public listing is safe with a business-backed location', function () {
    $owner = lcOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $location = lcLocation($owner, ['business_id' => $business->id]);
    $listing = lcListing($owner, [
        'status' => Listing::STATUS_PUBLISHED,
        'business_id' => $business->id,
        'location_id' => $location->id,
    ]);

    $this->get('/listing/' . $listing->slug)->assertOk();
});
