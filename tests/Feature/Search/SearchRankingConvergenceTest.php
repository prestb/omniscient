<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\User;
use App\Support\DiscoverySort;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 13 — SEARCH RANKING & SORT CONVERGENCE.
 *
 * These tests assert the search PAYLOAD and the SORT VOCABULARY directly, so
 * they do not depend on a live Meilisearch index or on pre-existing data.
 */

// ── Payload ─────────────────────────────────────────────────────────────────

test('the listing payload exposes rating and review count', function () {
    $listing = Listing::factory()->create(['business_id' => null]);

    $payload = $listing->toSearchableArray();

    expect($payload)->toHaveKey('rating');
    expect($payload)->toHaveKey('reviews_count');
    expect($payload)->toHaveKey('is_featured_rank');
});

test('a business-less listing indexes zero semantics without failing', function () {
    $listing = Listing::factory()->create(['business_id' => null]);

    $payload = $listing->toSearchableArray();

    expect($payload['rating'])->toBe(0.0);
    expect($payload['reviews_count'])->toBe(0);
    expect($payload['business_id'])->toBeNull();
});

test('a business-owned listing indexes its owning business review aggregate', function () {
    $business = Business::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->create();

    $payload = $listing->toSearchableArray();

    // Reviews are Business-owned; the Listing publishes its organization's
    // aggregate, never a Listing-owned rating.
    expect($payload['reviews_count'])->toBe((int) $business->total_reviews);
    expect($payload)->toHaveKey('rating');
});

test('featured state is indexed as an explicit numeric ranking signal', function () {
    $featured = Listing::factory()->create(['business_id' => null, 'is_featured' => true]);
    $plain = Listing::factory()->create(['business_id' => null, 'is_featured' => false]);

    expect($featured->toSearchableArray()['is_featured_rank'])->toBe(1);
    expect($featured->toSearchableArray()['is_featured'])->toBeTrue();
    expect($plain->toSearchableArray()['is_featured_rank'])->toBe(0);
});

// ── Sort vocabulary ─────────────────────────────────────────────────────────

test('the canonical sort vocabulary is exposed identically for both engines', function () {
    expect(DiscoverySort::keys())->toBe([
        DiscoverySort::RELEVANCE,
        DiscoverySort::RATING,
        DiscoverySort::REVIEWS,
        DiscoverySort::NEWEST,
        DiscoverySort::FEATURED,
    ]);
});

test('both engines map every canonical sort to a concrete ordering', function (string $canonical) {
    // SQL must always produce an ordering for a non-relevance sort.
    if ($canonical !== DiscoverySort::RELEVANCE) {
        expect(DiscoverySort::meilisearchOrder($canonical))->not->toBeEmpty();
        expect(DiscoverySort::sqlOrder($canonical))->not->toBeEmpty();
    }
})->with([
    DiscoverySort::RATING,
    DiscoverySort::REVIEWS,
    DiscoverySort::NEWEST,
    DiscoverySort::FEATURED,
]);

test('relevance applies no explicit ordering', function () {
    expect(DiscoverySort::meilisearchOrder(DiscoverySort::RELEVANCE))->toBeEmpty();
    expect(DiscoverySort::sqlOrder(DiscoverySort::RELEVANCE))->toBeEmpty();
});

test('sort input is normalized from aliases to canonical keys', function (string $input, string $expected) {
    expect(DiscoverySort::normalize($input))->toBe($expected);
})->with([
    ['rating', DiscoverySort::RATING],
    ['top_rated', DiscoverySort::RATING],
    ['highest_rated', DiscoverySort::RATING],
    ['reviews', DiscoverySort::REVIEWS],
    ['most_reviewed', DiscoverySort::REVIEWS],
    ['newest', DiscoverySort::NEWEST],
    ['latest', DiscoverySort::NEWEST],
    ['featured', DiscoverySort::FEATURED],
    ['name', 'name'],
    ['nonsense', DiscoverySort::RELEVANCE],
    ['', DiscoverySort::RELEVANCE],
]);

test('rating and review sorts require the business review aggregate', function () {
    expect(DiscoverySort::requiredAggregates(DiscoverySort::RATING))->toBe(['businessReviews']);
    expect(DiscoverySort::requiredAggregates(DiscoverySort::REVIEWS))->toBe(['businessReviews']);
    expect(DiscoverySort::requiredAggregates(DiscoverySort::NEWEST))->toBe([]);
});

// ── Ranking rule configuration (source guard) ───────────────────────────────

test('every built-in relevance rule precedes the promotion tie-breaker', function () {
    $source = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));

    // Featured must be the LAST ranking rule so it can only break ties.
    $featuredPos = strpos($source, "'is_featured_rank:desc'");
    $exactnessPos = strpos($source, "'exactness'");
    $wordsPos = strpos($source, "'words'");

    expect($featuredPos)->not->toBeFalse();
    expect($exactnessPos)->not->toBeFalse();
    expect($wordsPos)->not->toBeFalse();

    expect($wordsPos)->toBeLessThan($featuredPos);
    expect($exactnessPos)->toBeLessThan($featuredPos);
});

test('rating is a tie-breaker after relevance but before promotion', function () {
    $source = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));

    $ratingPos = strpos($source, "'rating:desc'");
    $exactnessPos = strpos($source, "'exactness'");
    $featuredPos = strpos($source, "'is_featured_rank:desc'");

    expect($exactnessPos)->toBeLessThan($ratingPos);
    expect($ratingPos)->toBeLessThan($featuredPos);
});

test('the new sortable attributes are declared', function () {
    $source = file_get_contents(app_path('Console/Commands/ConfigureMeilisearch.php'));

    foreach (['rating', 'reviews_count', 'is_featured_rank'] as $attr) {
        expect($source)->toContain($attr);
    }
});

// ── Multi-listing independence ──────────────────────────────────────────────

test('multiple listings of one business index independently and are never collapsed', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->create(['name' => 'Listing A']);
    $b = Listing::factory()->forBusiness($business)->create(['name' => 'Listing B']);
    $c = Listing::factory()->forBusiness($business)->create(['name' => 'Listing C']);

    $names = collect([$a, $b, $c])->map(fn($l) => $l->toSearchableArray()['name'])->all();

    // One document per Listing, no representative selection.
    expect($names)->toHaveCount(3);
    expect(array_unique($names))->toHaveCount(3);
    expect($names)->toBe(['Listing A', 'Listing B', 'Listing C']);

    // Each carries its own identity; none is chosen from the Business.
    foreach ([$a, $b, $c] as $listing) {
        expect($listing->toSearchableArray()['id'])->toBe($listing->id);
    }
});

test('no representative listing selection exists in the discovery payload', function () {
    $source = file_get_contents(app_path('Models/Listing.php'));
    $payloadStart = strpos($source, 'function toSearchableArray');
    $payload = substr($source, $payloadStart, 6000);

    foreach (['listings()->first', 'primaryListing', 'defaultListing', 'mainListing', 'representativeListing'] as $pattern) {
        expect($payload)->not->toContain($pattern);
    }
});
