<?php

use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;

uses(RefreshDatabase::class);

/**
 * PHASE 23D — /search ERROR BOUNDARY.
 *
 * The main `/search` page used to call `Listing::search(...)->paginate()`
 * with NO error boundary, so a Meilisearch outage returned a 500 — while
 * `/search/autocomplete` degraded gracefully. The two surfaces must not behave
 * differently when the engine is unavailable.
 *
 * These tests cover the failure path and prove the success path is unchanged.
 * They deliberately do NOT assert that specific rows are returned: Meilisearch
 * indexing is asynchronous, and asserting on it here would duplicate the live
 * integration coverage in `tests/Feature/Listing/ListingLifecycleTest.php`.
 *
 * Helper names are prefixed `sb` (search boundary); Pest shares one process
 * across every test file in the suite, and this file must also work when run on
 * its own, so it defines its own failing-engine seam rather than borrowing one.
 */

function sbOwner(): User
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

/** A published, visible Listing whose owner holds an active subscription. */
function sbListing(string $name): Listing
{
    return Listing::factory()->published()->create([
        'owner_id' => sbOwner()->id,
        'name' => $name,
        'location_id' => null,
    ]);
}

/**
 * Replace the Scout engine with one whose every search operation fails.
 *
 * MUST be called AFTER any Listings are created: while this driver is active
 * the Scout model observers would throw on save.
 */
function sbBreakSearchEngine(): void
{
    $engine = new class extends Engine
    {
        public function update($models) {}

        public function delete($models) {}

        public function search($builder)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function paginate($builder, $perPage = null, $page = null)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function mapIds($results)
        {
            throw new RuntimeException('search engine unavailable');
        }

        public function map($builder, $results, $model) { return collect(); }

        public function lazyMap($builder, $results, $model) { return collect(); }

        public function getTotalCount($results) { return 0; }

        public function flush($model) {}

        public function createIndex($name, array $options = []) {}

        public function deleteIndex($name) {}
    };

    app(EngineManager::class)->extend('failing-search-engine', fn () => $engine);
    config(['scout.driver' => 'failing-search-engine']);
}

// ═══ SUCCESS PATH UNCHANGED ═════════════════════════════════════════════════

test('the search page renders normally when the search engine is up', function () {
    sbListing('Zebra Search Success');

    $this->get('/search?q=Zebra')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Search/Index')
            ->has('listings.data')
            ->has('listings.links')
            ->has('listings.total')
            ->has('listings.from')
            ->has('listings.to')
        );
});

// ═══ THE FAILURE PATH ═══════════════════════════════════════════════════════
//
// NOTE ON SHAPE — PHASE 23E corrected the `/search` envelope. It used to be the
// API-resource shape `{ data, links:{first,last,prev,next}, meta:{…} }`; it is
// now the canonical application paginator shape
// `{ data, links:[…], current_page, from, last_page, per_page, to, total }`,
// identical to `/directory`, `/collections` and the admin listings page. The
// assertions below use that contract.

test('a search engine failure returns a normal 200 page instead of a 500', function () {
    sbListing('Zebra Search Failure');
    sbBreakSearchEngine();

    $this->get('/search?q=Zebra')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Search/Index')
            ->where('listings.data', [])
            ->where('listings.total', 0)
        );
});

test('a failed search returns an empty result set rather than falling back to the database', function () {
    sbListing('Zebra No Fallback');
    sbBreakSearchEngine();

    $props = $this->get('/search?q=Zebra')->assertOk()->viewData('page')['props'];

    // The row IS in the database — a SQL fallback would have found it.
    expect(Listing::where('name', 'Zebra No Fallback')->exists())->toBeTrue();

    // ...and search still returns nothing, because there is no fallback.
    expect($props['listings']['data'])->toBe([]);
    expect($props['listings']['total'])->toBe(0);
});

test('a search engine failure does not leak the exception to the visitor', function () {
    sbBreakSearchEngine();

    $response = $this->get('/search?q=anything')->assertOk();

    // APP_DEBUG is on in testing, so an uncaught failure would render the
    // engine's message into the error page. It must not appear anywhere.
    expect($response->getContent())->not->toContain('search engine unavailable');
});

test('a failed search is logged as a warning with the exception class', function () {
    sbBreakSearchEngine();

    Log::spy();

    $this->get('/search?q=Zebra')->assertOk();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($message) => $message === 'Search listing query failed')
        ->once();
});

// ═══ THE RESPONSE CONTRACT IS PRESERVED ═════════════════════════════════════

test('a failed search keeps the query, filters, sort and pagination shape', function () {
    sbBreakSearchEngine();

    $props = $this->get('/search?q=Zebra&category=1&city=2&featured=1&sort=rating&page=2')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['query'])->toBe('Zebra');
    expect($props['sort'])->toBe('rating');
    expect($props['filters']['category'])->toBe('1');
    expect($props['filters']['featured'])->toBe('1');

    // The empty result set is a real paginator, not a bare array.
    expect($props['listings']['current_page'])->toBe(2);
    expect($props['listings']['per_page'])->toBe(12);
    expect($props['listings']['total'])->toBe(0);
});

test('the listings shape is identical whether or not the engine is up', function () {
    sbListing('Zebra Shape');

    $healthy = $this->get('/search?q=Zebra')->assertOk()->viewData('page')['props'];
    $healthyKeys = array_keys($healthy['listings']);

    sbBreakSearchEngine();

    $broken = $this->get('/search?q=Zebra')->assertOk()->viewData('page')['props'];

    expect(array_keys($broken['listings']))->toBe($healthyKeys);
});

// ═══ AUTOCOMPLETE STAYS INDEPENDENTLY GUARDED ═══════════════════════════════

test('a search engine failure does not break the autocomplete endpoint', function () {
    sbListing('Zebra Autocomplete');
    sbBreakSearchEngine();

    $this->getJson('/search/autocomplete?q=Zebra')
        ->assertOk()
        ->assertJsonStructure(['suggestions', 'category_suggestions', 'search_suggestions']);
});
