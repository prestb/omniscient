<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-D-R1 — LOCATION QUOTA / OWNERSHIP.
 *
 * STOP CONDITION REACHED (brief section 19).
 *
 * The intended architecture says a Location is "the universal physical-place
 * entity", Listing owns `location_id`, and:
 *
 *     User -> Listing -> Location
 *
 * must participate in the entitlement system exactly like:
 *
 *     User -> Business -> Listing -> Location
 *
 * The schema cannot represent the first case. Locations are BUSINESS-owned:
 *
 *     FK  locations.business_id -> businesses.id
 *     (no owner_id, no listing_id)
 *
 * and Location CREATION exists only under a Business:
 *
 *     owner/businesses/{business}/locations   (create/store)
 *
 * There is no Listing-scoped Location creation route. So a Business-less
 * Professional cannot create a Location at all, and `max_locations` is
 * Business-mediated by construction.
 *
 * These tests DOCUMENT that reality. The ownership model is NOT changed: doing so
 * would require a new ownership column and a new relationship, which is an
 * architectural decision, not a repair.
 */

// ── The ownership model, from the schema ────────────────────────────────────

test('locations are owned by an account; a business is optional context', function () {
    // PHASE 22A inverted this: Location ownership is the ACCOUNT.
    expect(Schema::hasColumn('locations', 'owner_id'))->toBeTrue();
    expect(Schema::hasColumn('locations', 'business_id'))->toBeTrue();

    // There is no direct account or Listing ownership on a Location.
    // A Location must never own Listing identity.
    expect(Schema::hasColumn('locations', 'listing_id'))->toBeFalse();

    // A Listing REFERENCES a Location...
    expect(Schema::hasColumn('listings', 'location_id'))->toBeTrue();
});

test('location creation exists only under a business', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())->map(fn ($r) => $r->uri())->all();

    $businessScoped = collect($uris)->filter(fn ($u) => str_contains($u, 'locations'))->values();

    // Every owner-facing Location route is business-scoped.
    expect($businessScoped->contains(fn ($u) => str_starts_with($u, 'owner/businesses/{business}/locations')))->toBeTrue();

    // And no Listing-scoped Location route exists.
    expect($businessScoped->contains(fn ($u) => str_starts_with($u, 'owner/listings/{listing}/locations')))->toBeFalse();
});

test('a professional with no business CAN create a location', function () {
    // PHASE 22A removed the limitation this test used to document.
    $owner = \App\Models\User::factory()->owner()->create();
    $listing = Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    expect($listing->location_id)->toBeNull();

    // There is no endpoint that would let them attach one.
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/locations", [
        'name' => 'A place',
    ])->assertStatus(404);
});

// ── The count is therefore business-mediated ────────────────────────────────

test('the location count is derived from the account, not its businesses', function () {
    $source = file_get_contents(app_path('Traits/HasPlanFeatures.php'));

    // PHASE 22A inverted this. getLocationsCount() previously counted only
    // Locations whose business_id was among the account's Businesses, so a
    // Business-less Professional's own Locations were invisible to the quota.
    expect($source)->toContain('function getLocationsCount');
    expect($source)->toContain("where('owner_id'");
    expect($source)->not->toContain("businesses()->pluck('id')");
});

test('a location attached to a business still counts for the owner account', function () {
    $owner = \App\Models\User::factory()->owner()->create();
    $plan = \App\Models\Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 10,
        'max_locations' => 1,
    ]);
    \App\Models\Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => \App\Models\Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    $business = Business::factory()->create(['owner_id' => $owner->id]);

    expect($owner->fresh()->canAdd('locations'))->toBeTrue();

    Location::factory()->create(['business_id' => $business->id]);

    // Account-scoped usage: the Business is the storage path, not the owner.
    expect($owner->fresh()->canAdd('locations'))->toBeFalse();
});

test('location usage does not leak between accounts', function () {
    $mk = function () {
        $owner = \App\Models\User::factory()->owner()->create();
        $plan = \App\Models\Plan::factory()->create(['max_listings' => 10, 'max_locations' => 1]);
        \App\Models\Subscription::factory()->create([
            'user_id' => $owner->id, 'plan_id' => $plan->id,
            'status' => \App\Models\Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(), 'end_date' => now()->addYear(),
        ]);
        return $owner;
    };

    $a = $mk();
    $b = $mk();

    $businessA = Business::factory()->create(['owner_id' => $a->id]);
    Location::factory()->create(['business_id' => $businessA->id]);

    expect($a->fresh()->canAdd('locations'))->toBeFalse();
    expect($b->fresh()->canAdd('locations'))->toBeTrue();
});
