<?php

use App\Http\Resources\ListingDirectoryResource;
use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 21C-R1 — LISTING-OWNED REVIEW ARCHITECTURE.
 *
 * This file is the SPECIFICATION of the migrated architecture. It replaces the
 * previous version, which specified the opposite contract and has therefore been
 * inverted deliberately rather than patched:
 *
 *   OLD (asserted here before)                     NEW (asserted here now)
 *   "the reviews table has no listing column"      listing_id exists
 *   "a review cannot exist without a business"     without a LISTING
 *   "every listing...displays the same rating"     siblings are ISOLATED
 *   "a listing cannot be told apart by reputation" they CAN be
 *   "a business-less professional cannot..."       they CAN
 *
 * Helper names are prefixed: Pest loads every test file into one process, and a
 * duplicate global helper fatally aborts the full suite while the focused run
 * still passes.
 */

function archOwner(int $maxListings = 10): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => $maxListings,
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

function archListing(?User $owner = null, ?Business $business = null, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => ($owner ?? archOwner())->id,
        'business_id' => $business?->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ SCHEMA ═════════════════════════════════════════════════════════════════

test('the reviews table has a listing column', function () {
    expect(Schema::hasColumn('reviews', 'listing_id'))->toBeTrue();
});

test('the reviews table has no business column', function () {
    // Business ownership is gone, not retained as a compatibility column.
    expect(Schema::hasColumn('reviews', 'business_id'))->toBeFalse();
});

test('a review cannot exist without a listing', function () {
    $reviewer = User::factory()->create();

    $this->expectException(QueryException::class);

    Review::create([
        'listing_id' => null,
        'user_id' => $reviewer->id,
        'rating' => 5,
        'content' => 'Orphan attempt',
        'status' => Review::STATUS_APPROVED,
    ]);
});

test('the review model exposes listing, user and moderator relationships', function () {
    expect(method_exists(Review::class, 'listing'))->toBeTrue();
    expect(method_exists(Review::class, 'user'))->toBeTrue();
    expect(method_exists(Review::class, 'approvedBy'))->toBeTrue();
    expect(method_exists(Review::class, 'replies'))->toBeTrue();

    // The Business ownership relationship must not exist.
    expect(method_exists(Review::class, 'business'))->toBeFalse();
});

test('listing exposes canonical review relationships', function () {
    expect(method_exists(Listing::class, 'reviews'))->toBeTrue();
    expect(method_exists(Listing::class, 'approvedReviews'))->toBeTrue();

    // The old Business-delegating relation must be gone.
    expect(method_exists(Listing::class, 'businessReviews'))->toBeFalse();
});

// ═══ OWNERSHIP ══════════════════════════════════════════════════════════════

test('a review belongs to its listing and its reviewer', function () {
    $owner = archOwner();
    $listing = archListing($owner);
    $reviewer = User::factory()->create();

    $review = Review::factory()->for($listing)->for($reviewer)->approved()->create();

    expect($review->listing_id)->toBe($listing->id);
    expect($review->user_id)->toBe($reviewer->id);
    expect($review->listing->id)->toBe($listing->id);
});

// ═══ SIBLING ISOLATION — the central invariant ══════════════════════════════

test('sibling listings have isolated reputations', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = archListing($owner, $business);
    $a2 = archListing($owner, $business);

    // Deliberately DIFFERENT ratings: identical values would let a
    // cross-association bug pass unnoticed.
    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a2)->approved()->rated(2)->create();

    expect((int) $a1->approvedReviews()->count())->toBe(2);
    expect(round((float) $a1->approvedReviews()->avg('rating'), 1))->toBe(5.0);

    expect((int) $a2->approvedReviews()->count())->toBe(1);
    expect(round((float) $a2->approvedReviews()->avg('rating'), 1))->toBe(2.0);
});

test('a review on one listing never appears in its siblings collection', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = archListing($owner, $business);
    $a2 = archListing($owner, $business);

    $review = Review::factory()->for($a1)->approved()->create();

    expect($a1->reviews()->pluck('id')->all())->toContain($review->id);
    expect($a2->reviews()->pluck('id')->all())->not->toContain($review->id);
    expect($a2->reviews()->count())->toBe(0);
});

test('a listing with no reviews reports no rating rather than a sibling value', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $reviewed = archListing($owner, $business);
    $bare = archListing($owner, $business);

    Review::factory()->for($reviewed)->approved()->rated(5)->create();

    expect($bare->approvedReviews()->count())->toBe(0);
    expect($bare->approvedReviews()->avg('rating'))->toBeNull();
});

// ═══ BUSINESS AGGREGATE — derived, not owned ════════════════════════════════

