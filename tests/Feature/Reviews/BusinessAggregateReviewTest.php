<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 11 — REVIEW ATTRIBUTION INTEGRITY, Step 1.
 *
 * Reviews belong to the BUSINESS. A Listing may DISPLAY its owning Business's
 * aggregate metrics through Listing::businessReviews(). It must never receive
 * another Business's reviews, and the browse sort must keep working at SQL level.
 */

function approvedReview(Business $business, int $rating): Review
{
    return Review::create([
        'business_id' => $business->id,
        'user_id' => User::factory()->create()->id,
        'rating' => $rating,
        'content' => 'x',
        'status' => Review::STATUS_APPROVED,
    ]);
}

test('two listings of the same business receive the same business aggregate', function () {
    $business = Business::factory()->create();
    $a = Listing::factory()->forBusiness($business)->create();
    $b = Listing::factory()->forBusiness($business)->create();

    approvedReview($business, 5);
    approvedReview($business, 3);

    foreach ([$a, $b] as $listing) {
        $fresh = Listing::withCount('businessReviews')
            ->withAvg('businessReviews', 'rating')
            ->find($listing->id);

        expect($fresh->business_reviews_count)->toBe(2);
        expect(round((float) $fresh->business_reviews_avg_rating, 1))->toBe(4.0);
    }
});

test('a listing does not receive another business reviews', function () {
    $a = Business::factory()->create();
    $b = Business::factory()->create();

    $listingA = Listing::factory()->forBusiness($a)->create();
    $listingB = Listing::factory()->forBusiness($b)->create();

    approvedReview($a, 5);
    approvedReview($b, 1);
    approvedReview($b, 1);

    $freshA = Listing::withCount('businessReviews')->withAvg('businessReviews', 'rating')->find($listingA->id);
    $freshB = Listing::withCount('businessReviews')->withAvg('businessReviews', 'rating')->find($listingB->id);

    expect($freshA->business_reviews_count)->toBe(1);
    expect((float) $freshA->business_reviews_avg_rating)->toBe(5.0);

    expect($freshB->business_reviews_count)->toBe(2);
    expect((float) $freshB->business_reviews_avg_rating)->toBe(1.0);
});

test('a listing without a business receives no business aggregate', function () {
    $listing = Listing::factory()->create(['business_id' => null]);

    $fresh = Listing::withCount('businessReviews')->find($listing->id);

    expect((int) $fresh->business_reviews_count)->toBe(0);
});

test('the directory sort by rating executes and orders by the business aggregate', function () {
    $high = Business::factory()->create();
    $low = Business::factory()->create();

    Listing::factory()->forBusiness($high)->published()->create();
    Listing::factory()->forBusiness($low)->published()->create();

    approvedReview($high, 5);
    approvedReview($low, 1);

    $response = $this->get('/directory?sort=rating');

    $response->assertOk();

    $ids = collect($response->viewData('page')['props']['listings']['data'])->pluck('business_id')->all();

    // The high-rated Business's Listing sorts first.
    expect($ids[0])->toBe($high->id);
});

test('the directory sort by reviews executes and orders by the business aggregate', function () {
    $many = Business::factory()->create();
    $few = Business::factory()->create();

    Listing::factory()->forBusiness($many)->published()->create();
    Listing::factory()->forBusiness($few)->published()->create();

    approvedReview($many, 4);
    approvedReview($many, 4);
    approvedReview($few, 4);

    $response = $this->get('/directory?sort=reviews');

    $response->assertOk();

    $ids = collect($response->viewData('page')['props']['listings']['data'])->pluck('business_id')->all();

    expect($ids[0])->toBe($many->id);
});

test('business review behaviour remains intact', function () {
    $business = Business::factory()->create();

    approvedReview($business, 5);
    approvedReview($business, 3);
    Review::create([
        'business_id' => $business->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 1,
        'content' => 'pending',
        'status' => Review::STATUS_PENDING,
    ]);

    // Business::reviews() is approved-only and remains the canonical aggregate.
    expect($business->reviews()->count())->toBe(2);
    expect($business->fresh()->total_reviews)->toBe(2);
});

test('the obsolete listing-owned review relation and column are gone', function () {
    // Step 2 removed Listing::reviews(), Review::listing(), Review::scopeForListing()
    // and the reviews.listing_id column.
    expect(method_exists(Listing::class, 'reviews'))->toBeFalse();
    expect(method_exists(Listing::class, 'businessReviews'))->toBeTrue();
    expect(method_exists(Review::class, 'listing'))->toBeFalse();
    expect(method_exists(Review::class, 'scopeForListing'))->toBeFalse();

    expect(DB::selectOne(
        "select count(*) c from information_schema.columns
         where table_schema = database() and table_name = 'reviews' and column_name = 'listing_id'"
    )->c)->toBe(0);

    // Business ownership is untouched.
    expect(DB::selectOne(
        "select count(*) c from information_schema.columns
         where table_schema = database() and table_name = 'reviews' and column_name = 'business_id'"
    )->c)->toBe(1);
});
