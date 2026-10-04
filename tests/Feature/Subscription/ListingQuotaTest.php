<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-D-R1 — LISTING & LOCATION QUOTA ENFORCEMENT.
 *
 * The Listing quota is account-scoped:
 *
 *     max_listings = Listings owned by the USER  (Listing::countFor -> owner_id)
 *
 * A Business is an optional grouping layer and must never become a hidden quota
 * owner. Creation quota applies to CREATION only - editing an existing Listing
 * must remain possible at quota.
 */

/** An owner on an explicit plan granting a given listing allowance. */
function quotaOwner(int $maxListings, int $maxLocations = 1): User
{
    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
        'max_locations' => $maxLocations,
        'features' => ['lead_capture' => true],
    ]);

    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

function createListing(User $owner, string $name, array $extra = [])
{
    return test()->actingAs($owner)->post('/owner/listings', array_merge([
        'type' => 'professional',
        'name' => $name,
        'description' => 'A description.',
    ], $extra));
}

// ── 1 & 2. Professional quota ───────────────────────────────────────────────

test('a professional under the listing quota can create a listing', function () {
    $owner = quotaOwner(maxListings: 2);

    createListing($owner, 'First Listing')->assertRedirect();

    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('a professional at the listing quota cannot create another listing', function () {
    $owner = quotaOwner(maxListings: 1);

    createListing($owner, 'First Listing')->assertRedirect();
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);

    // THE DEFECT: this used to succeed, because `plan.limit:listings` was never
    // applied to POST /owner/listings.
    $response = createListing($owner, 'Second Listing');

    expect($response->getStatusCode())->toBeIn([302, 403]);
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('a business-less listing cannot bypass the quota', function () {
    $owner = quotaOwner(maxListings: 1);

    createListing($owner, 'Solo One', ['business_id' => null])->assertRedirect();
    createListing($owner, 'Solo Two', ['business_id' => null]);

    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

// ── 3. EDIT AT QUOTA — mandatory ────────────────────────────────────────────

test('a professional at the listing quota can still edit their existing listing', function () {
    $owner = quotaOwner(maxListings: 1);

    createListing($owner, 'The Only Listing')->assertRedirect();
    $listing = Listing::firstOrFail();

    // The creation quota must NOT be applied to the edit/update route.
    $this->actingAs($owner)
        ->put("/owner/listings/{$listing->id}", [
            'type' => 'professional',
            'name' => 'Renamed Listing',
            'description' => 'Updated.',
        ])->assertRedirect();

    expect($listing->fresh()->name)->toBe('Renamed Listing');
});

test('a professional at the listing quota can still publish and unpublish', function () {
    $owner = quotaOwner(maxListings: 1);

    createListing($owner, 'The Only Listing')->assertRedirect();
    $listing = Listing::firstOrFail();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_PUBLISHED);

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/unpublish")->assertRedirect();
    expect($listing->fresh()->status)->toBe(Listing::STATUS_DRAFT);
});

// ── 4, 5, 6. Business must not create a separate allowance ──────────────────

test('a business-backed listing consumes the owners listing quota', function () {
    $owner = quotaOwner(maxListings: 1);
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    createListing($owner, 'Grouped One', ['business_id' => $business->id])->assertRedirect();
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);

    // A Business grants no extra allowance.
    createListing($owner, 'Grouped Two', ['business_id' => $business->id]);
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(1);
});

test('grouped and ungrouped listings count against the same owner quota', function () {
    $owner = quotaOwner(maxListings: 2);
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    createListing($owner, 'Grouped', ['business_id' => $business->id])->assertRedirect();
    createListing($owner, 'Independent', ['business_id' => null])->assertRedirect();

    // Total is 2, which is the account's limit.
    expect(Listing::where('owner_id', $owner->id)->count())->toBe(2);
    expect($owner->fresh()->canAdd('listings'))->toBeFalse();
});

// ── 7. Account isolation ────────────────────────────────────────────────────

test('different accounts have isolated listing quotas', function () {
    $a = quotaOwner(maxListings: 1);
    $b = quotaOwner(maxListings: 1);

    createListing($a, 'A Only')->assertRedirect();

    // B is unaffected by A's usage.
    expect($b->fresh()->canAdd('listings'))->toBeTrue();
    createListing($b, 'B Only')->assertRedirect();

    expect(Listing::where('owner_id', $a->id)->count())->toBe(1);
    expect(Listing::where('owner_id', $b->id)->count())->toBe(1);
});

test('one owners business does not affect another owners quota', function () {
    $a = quotaOwner(maxListings: 3);
    $b = quotaOwner(maxListings: 1);
    Business::factory()->create(['owner_id' => $a->id]);

    expect($b->fresh()->canAdd('listings'))->toBeTrue();
});

// ── 8. Deletion releases capacity (existing contract) ───────────────────────

test('deleting a listing releases capacity', function () {
    $owner = quotaOwner(maxListings: 1);

    createListing($owner, 'Temporary')->assertRedirect();
    $listing = Listing::firstOrFail();

    expect($owner->fresh()->canAdd('listings'))->toBeFalse();

    $this->actingAs($owner)->delete("/owner/listings/{$listing->id}")->assertRedirect();

    // countFor() excludes soft-deleted rows, so capacity returns.
    expect($owner->fresh()->canAdd('listings'))->toBeTrue();
    createListing($owner, 'Replacement')->assertRedirect();
});

// ── Canonical source, not ad-hoc arithmetic ─────────────────────────────────

test('the quota uses the canonical account-scoped count', function () {
    $source = file_get_contents(app_path('Models/Listing.php'));

    expect($source)->toContain('public static function countFor');
    expect($source)->toContain("->where('owner_id', \$ownerId)");
});

test('the listing creation route carries the canonical plan limit middleware', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($r) => $r->uri() === 'owner/listings' && in_array('POST', $r->methods()));

    expect($routes)->not->toBeNull();
    expect($routes->middleware())->toContain('plan.limit:listings');
});

test('the edit route does NOT carry a creation quota', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($r) => $r->uri() === 'owner/listings/{listing}' && in_array('PUT', $r->methods()));

    expect($routes)->not->toBeNull();
    expect($routes->middleware())->not->toContain('plan.limit:listings');
});
