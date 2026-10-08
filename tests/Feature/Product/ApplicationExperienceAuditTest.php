<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21D — APPLICATION-WIDE UX / PRODUCT EXPERIENCE ALIGNMENT.
 *
 * Guards the journeys corrected in this phase, and the product invariants that
 * the UI must keep telling the truth about:
 *
 *   Review -> Listing -> optional Business
 *
 * Helper names are prefixed: Pest loads every test file into one process, and a
 * duplicate global helper fatally aborts the full suite while the focused run
 * still passes.
 */

function uxdOwner(): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 10,
        'max_locations' => 3,
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

function uxdListing(?User $owner = null, ?Business $business = null, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => ($owner ?? uxdOwner())->id,
        'business_id' => $business?->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ P0 — THE PUBLIC REVIEWS INDEX ══════════════════════════════════════════

test('the public reviews index works for a business-less professional listing', function () {
    // The page previously dereferenced `business.slug` / `business.name` /
    // `business.average_rating`, all NULL for an independent Listing.
    $owner = uxdOwner();
    $listing = uxdListing($owner, null, ['business_id' => null]);

    Review::factory()->for($listing)->approved()->rated(4)->create();

    $response = $this->get('/listing/' . $listing->slug . '/reviews')->assertOk();
    $props = $response->viewData('page')['props'];

    expect($props['listing']['id'])->toBe($listing->id);
    expect($props['business'])->toBeNull();
    expect($props['rating'])->toEqual(4.0);
    expect($props['reviewsCount'])->toBe(1);
});

test('the public reviews index reports the listings own rating, not a siblings', function () {
    $owner = uxdOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = uxdListing($owner, $business);
    $a2 = uxdListing($owner, $business);

    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a2)->approved()->rated(2)->create();

    $p1 = $this->get('/listing/' . $a1->slug . '/reviews')->assertOk()->viewData('page')['props'];
    $p2 = $this->get('/listing/' . $a2->slug . '/reviews')->assertOk()->viewData('page')['props'];

    expect($p1['rating'])->toEqual(5.0);
    expect($p2['rating'])->toEqual(2.0);
    expect($p1['rating'])->not->toBe($p2['rating']);
});

test('the public reviews index exposes optional business context when present', function () {
    $owner = uxdOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = uxdListing($owner, $business);

    $props = $this->get('/listing/' . $listing->slug . '/reviews')->assertOk()->viewData('page')['props'];

    expect($props['business']['id'])->toBe($business->id);
});

test('the public reviews page never builds a business-scoped review url', function () {
    // The sort control posted to /business/{id}/reviews, a route deleted in 21C-R1.
    $source = file_get_contents(resource_path('js/Pages/Public/Reviews/Index.vue'));

    expect($source)->toContain('/listing/${props.listing.slug}/reviews');
    expect($source)->not->toContain('/business/${props.business.id}/reviews');
    // The reviewed entity is the Listing.
    expect($source)->toContain('Reviews for {{ listing.name }}');
    expect($source)->not->toContain('business.average_rating');
    expect($source)->not->toContain('business.slug');
});

// ═══ RETIRED ROUTES MUST NOT BE ACTIVE ══════════════════════════════════════

test('no business-scoped review route is active', function () {
    $names = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($r) => (string) $r->getName())
        ->filter();

    expect($names->filter(fn ($n) => str_starts_with($n, 'business.reviews.')))->toBeEmpty();
    expect($names->filter(fn ($n) => str_starts_with($n, 'owner.businesses.reviews.')))->toBeEmpty();
});

test('the canonical review routes are listing-scoped', function () {
    $names = collect(app('router')->getRoutes()->getRoutes())->map(fn ($r) => (string) $r->getName());

    expect($names)->toContain('listing.reviews.index');
    expect($names)->toContain('listing.reviews.store');
    expect($names)->toContain('owner.listings.reviews.index');
});

test('no active branch route remains', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())->map(fn ($r) => $r->uri());

    expect($uris->filter(fn ($u) => str_contains($u, 'branch')))->toBeEmpty();
});

// ═══ HOME — REAL DATA, NO FABRICATED PROOF ══════════════════════════════════

test('the home page renders and reports only real statistics', function () {
    uxdListing();

    $response = $this->get('/')->assertOk();
    $props = $response->viewData('page')['props'];

    // Stats, where present, must be derived from the database.
    if (isset($props['stats'])) {
        expect($props['stats'])->toBeArray();
    }

    // No unsupported social proof.
    $home = file_get_contents(resource_path('js/Pages/Public/Home.vue'));
    foreach (['Trusted by', 'thousands of', '#1 ', 'Guaranteed', 'market leader'] as $claim) {
        expect($home)->not->toContain($claim);
    }
});

// ═══ TERMINOLOGY — PAID IS NOT TRUST ════════════════════════════════════════

test('paid promotion is never presented as verification on the listing page', function () {
    $profile = file_get_contents(resource_path('js/Pages/Public/ListingProfile.vue'));

    foreach (['Verified', 'Trusted', 'Guaranteed', 'blue check'] as $claim) {
        expect($profile)->not->toContain($claim);
    }
});

test('the rating summary distinguishes listing reputation from business reputation', function () {
    $summary = file_get_contents(resource_path('js/Components/Public/ui/RatingSummary.vue'));

    // One primitive, two truthful labels.
    expect($summary)->toContain("owner:");
    expect($summary)->toContain('ownerLabel');
    expect($summary)->not->toContain('Business rating: ');
});

// ═══ OWNER EXPERIENCE — BUSINESS IS OPTIONAL ════════════════════════════════

test('the owner review page guards optional organization context', function () {
    $show = file_get_contents(resource_path('js/Pages/Owner/Reviews/Show.vue'));

    // The organization card must not render for an independent Professional.
    expect($show)->toContain('<div v-if="business"');
});

test('the owner listing review index is listing-scoped', function () {
    $owner = uxdOwner();
    $listing = uxdListing($owner, null, ['business_id' => null]);

    Review::factory()->for($listing)->approved()->rated(5)->create();

    $props = $this->actingAs($owner)
        ->get("/owner/listings/{$listing->id}/reviews")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['listing']['id'])->toBe($listing->id);
    expect($props['business'])->toBeNull();
});
