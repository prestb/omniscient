<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingService;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Support\ListingType;
use App\Support\SearchIndexName;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 23H — LISTING CHILD → SEARCH SYNCHRONIZATION.
 *
 * `Listing::toSearchableArray()` publishes `category_ids`, `categories_names`
 * (the `listing_categories` pivot) and `services_names` (`listing_services`),
 * but Scout only observes a Listing's OWN events — so changing a child left the
 * Meilisearch document stale.
 *
 * These tests assert the REAL index behaviour through `Listing::search()`, which
 * queries the actual `test_listings` index on the running Meilisearch server.
 * They do NOT inspect observer registration.
 *
 * Meilisearch indexing is task-based, so every positive assertion polls. Absence
 * assertions short-circuit immediately when the invariant already holds.
 *
 * Helper names are prefixed `cs` (child sync); Pest shares one process across
 * every test file, and this file must also work when run on its own.
 */

function csOwner(): \App\Models\User
{
    return \App\Models\User::factory()->owner()->create(['email_verified_at' => now()]);
}

/** A published, visible Listing with a unique single-token marker. */
function csListing(array $attributes = []): Listing
{
    return Listing::factory()->published()->create(array_merge([
        'owner_id' => csOwner()->id,
        'name' => 'ZebraCs' . uniqid(),
        'business_id' => null,
        'location_id' => null,
    ], $attributes));
}

function csCategory(string $name): Category
{
    return Category::factory()->create(['name' => $name, 'is_active' => true]);
}

/** Poll the real index until the predicate holds, or give up. */
function csWaitFor(callable $predicate, int $attempts = 25): bool
{
    for ($i = 0; $i < $attempts; $i++) {
        if ($predicate()) {
            return true;
        }

        usleep(300000);
    }

    return false;
}

/** Does the real index currently return this Listing for the given query? */
function csFoundBy(string $query, Listing $listing): bool
{
    return Listing::search($query)->take(10)->get()->pluck('id')->contains($listing->id);
}

/** Does the real index return this Listing when filtered by a category? */
function csFilteredByCategory(Listing $listing, int $categoryId): bool
{
    return Listing::search($listing->name)
        ->where('category_ids', $categoryId)
        ->take(10)
        ->get()
        ->pluck('id')
        ->contains($listing->id);
}

/**
 * Read the Listing's ACTUAL document from the running Meilisearch server.
 *
 * Category and service membership can be proven through discovery queries
 * because those fields are filterable/searchable. Rating, review count and
 * paid-visibility are not, so they are asserted on the document itself.
 * The index name is resolved, never hardcoded.
 */
function csDocument(Listing $listing): ?array
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

// ═══ CATEGORY SYNCHRONIZATION ═══════════════════════════════════════════════

test('attaching a category updates the Listing search document', function () {
    $listing = csListing();
    $category = csCategory('PlumbSync' . uniqid());

    expect(csWaitFor(fn () => csFoundBy($listing->name, $listing)))
        ->toBeTrue('Precondition: the published Listing should be indexed.');

    expect(csFilteredByCategory($listing, $category->id))
        ->toBeFalse('Precondition: the category was never attached.');

    $listing->categories()->attach($category->id);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('Attaching a category must reach the search index.');
});

