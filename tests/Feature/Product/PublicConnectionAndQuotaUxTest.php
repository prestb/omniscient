<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-F — PUBLIC CONNECTION CONTINUITY & LISTING QUOTA UX.
 *
 * Issue A: a published Listing must never send a visitor to a known-dead
 *          organization page.
 * Issue B: a Listing owner must understand what their quota permits, while
 *          existing Listing management stays fully usable at capacity.
 */

/** An owner with an active subscription on a plan granting N listings. */
function f21SubOwner(int $maxListings = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
        'max_locations' => 3,
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

function f21PublishedListing(User $owner, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => null,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ ISSUE A — PUBLIC REACHABILITY ══════════════════════════════════════════

test('CASE A - a business-less listing exposes no organization context', function () {
    $owner = f21SubOwner();
    $listing = f21PublishedListing($owner, ['business_id' => null]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['business_id'])->toBeNull();
    // No organization to link to, and reachability is explicitly false.
    expect($props['listing']['business_publicly_reachable'])->toBeFalse();
});

test('CASE B - a business-backed listing with an active subscription is reachable', function () {
    $owner = f21SubOwner();
    $business = Business::factory()->create([
        'owner_id' => $owner->id,
        'status' => Business::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
    $listing = f21PublishedListing($owner, ['business_id' => $business->id]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['business_id'])->toBe($business->id);
    expect($props['listing']['business_slug'])->toBe($business->slug);
    // The CTA is safe to render, and the destination really is reachable.
    expect($props['listing']['business_publicly_reachable'])->toBeTrue();
    $this->get('/business/' . $business->slug)->assertOk();
});

test('CASE C - a business-backed listing whose organization is unavailable stays public', function () {
    // The organization's owner has NO active subscription, so /business 404s.
    $businessOwner = User::factory()->owner()->create();
    $business = Business::factory()->create([
        'owner_id' => $businessOwner->id,
        'status' => Business::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    // The Listing is owned by a DIFFERENT, fully subscribed account.
    $listingOwner = f21SubOwner();
    $listing = f21PublishedListing($listingOwner, ['business_id' => $business->id]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    // The Listing remains fully discoverable...
    expect($props['listing']['status'])->toBe(Listing::STATUS_PUBLISHED);
    // ...but the organization link would be dead, and the payload says so.
    expect($props['listing']['business_publicly_reachable'])->toBeFalse();

    // Proof the fix does NOT weaken Business visibility: it is still 404.
    $this->get('/business/' . $business->slug)->assertNotFound();
});

test('the organization page still requires an active subscription', function () {
    // Explicit guard against the tempting but wrong "fix" of making the
    // organization page public.
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->create([
        'owner_id' => $owner->id,
        'status' => Business::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);

    expect($owner->active_subscription)->toBeNull();
    expect($business->isPubliclyReachable())->toBeFalse();
    $this->get('/business/' . $business->slug)->assertNotFound();
});

test('reachability also requires a published, non-hidden organization', function () {
    $owner = f21SubOwner();

    $draft = Business::factory()->create([
        'owner_id' => $owner->id, 'status' => Business::STATUS_DRAFT, 'hidden_at' => null,
    ]);
    $hidden = Business::factory()->create([
        'owner_id' => $owner->id, 'status' => Business::STATUS_PUBLISHED, 'hidden_at' => now(),
    ]);

    expect($draft->isPubliclyReachable())->toBeFalse();
    expect($hidden->isPubliclyReachable())->toBeFalse();
});

test('the controller and the model agree on the reachability rule', function () {
    // One source of truth: the controller enforces what the model reports.
    $controller = file_get_contents(app_path('Http/Controllers/Public/DirectoryController.php'));

    expect($controller)->toContain('isPubliclyReachable()');
    // The old inline subscription check must be gone, so the two cannot drift.
    expect($controller)->not->toContain('$hasActiveSubscription');
});

test('the frontend never renders a dead organization link', function () {
    $source = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    // The CTA is gated on the backend's semantic field, not on business_id alone.
    expect($source)->toContain('listing.business_id && listing.business_publicly_reachable');
    // And Vue does not reconstruct subscription rules.
    expect($source)->not->toContain('active_subscription');
    expect($source)->not->toContain('is_verified');
});

// ═══ ISSUE B — LISTING QUOTA UX ═════════════════════════════════════════════

test('the listings index exposes the canonical account quota', function () {
    $owner = f21SubOwner(maxListings: 3);
    f21PublishedListing($owner);

    $props = $this->actingAs($owner)->get('/owner/listings')->assertOk()->viewData('page')['props'];

    expect($props)->toHaveKey('quota');
    expect($props['quota']['current'])->toBe(1);
    expect($props['quota']['limit'])->toBe(3);
    expect($props['quota']['can_create'])->toBeTrue();
});

test('under quota the create action remains available', function () {
    $owner = f21SubOwner(maxListings: 2);
    f21PublishedListing($owner);

    $props = $this->actingAs($owner)->get('/owner/listings')->viewData('page')['props'];

    expect($props['quota']['can_create'])->toBeTrue();
});

test('at quota the UI is told capacity is reached', function () {
    $owner = f21SubOwner(maxListings: 1);
    f21PublishedListing($owner);

    $props = $this->actingAs($owner)->get('/owner/listings')->assertOk()->viewData('page')['props'];

    expect($props['quota']['current'])->toBe(1);
    expect($props['quota']['limit'])->toBe(1);
    expect($props['quota']['can_create'])->toBeFalse();
});

test('existing listing management remains available at quota', function () {
    $owner = f21SubOwner(maxListings: 1);
    $listing = f21PublishedListing($owner);

    $props = $this->actingAs($owner)->get('/owner/listings')->viewData('page')['props'];
    expect($props['quota']['can_create'])->toBeFalse();

    // Editing, publishing and unpublishing an EXISTING Listing are unaffected.
    $this->actingAs($owner)->get("/owner/listings/{$listing->id}/edit")->assertOk();

    $this->actingAs($owner)->put("/owner/listings/{$listing->id}", [
        'type' => 'professional', 'name' => 'Renamed at quota', 'description' => 'x',
    ])->assertRedirect();

    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/unpublish")->assertRedirect();
    $this->actingAs($owner)->post("/owner/listings/{$listing->id}/publish")->assertRedirect();

    expect($listing->fresh()->name)->toBe('Renamed at quota');
});

test('the UI gates the create action on the backend entitlement, not its own maths', function () {
    $source = file_get_contents(resource_path('js/Pages/Owner/Listings/Index.vue'));

    expect($source)->toContain('quota.can_create');
    // No second quota calculation in Vue.
    expect($source)->not->toContain('max_listings');
    expect($source)->not->toContain('quota.limit - quota.current');
    // No hard-coded plan names or prices.
    expect($source)->not->toContain('Premium');
    expect($source)->not->toContain('Growth');
});

test('a business-less and a business-backed account get the same quota representation', function () {
    $soloOwner = f21SubOwner(maxListings: 3);
    f21PublishedListing($soloOwner, ['business_id' => null]);

    $groupedOwner = f21SubOwner(maxListings: 3);
    $business = Business::factory()->create(['owner_id' => $groupedOwner->id]);
    f21PublishedListing($groupedOwner, ['business_id' => $business->id]);

    $solo = $this->actingAs($soloOwner)->get('/owner/listings')->viewData('page')['props']['quota'];
    $grouped = $this->actingAs($groupedOwner)->get('/owner/listings')->viewData('page')['props']['quota'];

    // A Business grants no separate allowance and does not change the shape.
    expect($solo)->toBe($grouped);
    expect($solo['current'])->toBe(1);
});
