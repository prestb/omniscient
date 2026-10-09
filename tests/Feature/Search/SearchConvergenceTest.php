<?php

use App\Models\Business;
use App\Models\Listing;
use App\Support\SearchIndexName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Searchable;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-1 — Business is NOT a discovery entity.
 *
 * The canonical public discovery/search entity is the LISTING. A Business is an
 * organization (an aggregate of Listings) and must not carry an independent
 * public search identity that competes with Listing.
 */

test('business is not a searchable entity', function () {
    expect(in_array(Searchable::class, class_uses_recursive(Business::class), true))
        ->toBeFalse();

    // The search surface must be gone, not merely unused.
    expect(method_exists(Business::class, 'searchableAs'))->toBeFalse();
    expect(method_exists(Business::class, 'toSearchableArray'))->toBeFalse();
    expect(method_exists(Business::class, 'smartSearch'))->toBeFalse();
});

test('listing is the canonical searchable entity', function () {
    $listing = Listing::factory()->create();

    // PHASE 23C-R4 — the index name is RESOLVED, not a literal. The assertion
    // is pinned to the resolved test index AND to the literal, so it cannot
    // pass by following a resolver that silently points at development.
    expect($listing->searchableAs())->toBe(SearchIndexName::listing());
    expect($listing->searchableAs())->toBe('test_listings');
    expect(method_exists($listing, 'toSearchableArray'))->toBeTrue();
});

test('the listing search document exposes the canonical discovery fields', function () {
    $listing = Listing::factory()->published()->create();

    $document = $listing->toSearchableArray();

    foreach ([
        'id', 'name', 'slug', 'type', 'status', 'hidden',
        'business_id', 'location_id',
        'city_id', 'region_id', 'country_id',
        'category_ids', 'categories_names', 'services_names',
        'is_featured', 'is_open_now', 'has_active_subscription',
        'created_at', 'published_at',
    ] as $key) {
        expect($document)->toHaveKey($key);
    }

    // The organization is contextual metadata on a Listing document,
    // never a separate search identity.
    expect($document['business_id'])->toBe($listing->business_id);
});

test('a listing associated with a business keeps its organization context', function () {
    $business = Business::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    // Two listings of one organization are two independent documents.
    $second = Listing::factory()->forBusiness($business)->published()->create();

    expect($listing->toSearchableArray()['business_id'])->toBe($business->id);
    expect($second->toSearchableArray()['business_id'])->toBe($business->id);
    expect($listing->toSearchableArray()['id'])->not->toBe($second->toSearchableArray()['id']);
});

test('business and listing search identities do not share an index', function () {
    // PHASE 23C-R4 — and the suite must never resolve that index to the
    // DEVELOPMENT index the running application uses.
    expect(Listing::factory()->create()->searchableAs())->toBe('test_listings');
    expect(Listing::factory()->create()->searchableAs())->not->toBe('listings');

    // Business has no index at all now.
    expect(method_exists(Business::class, 'searchableAs'))->toBeFalse();
});