test('detaching a category updates the Listing search document', function () {
    $listing = csListing();
    $category = csCategory('PlumbDetach' . uniqid());

    $listing->categories()->attach($category->id);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('Precondition: the attached category should be indexed.');

    $listing->categories()->detach($category->id);

    expect(csWaitFor(fn () => ! csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('A removed category must STOP matching — stale classification is the defect.');
});

test('a category change on one Listing does not affect a sibling Listing', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->published()->forBusiness($business)->create(['name' => 'ZebraCsA' . uniqid()]);
    $b = Listing::factory()->published()->forBusiness($business)->create(['name' => 'ZebraCsB' . uniqid()]);

    $catX = csCategory('PlumbX' . uniqid());
    $catY = csCategory('PlumbY' . uniqid());

    expect(csWaitFor(fn () => csFoundBy($a->name, $a) && csFoundBy($b->name, $b)))
        ->toBeTrue('Precondition: both sibling Listings should be indexed.');

    $a->categories()->attach($catX->id);
    $b->categories()->attach($catY->id);

    expect(csWaitFor(fn () => csFilteredByCategory($a, $catX->id) && csFilteredByCategory($b, $catY->id)))
        ->toBeTrue('Precondition: each Listing should match its OWN category.');

    // The invariant: sibling Listings never absorb each other's classification.
    expect(csFilteredByCategory($a, $catY->id))->toBeFalse('Listing A must not match Listing B\'s category.');
    expect(csFilteredByCategory($b, $catX->id))->toBeFalse('Listing B must not match Listing A\'s category.');
});

// ═══ SERVICE SYNCHRONIZATION ════════════════════════════════════════════════

test('creating a service updates the Listing search document', function () {
    $listing = csListing();
    $serviceName = 'PipeRepair' . uniqid();

    expect(csWaitFor(fn () => csFoundBy($listing->name, $listing)))
        ->toBeTrue('Precondition: the published Listing should be indexed.');

    ListingService::create([
        'listing_id' => $listing->id,
        'name' => $serviceName,
        'sort_order' => 1,
    ]);

    expect(csWaitFor(fn () => csFoundBy($serviceName, $listing)))
        ->toBeTrue('A new service name must become searchable.');
});

test('deleting a service updates the Listing search document', function () {
    $listing = csListing();
    $serviceName = 'PipeRemove' . uniqid();

    $service = ListingService::create([
        'listing_id' => $listing->id,
        'name' => $serviceName,
        'sort_order' => 1,
    ]);

    expect(csWaitFor(fn () => csFoundBy($serviceName, $listing)))
        ->toBeTrue('Precondition: the created service should be searchable.');

    $service->delete();

    expect(csWaitFor(fn () => ! csFoundBy($serviceName, $listing)))
        ->toBeTrue('A deleted service must STOP matching — stale services_names is the defect.');
});

test('a service change on one Listing does not affect a sibling Listing', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->published()->forBusiness($business)->create(['name' => 'ZebraCsSA' . uniqid()]);
    $b = Listing::factory()->published()->forBusiness($business)->create(['name' => 'ZebraCsSB' . uniqid()]);

    $serviceA = 'Drainage' . uniqid();

    expect(csWaitFor(fn () => csFoundBy($a->name, $a) && csFoundBy($b->name, $b)))
        ->toBeTrue('Precondition: both sibling Listings should be indexed.');

    ListingService::create(['listing_id' => $a->id, 'name' => $serviceA, 'sort_order' => 1]);

    expect(csWaitFor(fn () => csFoundBy($serviceA, $a)))
        ->toBeTrue('Precondition: Listing A should match its own service.');

    expect(csFoundBy($serviceA, $b))
        ->toBeFalse('Listing B must not match Listing A\'s service.');
});

// ═══ VISIBILITY ═════════════════════════════════════════════════════════════

test('a draft Listing does not become searchable when its children change', function () {
    $listing = csListing(['status' => Listing::STATUS_DRAFT]);
    $category = csCategory('PlumbDraft' . uniqid());
    $serviceName = 'DraftService' . uniqid();

    $listing->categories()->attach($category->id);
    ListingService::create(['listing_id' => $listing->id, 'name' => $serviceName, 'sort_order' => 1]);

    // The children ARE persisted — this must not be a vacuous absence.
    expect($listing->fresh()->categories()->count())->toBe(1);
    expect($listing->fresh()->services()->count())->toBe(1);

    expect(csFoundBy($listing->name, $listing))
        ->toBeFalse('A draft Listing must stay out of the index even when its children change.');

    // ...and publishing publishes the CURRENT child state.
    $listing->update(['status' => Listing::STATUS_PUBLISHED]);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id) && csFoundBy($serviceName, $listing)))
        ->toBeTrue('Publishing must publish the current child state.');
});

test('a hidden Listing does not become searchable when its children change', function () {
    $listing = csListing(['hidden_at' => now()]);
    $category = csCategory('PlumbHidden' . uniqid());
    $serviceName = 'HiddenService' . uniqid();

    $listing->categories()->attach($category->id);
    ListingService::create(['listing_id' => $listing->id, 'name' => $serviceName, 'sort_order' => 1]);

    expect($listing->fresh()->categories()->count())->toBe(1);
    expect($listing->fresh()->services()->count())->toBe(1);

    expect(csFoundBy($listing->name, $listing))
        ->toBeFalse('A hidden Listing must stay out of the index even when its children change.');

    $listing->update(['hidden_at' => null]);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id) && csFoundBy($serviceName, $listing)))
        ->toBeTrue('Unhiding must publish the current child state.');
});

// ═══ BUSINESS-LESS PROFESSIONAL ═════════════════════════════════════════════

test('a Business-less Professional synchronizes its children identically', function () {
    $listing = csListing(['type' => ListingType::PROFESSIONAL->value]);
    $category = csCategory('PlumbPro' . uniqid());
    $serviceName = 'ProService' . uniqid();

    expect($listing->business_id)->toBeNull();

    $listing->categories()->attach($category->id);
    ListingService::create(['listing_id' => $listing->id, 'name' => $serviceName, 'sort_order' => 1]);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('A Business-less Professional must synchronize categories.');

    expect(csWaitFor(fn () => csFoundBy($serviceName, $listing)))
        ->toBeTrue('A Business-less Professional must synchronize services.');
});

