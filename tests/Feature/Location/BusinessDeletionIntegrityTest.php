<?php

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
 * PHASE 22C — BUSINESS DELETION INTEGRITY + PROFESSIONAL LOCATION HOURS.
 *
 * §3 is the mandatory precondition: Business deletion must not bypass the
 * Location in-use protection that the explicit Location endpoints enforce.
 *
 * §7 closes the Professional hours journey: hours routes no longer require a
 * Business, and authorization still resolves through `location.owner_id`.
 *
 * Helper names are prefixed (Pest shares one process across every test file).
 */

function bcOwner(int $maxLocations = 10): User
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

function bcListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => Listing::STATUS_DRAFT,
        'hidden_at' => null,
    ], $attrs));
}

function bcBusiness(User $owner): Business
{
    return Business::factory()->create(['owner_id' => $owner->id]);
}

// ═══ §3 BUSINESS DELETION INTEGRITY ═════════════════════════════════════════

test('a business with no locations at all can be deleted', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertRedirect();

    expect(Business::find($business->id))->toBeNull();
});

test('a business whose locations are all unused can be deleted', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $unused = Location::factory()->forOwner($owner)->forBusiness($business)->create();

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertRedirect();

    expect(Business::find($business->id))->toBeNull();
    // Unused Locations follow the business removal.
    expect(Location::find($unused->id))->toBeNull();
});

test('deleting a business is REJECTED when one location is in use', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    // Nothing destructive happened.
    expect(Business::find($business->id))->not->toBeNull();
    expect(Location::find($location->id))->not->toBeNull();
});

test('the rejection message names the number of affected listings', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);
    bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

    $response = $this->actingAs($owner)->delete("/owner/businesses/{$business->id}");

    $response->assertRedirect();
    $message = session('error');
    expect($message)->toContain('2');
});

test('multiple referenced locations block deletion and are all preserved', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);

    $locations = collect(range(1, 3))->map(function ($n) use ($owner, $business) {
        $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
        bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

        return $location;
    });

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertSessionHas('error');

    expect(Business::find($business->id))->not->toBeNull();

    foreach ($locations as $location) {
        expect(Location::find($location->id))->not->toBeNull();
    }
});

test('a mixture of used and unused locations blocks the whole deletion', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);

    $used = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    $unused = Location::factory()->forOwner($owner)->forBusiness($business)->create();

    bcListing($owner, ['business_id' => $business->id, 'location_id' => $used->id]);

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertSessionHas('error');

    // The unused Location must NOT have been removed as a partial mutation.
    expect(Business::find($business->id))->not->toBeNull();
    expect(Location::find($used->id))->not->toBeNull();
    expect(Location::find($unused->id))->not->toBeNull();
});

test('listing location references survive a rejected business deletion', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    $listing = bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

    $this->actingAs($owner)->delete("/owner/businesses/{$business->id}");

    expect($listing->fresh()->location_id)->toBe($location->id);
});

test('location ownership is unchanged by a rejected business deletion', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

    $this->actingAs($owner)->delete("/owner/businesses/{$business->id}");

    expect($location->fresh()->owner_id)->toBe($owner->id);
});

test('a stranger cannot delete another owners business', function () {
    $owner = bcOwner();
    $stranger = bcOwner();
    $business = bcBusiness($owner);

    $this->actingAs($stranger)
        ->delete("/owner/businesses/{$business->id}")
        ->assertForbidden();

    expect(Business::find($business->id))->not->toBeNull();
});

test('after detaching the listing the business can be deleted', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();
    $listing = bcListing($owner, ['business_id' => $business->id, 'location_id' => $location->id]);

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertSessionHas('error');

    // Detach explicitly, then the operation becomes safe.
    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'business',
        'name' => $listing->name,
        'location_id' => null,
    ])->assertRedirect();

    $this->actingAs($owner)
        ->delete("/owner/businesses/{$business->id}")
        ->assertRedirect();

    expect(Business::find($business->id))->toBeNull();
});

// ═══ §7 PROFESSIONAL LOCATION HOURS ═════════════════════════════════════════

test('a business-less professional can open the hours interface for its owned location', function () {
    $owner = bcOwner();
    // business_id = NULL: the whole point of the owner-scoped route.
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($owner)
        ->get("/owner/locations/{$location->id}/hours")
        ->assertOk();
});

test('a professional can persist an hours update for a business-less location', function () {
    $owner = bcOwner();
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($owner)->post("/owner/locations/{$location->id}/hours/batch", [
        'hours' => [
            ['day_of_week' => 1, 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '17:00'],
            ['day_of_week' => 2, 'is_closed' => true, 'opens_at' => null, 'closes_at' => null],
        ],
    ])->assertRedirect();

    $monday = LocationHour::where('location_id', $location->id)
        ->where('day_of_week', 1)
        ->first();

    expect($monday)->not->toBeNull();
    expect($monday->is_closed)->toBeFalsy();
});

test('a stranger cannot reach another accounts owner-scoped hours', function () {
    $owner = bcOwner();
    $stranger = bcOwner();
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($stranger)
        ->get("/owner/locations/{$location->id}/hours")
        ->assertForbidden();
});

test('a stranger cannot post hours to another accounts location', function () {
    $owner = bcOwner();
    $stranger = bcOwner();
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($stranger)->post("/owner/locations/{$location->id}/hours/batch", [
        'hours' => [
            ['day_of_week' => 1, 'is_closed' => false, 'opens_at' => '08:00', 'closes_at' => '17:00'],
        ],
    ])->assertForbidden();

    expect(LocationHour::where('location_id', $location->id)->count())->toBe(0);
});

test('the business-scoped hours route still works for a business-backed location', function () {
    $owner = bcOwner();
    $business = bcBusiness($owner);
    $location = Location::factory()->forOwner($owner)->forBusiness($business)->create();

    $this->actingAs($owner)
        ->get("/owner/businesses/{$business->id}/locations/{$location->id}/hours")
        ->assertOk();
});

test('a forged business id cannot bypass location ownership', function () {
    // A stranger owns a Business and names it in the route while pointing at
    // another account's Location. Ownership must still be the boundary.
    $owner = bcOwner();
    $stranger = bcOwner();
    $strangerBusiness = bcBusiness($stranger);
    $location = Location::factory()->forOwner($owner)->standalone()->create();

    $this->actingAs($stranger)
        ->get("/owner/businesses/{$strangerBusiness->id}/locations/{$location->id}/hours")
        ->assertForbidden();
});
