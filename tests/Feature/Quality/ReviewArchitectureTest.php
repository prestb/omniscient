<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 19F — REPUTATION & REVIEW ARCHITECTURE AUDIT.
 *
 * These tests ESTABLISH THE EXISTING CONTRACT. They do not assert a desired
 * architecture, and no production code was changed to write them.
 *
 * Findings recorded here:
 *   1. `reviews` has NO `listing_id` column. Ownership is Business-only.
 *   2. `reviews.business_id` is NOT NULL, so a Business-less Listing can never
 *      receive a Review.
 *   3. Every Listing under a Business displays the SAME rating and count,
 *      derived from the Business's aggregate.
 */

function revBusiness(): Business
{
    return Business::factory()->published()->create(['hidden_at' => null]);
}

// ── 1. The schema is Business-owned, with no Listing linkage ────────────────

test('the reviews table has no listing column', function () {
    expect(Schema::hasColumn('reviews', 'business_id'))->toBeTrue();
    expect(Schema::hasColumn('reviews', 'listing_id'))->toBeFalse();
    expect(Schema::hasColumn('reviews', 'location_id'))->toBeFalse();
    expect(Schema::hasColumn('reviews', 'service_id'))->toBeFalse();
});

test('a review cannot exist without a business', function () {
    $reviewer = User::factory()->create();

    // business_id is NOT NULL at the database level.
    $this->expectException(\Illuminate\Database\QueryException::class);

    Review::create([
        'business_id' => null,
        'user_id' => $reviewer->id,
        'rating' => 5,
        'content' => 'Orphan attempt',
        'status' => Review::STATUS_APPROVED,
    ]);
});

test('the review model exposes only business, user and moderator relationships', function () {
    $review = new Review();

    expect(method_exists($review, 'business'))->toBeTrue();
    expect(method_exists($review, 'user'))->toBeTrue();
    expect(method_exists($review, 'approvedBy'))->toBeTrue();
    expect(method_exists($review, 'replies'))->toBeTrue();

    // There is no Listing or Location relationship to expose.
    expect(method_exists($review, 'listing'))->toBeFalse();
    expect(method_exists($review, 'location'))->toBeFalse();
});

// ── 2. Reviews are created against a Business ───────────────────────────────

test('the only review creation route is business-scoped', function () {
    $source = file_get_contents(base_path('routes/web.php'));

    expect($source)->toContain("Route::prefix('business/{business}/reviews')");
    // No Listing-scoped review route exists.
    expect($source)->not->toContain("listing/{listing}/reviews");
    expect($source)->not->toContain("listing/{listing}/review'");
});

// ── 3. Multi-Listing: one Business reputation, shared by every Listing ──────

test('every listing under a business displays the same business rating', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();

    $a1 = Listing::factory()->forBusiness($business)->published()->create(['name' => 'A1']);
    $a2 = Listing::factory()->forBusiness($business)->published()->create(['name' => 'A2']);
    $a3 = Listing::factory()->forBusiness($business)->published()->create(['name' => 'A3']);

    // ONE review, attached to the BUSINESS.
    Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 5,
        'content' => 'Excellent work on the A1 job.',
        'status' => Review::STATUS_APPROVED,
    ]);

    $ratings = collect([$a1, $a2, $a3])->map(function (Listing $l) {
        $fresh = Listing::withCount('businessReviews')
            ->withAvg('businessReviews', 'rating')
            ->find($l->id);

        return [
            'rating' => round((float) $fresh->business_reviews_avg_rating, 1),
            'count' => (int) $fresh->business_reviews_count,
        ];
    });

    // A2 and A3 report the review that was written about A1's work.
    expect($ratings->pluck('rating')->unique()->all())->toBe([5.0]);
    expect($ratings->pluck('count')->unique()->all())->toBe([1]);
});

test('a listing cannot be told apart from its siblings by reputation', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();

    $a1 = Listing::factory()->forBusiness($business)->published()->create(['name' => 'A1']);
    $a2 = Listing::factory()->forBusiness($business)->published()->create(['name' => 'A2']);

    Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 4,
        'content' => 'Review of A1 only.',
        'status' => Review::STATUS_APPROVED,
    ]);

    $r1 = round((float) Listing::withAvg('businessReviews', 'rating')->find($a1->id)->business_reviews_avg_rating, 1);
    $r2 = round((float) Listing::withAvg('businessReviews', 'rating')->find($a2->id)->business_reviews_avg_rating, 1);

    expect($r1)->toBe($r2);
    // The review body names A1, but A2's reputation is indistinguishable.
    expect(Review::first()->content)->toContain('A1');
});

