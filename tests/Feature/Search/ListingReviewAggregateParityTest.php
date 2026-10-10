<?php

use App\Http\Resources\ListingDirectoryResource;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use App\Support\SearchIndexName;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 23J — REVIEW AGGREGATE PARITY.
 *
 * `Listing::toSearchableArray()` publishes the LISTING's own APPROVED-review
 * aggregate, but every SQL-backed payload aggregated the UNFILTERED `reviews`
 * relation. So `/directory` counted pending and rejected reviews while `/search`
 * did not: two answers to the same question, and a Listing with three PENDING
 * reviews outranked one with a single APPROVED review.
 *
 * These tests drive the REAL routes and the REAL index. They do not inspect SQL
 * text or observer registration.
 *
 * Helper names are prefixed `rp`; Pest shares one process across test files.
 */

function rpOwner(): User
{
    $owner = User::factory()->owner()->create(['email_verified_at' => now()]);

    $plan = Plan::factory()->create([
        'max_listings' => 10,
        'max_locations' => 3,
        'max_images' => 10,
        'features' => ['lead_capture' => true],
    ]);

    // The indexed `has_active_subscription` is read from the owner at INDEX
    // time, so it must exist before the Listing is created.
    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

function rpListing(User $owner, string $name): Listing
{
    return Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
        'name' => $name,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
}

/** The payload the SQL directory serves for a Listing. */
function rpDirectoryPayload(Listing $listing): array
{
    $loaded = Listing::query()->withApprovedReviewAggregates()->find($listing->id);

    return (new ListingDirectoryResource($loaded))->resolve();
}

/** The Listing's ACTUAL document in the running Meilisearch index. */
function rpDocument(Listing $listing): ?array
{
    try {
        $client = new \Meilisearch\Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );

        return $client->index(SearchIndexName::listing())->getDocument($listing->id);
    } catch (\Throwable $e) {
        return null;
    }
}

/** Poll until the Listing is present in the real index. */
function rpWaitForIndex(Listing $listing, int $attempts = 25): bool
{
    for ($i = 0; $i < $attempts; $i++) {
        if (rpDocument($listing) !== null) {
            return true;
        }

        usleep(300000);
    }

    return false;
}

/** The `/directory` result names for a query string. */
function rpDirectoryNames(string $query): array
{
    $props = test()->get('/directory?' . $query)->assertOk()->viewData('page')['props'];

    return collect($props['listings']['data'] ?? $props['listings'] ?? [])
        ->pluck('name')
        ->all();
}

// ═══ UNAPPROVED REVIEWS CANNOT INFLATE ANYTHING ═════════════════════════════

test('a pending review cannot inflate the displayed rating or review count', function () {
    $owner = rpOwner();
    $listing = rpListing($owner, 'Pending Inflated Co');

    Review::factory()->for($listing)->approved()->rated(5)->create();
    Review::factory()->for($listing)->pending()->rated(1)->count(3)->create();

    $payload = rpDirectoryPayload($listing);

    expect($payload['reviews_count'])->toBe(1)
        ->and($payload['rating'])->toBe(5.0);
});

test('a rejected review cannot inflate the displayed rating or review count', function () {
    $owner = rpOwner();
    $listing = rpListing($owner, 'Rejected Inflated Co');

    Review::factory()->for($listing)->approved()->rated(4)->create();
    Review::factory()->for($listing)->rejected()->rated(1)->count(2)->create();

    $payload = rpDirectoryPayload($listing);

    expect($payload['reviews_count'])->toBe(1)
        ->and($payload['rating'])->toBe(4.0);
});

test('the directory review sort does not rank a Listing by unapproved reviews', function () {
    // The exact 23J regression: five PENDING reviews must not outrank one
    // APPROVED review.
    $owner = rpOwner();
    rpListing($owner, 'Approved Leader Co');
    $pendingHeavy = rpListing($owner, 'Pending Heavy Co');

    Review::factory()->for(Listing::where('name', 'Approved Leader Co')->first())
        ->approved()->rated(5)->create();

    Review::factory()->for($pendingHeavy)->pending()->rated(5)->count(5)->create();

    $names = rpDirectoryNames('sort=reviews');

    expect($names)->toContain('Approved Leader Co')
        ->and($names)->toContain('Pending Heavy Co');

    // Approved Leader has the only APPROVED reviews, so it must rank first.
    expect(array_search('Approved Leader Co', $names, true))
        ->toBeLessThan(array_search('Pending Heavy Co', $names, true));
});

test('the search document and the directory payload agree on the review aggregate', function () {
    $owner = rpOwner();
    $listing = rpListing($owner, 'Parity Co');

    Review::factory()->for($listing)->approved()->rated(5)->create();
    Review::factory()->for($listing)->pending()->rated(1)->count(2)->create();

    expect(rpWaitForIndex($listing))->toBeTrue('Precondition: the Listing should be indexed.');

    $directory = rpDirectoryPayload($listing);

    $document = rpDocument($listing);
    $hydrated = (new Listing())->newFromBuilder($document);
    $search = (new ListingDirectoryResource($hydrated))->resolve();

    // Same numbers, same types — and approved-only on both sides.
    expect($search['reviews_count'])->toBe($directory['reviews_count'])
        ->and($search['rating'])->toBe($directory['rating'])
        ->and($directory['reviews_count'])->toBe(1)
        ->and($directory['rating'])->toBe(5.0);
});

test('a Listing with only unapproved reviews reports zero on both engines', function () {
    $owner = rpOwner();
    $listing = rpListing($owner, 'Unapproved Only Co');

    Review::factory()->for($listing)->pending()->rated(5)->count(3)->create();

    expect(rpWaitForIndex($listing))->toBeTrue('Precondition: the Listing should be indexed.');

    $directory = rpDirectoryPayload($listing);
    $search = (new ListingDirectoryResource((new Listing())->newFromBuilder(rpDocument($listing))))->resolve();

    expect($directory['reviews_count'])->toBe(0)
        ->and($directory['rating'])->toBe(0.0)
        ->and($search['reviews_count'])->toBe(0)
        ->and($search['rating'])->toBe(0.0);
});

test('deleting an approved review removes it from the aggregate', function () {
    $owner = rpOwner();
    $listing = rpListing($owner, 'Deleted Review Co');

    $review = Review::factory()->for($listing)->approved()->rated(5)->create();

    expect(rpDirectoryPayload($listing)['reviews_count'])->toBe(1);

    $review->delete();

    $payload = rpDirectoryPayload($listing);

    expect($payload['reviews_count'])->toBe(0)
        ->and($payload['rating'])->toBe(0.0);
});

test('the min_rating filter and the review sort share approved-only semantics', function () {
    $owner = rpOwner();
    rpListing($owner, 'Approved Co');
    $pendingOnly = rpListing($owner, 'Pending Only Co');

    Review::factory()->for(Listing::where('name', 'Approved Co')->first())
        ->approved()->rated(5)->create();
    Review::factory()->for($pendingOnly)->pending()->rated(5)->create();

    // The FILTER is approved-only...
    $filtered = rpDirectoryNames('min_rating=4');
    expect($filtered)->toContain('Approved Co')
        ->and($filtered)->not->toContain('Pending Only Co');

    // ...and so is the SORT, which must not credit the pending review.
    $sorted = rpDirectoryNames('sort=rating');
    expect(array_search('Approved Co', $sorted, true))
        ->toBeLessThan(array_search('Pending Only Co', $sorted, true));
});