// ═══ COMBINED CONVERGENCE ═══════════════════════════════════════════════════

test('category and service changes converge on one current document', function () {
    $listing = csListing();

    $keepCategory = csCategory('PlumbKeep' . uniqid());
    $dropCategory = csCategory('PlumbDrop' . uniqid());

    $keepService = 'KeepService' . uniqid();
    $dropServiceName = 'DropService' . uniqid();

    $listing->categories()->attach([$keepCategory->id, $dropCategory->id]);

    $dropService = ListingService::create([
        'listing_id' => $listing->id,
        'name' => $dropServiceName,
        'sort_order' => 1,
    ]);

    ListingService::create([
        'listing_id' => $listing->id,
        'name' => $keepService,
        'sort_order' => 2,
    ]);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $keepCategory->id)
        && csFilteredByCategory($listing, $dropCategory->id)
        && csFoundBy($keepService, $listing)
        && csFoundBy($dropServiceName, $listing)))
        ->toBeTrue('Precondition: the initial combined state should be indexed.');

    // Remove one of each and add nothing.
    $listing->categories()->detach($dropCategory->id);
    $dropService->delete();

    expect(csWaitFor(fn () => ! csFilteredByCategory($listing, $dropCategory->id)
        && ! csFoundBy($dropServiceName, $listing)))
        ->toBeTrue('Removals must converge in the index.');

    // The surviving children must still match — removals must not wipe the document.
    expect(csFilteredByCategory($listing, $keepCategory->id))->toBeTrue('The kept category must survive.');
    expect(csFoundBy($keepService, $listing))->toBeTrue('The kept service must survive.');
});

test('a category change made from the Category side synchronizes too', function () {
    $listing = csListing();
    $category = csCategory('PlumbInverse' . uniqid());

    expect(csWaitFor(fn () => csFoundBy($listing->name, $listing)))
        ->toBeTrue('Precondition: the published Listing should be indexed.');

    // The INVERSE direction: a different model API writing the same pivot.
    // Without `using()` on Category::listings() this would bypass indexing.
    $category->listings()->attach($listing->id);

    expect(csWaitFor(fn () => csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('Attaching from the Category side must synchronize.');

    $category->listings()->detach($listing->id);

    expect(csWaitFor(fn () => ! csFilteredByCategory($listing, $category->id)))
        ->toBeTrue('Detaching from the Category side must synchronize.');
});

// ═══ THE OTHER SEARCH-AFFECTING CHILDREN ════════════════════════════════════
// `toSearchableArray()` also publishes Location fields, the owner's paid
// visibility, and the Listing's own review aggregate. Each is asserted on the
// ACTUAL document, because none of these three is a discovery term.

test('a Location change updates every Listing that references it', function () {
    $location = Location::factory()->create();
    $listing = csListing(['location_id' => $location->id]);

    expect(csWaitFor(fn () => csDocument($listing) !== null))
        ->toBeTrue('Precondition: the Listing should be indexed.');

    $newAddress = 'Address' . uniqid();
    $location->update(['address' => $newAddress]);

    expect(csWaitFor(fn () => (csDocument($listing)['address'] ?? null) === $newAddress))
        ->toBeTrue('Editing a Location must reach every Listing document that publishes it.');
});

test('a Subscription change updates the owner Listings', function () {
    $owner = csOwner();
    $plan = Plan::factory()->create(['max_listings' => 10]);

    $subscription = Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    $listing = csListing(['owner_id' => $owner->id]);

    expect(csWaitFor(fn () => (csDocument($listing)['has_active_subscription'] ?? null) === true))
        ->toBeTrue('Precondition: an active subscription should be published as true.');

    $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

    expect(csWaitFor(fn () => (csDocument($listing)['has_active_subscription'] ?? null) === false))
        ->toBeTrue('A cancelled subscription must stop publishing paid visibility.');
});

test('a Review change updates the Listing search document', function () {
    $listing = csListing();

    expect(csWaitFor(fn () => csDocument($listing) !== null))
        ->toBeTrue('Precondition: the Listing should be indexed.');

    expect((int) (csDocument($listing)['reviews_count'] ?? -1))->toBe(0);

    $review = Review::factory()->create([
        'listing_id' => $listing->id,
        'status' => Review::STATUS_APPROVED,
        'rating' => 4,
    ]);

    expect(csWaitFor(fn () => (int) (csDocument($listing)['reviews_count'] ?? 0) === 1
        && (float) (csDocument($listing)['rating'] ?? 0) === 4.0))
        ->toBeTrue('An approved review must reach the Listing document aggregate.');

    $review->delete();

    expect(csWaitFor(fn () => (int) (csDocument($listing)['reviews_count'] ?? -1) === 0))
        ->toBeTrue('Deleting a review must update the aggregate.');
});
