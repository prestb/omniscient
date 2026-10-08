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
 * PHASE 21C-R1 — BUSINESS REPUTATION IS A DERIVED AGGREGATE.
 *
 * This file previously asserted the OPPOSITE contract and has been rewritten
 * deliberately rather than patched. The old file asserted:
 *
 *   "two listings of the same business receive the same business aggregate"
 *   "a listing does not receive another business's reviews"
 *   "the obsolete listing-owned review relation"
 *
 * Under Listing-owned reviews, two sibling Listings no longer share a rating.
 * The Business aggregate still exists, but it is DERIVED by traversing
 * Business → Listings → Reviews, and it is presented only where organization
 * reputation is genuinely intended, never on a Listing.
 */

function aggOwner(): User
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

function aggListing(User $owner, ?Business $business = null, array $attrs = []): Listing
{
    return Listing::factory()->create(array_merge([
        'owner_id' => $owner->id,
        'business_id' => $business?->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ], $attrs));
}

// ═══ SIBLINGS NO LONGER SHARE A RATING ══════════════════════════════════════

test('two listings of the same business have independent ratings', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $a1 = aggListing($owner, $business);
    $a2 = aggListing($owner, $business);

    Review::factory()->for($a1)->approved()->rated(5)->create();
    Review::factory()->for($a2)->approved()->rated(2)->create();

    // The whole point of the migration: these are NOT the same number.
    $r1 = round((float) $a1->approvedReviews()->avg('rating'), 1);
    $r2 = round((float) $a2->approvedReviews()->avg('rating'), 1);

    expect($r1)->toBe(5.0);
    expect($r2)->toBe(2.0);
    expect($r1)->not->toBe($r2);
});

test('a listing does not inherit reviews from its sibling', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $rated = aggListing($owner, $business);
    $unrated = aggListing($owner, $business);

    Review::factory()->for($rated)->approved()->rated(5)->create();

    // Before the migration this returned the Business aggregate (1 review).
    expect($unrated->approvedReviews()->count())->toBe(0);
    expect($unrated->approvedReviews()->avg('rating'))->toBeNull();
});

// ═══ BUSINESS AGGREGATE — DERIVED, NOT OWNED ════════════════════════════════

test('business aggregate includes approved reviews from every listing', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    Review::factory()->for(aggListing($owner, $business))->approved()->rated(5)->create();
    Review::factory()->for(aggListing($owner, $business))->approved()->rated(4)->create();
    Review::factory()->for(aggListing($owner, $business))->approved()->rated(3)->create();

    expect($business->reviews()->count())->toBe(3);
    expect(round($business->averageRating(), 1))->toBe(4.0);
});

test('the business aggregate is the true mean not the mean of means', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $heavy = aggListing($owner, $business);
    $light = aggListing($owner, $business);

    // Listing A: four 5s.  Listing B: one 1.
    foreach (range(1, 4) as $i) {
        Review::factory()->for($heavy)->approved()->rated(5)->create();
    }
    Review::factory()->for($light)->approved()->rated(1)->create();

    // Mean-of-means would be (5 + 1) / 2 = 3.0. The true mean is 21/5 = 4.2.
    expect(round($business->averageRating(), 1))->toBe(4.2);
});

test('business aggregate excludes pending rejected and deleted reviews', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = aggListing($owner, $business);

    Review::factory()->for($listing)->approved()->rated(5)->create();
    Review::factory()->for($listing)->pending()->rated(1)->create();
    Review::factory()->for($listing)->rejected()->rated(1)->create();
    Review::factory()->for($listing)->approved()->rated(1)->create()->delete();

    // approvedReviews() excludes pending/rejected/deleted; reviews() is all rows.
    expect($business->approvedReviews()->count())->toBe(1);
    expect(round($business->averageRating(), 1))->toBe(5.0);
});

test('a business-less listing contributes to no business aggregate', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $backed = aggListing($owner, $business);
    $solo = aggListing($owner, null, ['business_id' => null]);

    Review::factory()->for($backed)->approved()->rated(5)->create();
    Review::factory()->for($solo)->approved()->rated(1)->create();

    // The Business sees only its own Listing.
    // approvedReviews() excludes pending/rejected/deleted; reviews() is all rows.
    expect($business->approvedReviews()->count())->toBe(1);
    expect(round($business->averageRating(), 1))->toBe(5.0);

    // The Professional keeps its own reputation.
    expect(round((float) $solo->approvedReviews()->avg('rating'), 1))->toBe(1.0);
});

test('a business review never becomes another business review', function () {
    $owner = aggOwner();
    $a = Business::factory()->create(['owner_id' => $owner->id]);
    $b = Business::factory()->create(['owner_id' => $owner->id]);

    Review::factory()->for(aggListing($owner, $a))->approved()->rated(5)->create();

    expect($a->reviews()->count())->toBe(1);
    expect($b->reviews()->count())->toBe(0);
    expect($b->averageRating())->toBeNull();
});

// ═══ DISCOVERY SORTING USES LISTING-OWNED REPUTATION ════════════════════════

test('the directory sort by rating ranks listings by their own rating', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $high = aggListing($owner, $business);
    $low = aggListing($owner, $business);

    Review::factory()->for($high)->approved()->rated(5)->create();
    Review::factory()->for($low)->approved()->rated(2)->create();

    $sorted = Listing::query()
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->orderByDesc('reviews_avg_rating')
        ->pluck('id')
        ->all();

    expect(array_search($high->id, $sorted, true))
        ->toBeLessThan(array_search($low->id, $sorted, true));
});

test('the directory sort by review count ranks listings by their own count', function () {
    $owner = aggOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $many = aggListing($owner, $business);
    $few = aggListing($owner, $business);

    Review::factory()->for($many)->approved()->rated(4)->count(3)->create();
    Review::factory()->for($few)->approved()->rated(4)->create();

    $sorted = Listing::query()
        ->withCount('reviews')
        ->orderByDesc('reviews_count')
        ->pluck('id')
        ->all();

    expect(array_search($many->id, $sorted, true))
        ->toBeLessThan(array_search($few->id, $sorted, true));
});

test('the discovery sort vocabulary resolves to listing-owned columns', function () {
    // The sort configuration must reference the LISTING-owned aggregate
    // attributes, not the retired Business-delegating relation.
    $source = file_get_contents(app_path('Support/DiscoverySort.php'));

    expect($source)->not->toContain('business_reviews_avg_rating');
    expect($source)->not->toContain('business_reviews_count');
    expect($source)->not->toContain('businessReviews');
});

test('the obsolete business-delegating relation no longer exists', function () {
    // `businessReviews()` was the hasManyThrough that made a Listing display its
    // organization's reviews. It must be gone, not merely unused.
    expect(method_exists(Listing::class, 'businessReviews'))->toBeFalse();
});