test('the listing accessors read the business aggregate', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 3,
        'content' => 'Average.',
        'status' => Review::STATUS_APPROVED,
    ]);

    // CORRECTED: `reviews_count` / `average_rating` are NOT Listing model
    // accessors. Every real code path supplies them via withCount/withAvg on
    // the `businessReviews` relation (see ListingController and the directory
    // query). Reading them off a bare model returns null, which is itself part
    // of the contract worth recording.
    expect(Listing::find($listing->id)->reviews_count)->toBeNull();

    $fresh = Listing::withCount('businessReviews')
        ->withAvg('businessReviews', 'rating')
        ->find($listing->id);

    expect((int) $fresh->business_reviews_count)->toBe(1);
    expect(round((float) $fresh->business_reviews_avg_rating, 1))->toBe(3.0);
});

test('the search index derives its rating from the business relation', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 2,
        'content' => 'Poor.',
        'status' => Review::STATUS_APPROVED,
    ]);

    // A third, independent rating source: toSearchableArray() queries
    // businessReviews()->avg('rating') directly for Meilisearch.
    $payload = Listing::find($listing->id)->toSearchableArray();

    expect((float) $payload['rating'])->toBe(2.0);
    expect((int) $payload['reviews_count'])->toBe(1);
});

// ── 4. Business-less Professional: no reputation path at all ────────────────

test('a business-less professional cannot receive or display reputation', function () {
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
    ]);

    $fresh = Listing::withCount('businessReviews')
        ->withAvg('businessReviews', 'rating')
        ->find($listing->id);

    // No Business -> no reviews -> no rating, and the schema forbids creating one.
    expect($listing->business_id)->toBeNull();
    expect($fresh->business_reviews_count)->toBe(0);
    expect($fresh->business_reviews_avg_rating)->toBeNull();
});

test('the business-less professional is still publicly reachable despite having no reputation', function () {
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
    ]);

    $props = $this->get('/listing/' . $listing->slug)->assertOk()->viewData('page')['props'];

    expect($props['listing']['business_id'])->toBeNull();
    // Absent reputation is expressed as null/0, not as an error.
    expect($props['listing']['reviews_count'])->toBe(0);
});

// ── 5. Status / moderation ──────────────────────────────────────────────────

test('the review lifecycle is pending, approved or rejected', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();

    $review = Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 5,
        'content' => 'Good.',
        'status' => Review::STATUS_PENDING,
    ]);

    expect($review->status)->toBe(Review::STATUS_PENDING);
    expect($review->isPending())->toBeTrue();
    expect($review->isApproved())->toBeFalse();

    $review->update(['status' => Review::STATUS_APPROVED, 'approved_at' => now()]);
    expect($review->fresh()->isApproved())->toBeTrue();
});

// ── 6. Discovery reads the Business aggregate ───────────────────────────────

test('the directory resource exposes the business rating on a listing', function () {
    $business = revBusiness();
    $reviewer = User::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    Review::create([
        'business_id' => $business->id,
        'user_id' => $reviewer->id,
        'rating' => 4,
        'content' => 'Solid.',
        'status' => Review::STATUS_APPROVED,
    ]);

    $fresh = Listing::withCount('businessReviews')->withAvg('businessReviews', 'rating')->find($listing->id);
    $payload = (new \App\Http\Resources\ListingDirectoryResource($fresh))->resolve();

    expect((float) $payload['rating'])->toBe(4.0);
    expect((int) $payload['reviews_count'])->toBe(1);

    // The resource documents this as the owning Business's metric.
    $resource = file_get_contents(app_path('Http/Resources/ListingDirectoryResource.php'));
    expect($resource)->toContain('business_reviews_avg_rating');
});

test('the public listing page loads the business aggregate, not a listing aggregate', function () {
    $source = file_get_contents(app_path('Http/Controllers/Public/ListingController.php'));

    expect($source)->toContain("withCount(['businessReviews'");
    expect($source)->toContain("withAvg('businessReviews'");
});