test('business reputation aggregates approved reviews across its listings', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = archListing($owner, $business);
    $a2 = archListing($owner, $business);

    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a2)->approved()->rated(3)->create();

    // The aggregate spans BOTH listings...
    expect($business->reviews()->count())->toBe(2);
    expect(round($business->averageRating(), 1))->toBe(4.0);

    // ...while each listing keeps its own.
    expect(round((float) $a1->approvedReviews()->avg('rating'), 1))->toBe(5.0);
    expect(round((float) $a2->approvedReviews()->avg('rating'), 1))->toBe(3.0);
});

test('business reputation excludes pending rejected and deleted reviews', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = archListing($owner, $business);

    Review::factory()->for($listing)->approved()->rated(5)->create();
    Review::factory()->for($listing)->pending()->rated(1)->create();
    Review::factory()->for($listing)->rejected()->rated(1)->create();
    Review::factory()->for($listing)->approved()->rated(1)->create()->delete();

    // approvedReviews() excludes pending/rejected/deleted; reviews() is all rows.
    expect($business->approvedReviews()->count())->toBe(1);
    expect(round($business->averageRating(), 1))->toBe(5.0);
    expect($business->reviewsCount())->toBe(1);
});

test('a business with no listings reports no reputation safely', function () {
    $business = Business::factory()->create();

    expect($business->reviews()->count())->toBe(0);
    expect($business->averageRating())->toBeNull();
    expect($business->reviewsCount())->toBe(0);
});

test('another business is unaffected by this business reviews', function () {
    $owner = archOwner();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Review::factory()->for(archListing($owner, $a))->approved()->rated(5)->create();

    expect($a->reviews()->count())->toBe(1);
    expect($b->reviews()->count())->toBe(0);
});

// ═══ BUSINESS-LESS PROFESSIONAL — mandatory ═════════════════════════════════

test('a business-less professional can receive and display reputation', function () {
    $owner = archOwner();
    $listing = archListing($owner, null, ['business_id' => null]);

    expect($listing->business_id)->toBeNull();

    $review = Review::factory()->for($listing)->approved()->rated(4)->create();

    expect($review->listing_id)->toBe($listing->id);
    expect($listing->approvedReviews()->count())->toBe(1);
    expect(round((float) $listing->approvedReviews()->avg('rating'), 1))->toBe(4.0);

    // No Business was required at any point.
    expect(Business::where('owner_id', $owner->id)->count())->toBe(0);
});

test('the business-less professional is publicly reachable with its own rating', function () {
    $owner = archOwner();
    $listing = archListing($owner, null, ['business_id' => null]);

    Review::factory()->for($listing)->approved()->rated(4)->create();

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['rating'])->toEqual(4.0);
    expect($props['listing']['reviews_count'])->toBe(1);
});

// ═══ MODERATION — unchanged semantics ═══════════════════════════════════════

test('the review lifecycle is pending, approved or rejected', function () {
    $listing = archListing();

    $pending = Review::factory()->for($listing)->pending()->create();
    expect($pending->status)->toBe(Review::STATUS_PENDING);
    expect($pending->isPending())->toBeTrue();

    $pending->approve();
    expect($pending->fresh()->status)->toBe(Review::STATUS_APPROVED);
    expect($pending->fresh()->isApproved())->toBeTrue();

    $approved = Review::factory()->for($listing)->approved()->create();
    $approved->reject();
    expect($approved->fresh()->status)->toBe(Review::STATUS_REJECTED);
});

test('only approved reviews contribute to listing reputation', function () {
    $listing = archListing();

    Review::factory()->for($listing)->approved()->rated(5)->create();
    Review::factory()->for($listing)->pending()->rated(1)->create();
    Review::factory()->for($listing)->rejected()->rated(1)->create();

    expect($listing->approvedReviews()->count())->toBe(1);
    expect(round((float) $listing->approvedReviews()->avg('rating'), 1))->toBe(5.0);
    expect($listing->reviews()->count())->toBe(3);
});

// ═══ DUPLICATE PREVENTION ═══════════════════════════════════════════════════

test('the database refuses a second review by the same user on the same listing', function () {
    $listing = archListing();
    $reviewer = User::factory()->create();

    Review::factory()->for($listing)->for($reviewer)->approved()->create();

    $this->expectException(QueryException::class);

    Review::factory()->for($listing)->for($reviewer)->approved()->create();
});

test('the same user may review two different listings independently', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = archListing($owner, $business);
    $a2 = archListing($owner, $business);
    $reviewer = User::factory()->create();

    Review::factory()->for($a1)->for($reviewer)->approved()->create();
    Review::factory()->for($a2)->for($reviewer)->approved()->create();

    // The rule is per LISTING, not per Business.
    expect(Review::where('user_id', $reviewer->id)->count())->toBe(2);
});

// ═══ DIRECTORY RESOURCE — same output contract, Listing-owned source ════════

