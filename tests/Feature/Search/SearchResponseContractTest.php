<?php

use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 23E — /search SERVER RESPONSE CONTRACT.
 *
 * The search page consumed `listings.total`, `listings.from`, `listings.to`,
 * `listings.links` and `listings.data` at the TOP level, while the controller
 * wrapped the paginator in `ListingDirectoryResource::collection()`, which
 * emits the API-resource envelope `{ data, links:{first,last,prev,next},
 * meta:{…} }`. `Pagination.vue` requires `links: Array`, so pagination never
 * rendered and the counts read `undefined`.
 *
 * These tests pin the corrected contract: the flattened paginator shape the
 * rest of the application already uses. They assert STRUCTURE rather than hit
 * counts, because Meilisearch indexing is asynchronous — hit-count behaviour is
 * covered by the live integration test in
 * `tests/Feature/Listing/ListingLifecycleTest.php`.
 *
 * The 23D failure path is deliberately NOT duplicated here; it is already
 * proven in `SearchErrorBoundaryTest.php`, which also asserts this same
 * envelope.
 *
 * Helper names are prefixed `rc` (response contract); Pest shares one process
 * across every test file, and this file must also work when run on its own.
 */

function rcOwner(): User
{
    $plan = Plan::factory()->create(['max_listings' => 10]);

    $owner = User::factory()->owner()->create([
        'email_verified_at' => now(),
    ]);

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    return $owner;
}

function rcListing(string $name): Listing
{
    return Listing::factory()->published()->create([
        'owner_id' => rcOwner()->id,
        'name' => $name,
        'location_id' => null,
    ]);
}

/** The canonical paginator keys the Search page reads. */
const RC_PAGINATOR_KEYS = [
    'current_page',
    'data',
    'first_page_url',
    'from',
    'last_page',
    'last_page_url',
    'links',
    'next_page_url',
    'path',
    'per_page',
    'prev_page_url',
    'to',
    'total',
];

// ═══ THE ENVELOPE ═══════════════════════════════════════════════════════════

test('a search response carries the canonical flattened paginator envelope', function () {
    $props = $this->get('/search?q=zzz-no-such-listing')->assertOk()->viewData('page')['props'];

    foreach (RC_PAGINATOR_KEYS as $key) {
        expect($props['listings'])->toHaveKey($key);
    }

    // The API-resource envelope must NOT come back. This is the regression
    // guard for the exact defect: `meta` is where `total` used to hide.
    expect($props['listings'])->not->toHaveKey('meta');
    expect($props['listings'])->not->toHaveKey('data.meta');
});

test('the paginator metadata has the types the search page expects', function () {
    $listings = $this->get('/search?q=zzz-no-such-listing')->assertOk()->viewData('page')['props']['listings'];

    expect($listings['data'])->toBeArray();
    expect($listings['current_page'])->toBeInt();
    expect($listings['last_page'])->toBeInt();
    expect($listings['per_page'])->toBeInt()->toBe(12);
    expect($listings['total'])->toBeInt();
    expect($listings['from'])->toBeNull();   // empty page => null
    expect($listings['to'])->toBeNull();
});

test('links is the array shape Pagination.vue requires', function () {
    $listings = $this->get('/search?q=zzz-no-such-listing')->assertOk()->viewData('page')['props']['listings'];

    // `Pagination.vue` declares `links: { type: Array, required: true }` and
    // reads `links.length`. The resource envelope made this an OBJECT, so
    // `links.length` was undefined and the whole control was hidden by
    // `v-if="links.length > 3"`.
    expect($listings['links'])->toBeArray()->not->toBeEmpty();

    foreach ($listings['links'] as $link) {
        expect($link)->toHaveKeys(['url', 'label', 'active']);
        expect($link['label'])->toBeString();
        expect($link['active'])->toBeBool();
        // A disabled link legitimately has a null URL.
        expect($link['url'] === null || is_string($link['url']))->toBeTrue();
    }
});

// ═══ ZERO RESULTS ═══════════════════════════════════════════════════════════

test('a zero-result search still produces the canonical envelope', function () {
    $listings = $this->get('/search?q=zzz-no-such-listing')->assertOk()->viewData('page')['props']['listings'];

    expect($listings['data'])->toBe([]);
    expect($listings['total'])->toBe(0);
    expect($listings['current_page'])->toBe(1);
    expect($listings['last_page'])->toBe(1);
});

test('a later page of an empty result set still reports coherent metadata', function () {
    $listings = $this->get('/search?q=zzz-no-such-listing&page=2')->assertOk()->viewData('page')['props']['listings'];

    expect($listings['data'])->toBe([]);
    expect($listings['total'])->toBe(0);
    expect($listings['current_page'])->toBe(2);
});

// ═══ ITEMS ARE STILL RESOURCE-TRANSFORMED ═══════════════════════════════════

test('search results are transformed listing payloads, not raw models', function () {
    $marker = 'Zebra Contract ' . uniqid();

    $listing = rcListing($marker);

    // Meilisearch indexing is task-based; mirror the settle window the live
    // integration test already uses.
    $item = null;

    for ($attempt = 0; $attempt < 15; $attempt++) {
        $data = $this->get('/search?q=' . urlencode($marker))
            ->assertOk()
            ->viewData('page')['props']['listings']['data'];

        if (count($data) > 0) {
            $item = $data[0];
            break;
        }

        usleep(300000);
    }

    $this->assertNotNull($item, 'The created Listing should reach the search index within the settle window.');

    // The resource's own vocabulary — proves `ListingDirectoryResource` still
    // runs per item after the envelope change.
    expect($item['id'])->toBe($listing->id);
    expect($item['type'])->toBe('listing');
    expect($item)->toHaveKey('feature_flags');
    expect($item)->toHaveKey('locations');

    // A raw Eloquent dump would carry these; the resource never does.
    expect($item)->not->toHaveKey('owner_id');
    expect($item)->not->toHaveKey('created_at');
    expect($item)->not->toHaveKey('location_id');
});
