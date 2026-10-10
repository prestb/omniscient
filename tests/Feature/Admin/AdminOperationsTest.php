<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 27 — ADMIN OPERATIONS REGRESSION COVERAGE.
 *
 * Pins the P1 work: the dashboard's canonical Listing/Location KPIs, the Business
 * deletion dependency safeguard, physical Location inspection, and the existing
 * Business moderation lifecycle. Assertions are on PERSISTED STATE and
 * AUTHORIZATION OUTCOMES, not merely on a successful response.
 *
 * Helper names are prefixed `ops`; Pest shares one process across test files.
 */

function opsAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function opsPublishedListing(array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ DASHBOARD KPIs ═════════════════════════════════════════════════════════

test('the admin dashboard reports canonical Listing and Location KPIs', function () {
    // 2 public Listings, 1 hidden, 1 draft => 4 total, 2 public, 2 not public.
    opsPublishedListing();
    opsPublishedListing();
    opsPublishedListing(['hidden_at' => now()]);
    opsPublishedListing(['status' => Listing::STATUS_DRAFT]);

    // 3 physical Locations: 2 used by Listings, 1 not.
    Location::factory()->count(2)->create();
    Location::factory()->create();

    $listings = Listing::orderBy('id')->get();
    $locations = Location::orderBy('id')->get();

    $listings[0]->update(['location_id' => $locations[0]->id]);
    $listings[1]->update(['location_id' => $locations[1]->id]);

    $stats = $this->actingAs(opsAdmin())->get('/admin/dashboard')
        ->assertOk()
        ->viewData('page')['props']['stats'];

    expect($stats['total_listings'])->toBe(4)
        ->and($stats['published_listings'])->toBe(2)
        ->and($stats['non_public_listings'])->toBe(2)
        ->and($stats['total_locations'])->toBe(3)
        ->and($stats['locations_with_listings'])->toBe(2);
});

test('the dashboard Listing KPI excludes soft-deleted Listings', function () {
    $listing = opsPublishedListing();
    $listing->delete();

    $stats = $this->actingAs(opsAdmin())->get('/admin/dashboard')
        ->assertOk()
        ->viewData('page')['props']['stats'];

    expect($stats['total_listings'])->toBe(0);
});

// ═══ BUSINESS DELETION SAFEGUARD ════════════════════════════════════════════

test('an organization whose Location is used by a Listing cannot be deleted', function () {
    $business = Business::factory()->create(['status' => 'published']);
    // The Location belongs to the organization -- the same dependency the
    // owner-side guard refuses on.
    $location = Location::factory()->create([
        'owner_id' => $business->owner_id,
        'business_id' => $business->id,
    ]);
    $listing = opsPublishedListing(['business_id' => $business->id]);
    $listing->update(['location_id' => $location->id]);

    // The owner-side policy already refuses this; admin must apply the same rule.
    $this->actingAs(opsAdmin())
        ->delete("/admin/businesses/{$business->id}")
        ->assertRedirect();

    expect(Business::find($business->id))->not->toBeNull();
});

test('an organization with no dependent Listings can be deleted', function () {
    $business = Business::factory()->create(['status' => 'published']);

    $this->actingAs(opsAdmin())
        ->delete("/admin/businesses/{$business->id}")
        ->assertRedirect(route('admin.businesses.index'));

    expect(Business::find($business->id))->toBeNull();
});

// ═══ BUSINESS MODERATION ════════════════════════════════════════════════════

test('an admin can approve, publish and suspend an organization', function () {
    $business = Business::factory()->create(['status' => 'submitted']);
    $admin = opsAdmin();

    $this->actingAs($admin)->post("/admin/businesses/{$business->id}/approve")->assertRedirect();
    expect($business->fresh()->status)->toBe(Business::STATUS_APPROVED);

    $this->actingAs($admin)->post("/admin/businesses/{$business->id}/publish")->assertRedirect();
    expect($business->fresh()->status)->toBe(Business::STATUS_PUBLISHED);

    $this->actingAs($admin)->post("/admin/businesses/{$business->id}/suspend")->assertRedirect();
    expect($business->fresh()->status)->toBe(Business::STATUS_SUSPENDED);
});

test('an admin can reject an organization', function () {
    $business = Business::factory()->create(['status' => 'submitted']);

    $this->actingAs(opsAdmin())->post("/admin/businesses/{$business->id}/reject")->assertRedirect();

    expect($business->fresh()->status)->toBe(Business::STATUS_REJECTED);
});

// ═══ PHYSICAL LOCATION INSPECTION ═══════════════════════════════════════════

test('an admin can inspect physical Locations', function () {
    $location = Location::factory()->create(['name' => 'Ops Clinic Branch']);
    opsPublishedListing()->update(['location_id' => $location->id]);

    $props = $this->actingAs(opsAdmin())->get('/admin/locations')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['stats']['total'])->toBe(1)
        ->and($props['stats']['with_listings'])->toBe(1)
        ->and($props['locations']['data'])->toHaveCount(1)
        ->and($props['locations']['data'][0]['name'])->toBe('Ops Clinic Branch')
        ->and($props['locations']['data'][0]['listings_count'])->toBe(1);
});

test('the physical Location index is forbidden to non-admins', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get('/admin/locations')->assertRedirect(route('owner.dashboard'));
});

test('the physical Location index filters by whether a Listing uses it', function () {
    $used = Location::factory()->create();
    Location::factory()->create();

    opsPublishedListing()->update(['location_id' => $used->id]);

    $props = $this->actingAs(opsAdmin())->get('/admin/locations?has_listings=no')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['locations']['data'])->toHaveCount(1)
        ->and($props['locations']['data'][0]['listings_count'])->toBe(0);
});

test('the geography reference routes remain intact', function () {
    $admin = opsAdmin();

    foreach (['/admin/locations/countries', '/admin/locations/regions', '/admin/locations/cities', '/admin/locations/areas'] as $path) {
        $this->actingAs($admin)->get($path)->assertOk();
    }
});