test('the directory resource exposes the listing own rating', function () {
    $owner = archOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = archListing($owner, $business);
    $a2 = archListing($owner, $business);

    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a2)->approved()->rated(2)->create();

    $fresh = Listing::withCount('reviews')->withAvg('reviews', 'rating')->find($a1->id);
    $payload = (new ListingDirectoryResource($fresh))->resolve();

    // Output keys are unchanged; only their source moved.
    expect($payload)->toHaveKey('rating');
    expect($payload)->toHaveKey('average_rating');
    expect($payload)->toHaveKey('reviews_count');

    expect($payload['rating'])->toEqual(5.0);
    expect($payload['reviews_count'])->toBe(1);
});

// ═══ SEARCH PAYLOAD ═════════════════════════════════════════════════════════

test('the search payload derives its rating from the listing own reviews', function () {
    $listing = archListing();

    Review::factory()->for($listing)->approved()->rated(3)->create();
    Review::factory()->for($listing)->approved()->rated(5)->create();

    $payload = $listing->fresh()->toSearchableArray();

    expect($payload['rating'])->toEqual(4.0);
    expect($payload['reviews_count'])->toBe(2);
});

test('a business-less listing indexes its own rating rather than zero', function () {
    $owner = archOwner();
    $listing = archListing($owner, null, ['business_id' => null]);

    Review::factory()->for($listing)->approved()->rated(4)->create();

    $payload = $listing->fresh()->toSearchableArray();

    // The old implementation returned 0 whenever business_id was null.
    expect($payload['rating'])->toEqual(4.0);
    expect($payload['reviews_count'])->toBe(1);
});

// ═══ ROUTES ═════════════════════════════════════════════════════════════════

test('the review creation route is listing-scoped', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());

    $store = $routes->first(fn ($r) => $r->getName() === 'listing.reviews.store');
    expect($store)->not->toBeNull();
    expect($store->uri())->toContain('listing');
    expect($store->middleware())->toContain('auth');

    // No Business-scoped review creation route may remain.
    $legacy = $routes->first(fn ($r) => str_starts_with((string) $r->getName(), 'business.reviews.'));
    expect($legacy)->toBeNull();
});

test('owner review routes are listing-scoped', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());

    $index = $routes->first(fn ($r) => $r->getName() === 'owner.listings.reviews.index');
    expect($index)->not->toBeNull();
    expect($index->uri())->toBe('owner/listings/{listing}/reviews');

    // The old Business-keyed owner route is gone.
    $legacy = $routes->first(fn ($r) => str_starts_with((string) $r->getName(), 'owner.businesses.reviews.'));
    expect($legacy)->toBeNull();
});

// ═══ PUBLIC CREATION — authentication ═══════════════════════════════════════

test('a guest cannot submit a review', function () {
    $listing = archListing();

    $this->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'Great.',
    ])->assertRedirect(route('login'));

    expect(Review::count())->toBe(0);
});

test('an authenticated non-owner can submit a pending review', function () {
    $listing = archListing();
    $reviewer = User::factory()->create();

    $this->actingAs($reviewer)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'Excellent work.',
    ])->assertRedirect();

    $review = Review::firstOrFail();
    expect($review->listing_id)->toBe($listing->id);
    expect($review->user_id)->toBe($reviewer->id);
    // Moderation still gates public visibility.
    expect($review->status)->toBe(Review::STATUS_PENDING);
});

test('the listing owner cannot review their own listing', function () {
    $owner = archOwner();
    $listing = archListing($owner);

    $this->actingAs($owner)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'Self praise.',
    ])->assertSessionHasErrors('rating');

    expect(Review::count())->toBe(0);
});

test('a business-less professional owner cannot review their own listing either', function () {
    $owner = archOwner();
    $listing = archListing($owner, null, ['business_id' => null]);

    $this->actingAs($owner)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'Self praise.',
    ])->assertSessionHasErrors('rating');

    expect(Review::count())->toBe(0);
});

test('a user who owns the business but not the listing may still review it', function () {
    // The authority is listing.owner_id, NOT the Business owner.
    $businessOwner = archOwner();
    $business = Business::factory()->create(['owner_id' => $businessOwner->id]);

    $listingOwner = archOwner();
    $listing = archListing($listingOwner, $business);

    $this->actingAs($businessOwner)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'Different owner.',
    ])->assertRedirect();

    expect(Review::where('listing_id', $listing->id)->count())->toBe(1);
});

test('a user cannot submit two reviews for the same listing', function () {
    $listing = archListing();
    $reviewer = User::factory()->create();

    $this->actingAs($reviewer)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 5, 'content' => 'First.',
    ])->assertRedirect();

    $this->actingAs($reviewer)->post('/listing/' . $listing->slug . '/reviews', [
        'rating' => 1, 'content' => 'Second.',
    ])->assertSessionHasErrors('rating');

    expect(Review::where('listing_id', $listing->id)->count())->toBe(1);
});
