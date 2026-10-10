<?php

use App\Models\Listing;
use App\Models\Location;
use App\Models\LocationHour;
use App\Models\LocationHourOverride;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 23J — OPENING-STATUS PARITY.
 *
 * Opening status is TIME-VARYING: it changes as the clock advances even when no
 * row is written. `/search` used to filter on a Boolean frozen into the index
 * while `/directory` evaluated the same question live — two authorities, two
 * answers.
 *
 * `/search` now resolves the currently-open Locations through the SAME predicate
 * the directory uses (`Location::scopeOpenNow()`) and narrows the engine by the
 * already-filterable `location_id`, so the engine still performs the search AND
 * the pagination.
 *
 * Two deliberate test-hygiene rules:
 *
 *   * Time is controlled with `travelTo`, never the wall clock.
 *   * EVERY query is scoped by a unique tag. The shared `test_listings` index is
 *     NOT rolled back between tests (Meilisearch has no transaction), so an
 *     unscoped query would race thousands of stale documents from earlier runs
 *     and prove nothing.
 *
 * Helper names are prefixed `on`; Pest shares one process across test files.
 */

function onTag(): string
{
    return 'Zebra' . uniqid();
}

function onOwner(): User
{
    $owner = User::factory()->owner()->create(['email_verified_at' => now()]);

    $plan = Plan::factory()->create([
        'max_listings' => 40,
        'max_locations' => 40,
        'max_images' => 10,
        'features' => ['lead_capture' => true],
    ]);

    // The indexed `has_active_subscription` is read from the owner at INDEX
    // time, so it must exist before any Listing is created.
    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

/**
 * A published Listing named "<tag> <label>" with its own Location and hours.
 *
 * @param  array<int, array<string, mixed>>  $hourRows
 */
function onListing(User $owner, string $tag, string $label, array $hourRows = []): Listing
{
    $location = Location::factory()->create(['owner_id' => $owner->id]);

    foreach ($hourRows as $row) {
        LocationHour::create(array_merge([
            'location_id' => $location->id,
            'day_of_week' => 0,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
            'is_24h' => false,
            'sort_order' => 0,
        ], $row));
    }

    return Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => $location->id,
        'name' => $tag . ' ' . $label,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
}

/** Weekly hours that make a Location open between $opens and $closes. */
function onHours(int $day, string $opens, string $closes): array
{
    return [[
        'day_of_week' => $day,
        'opens_at' => $opens,
        'closes_at' => $closes,
        'is_closed' => false,
        'is_24h' => false,
    ]];
}

/**
 * Poll the REAL index until the tag-scoped query returns at least $expected
 * hits.
 *
 * The check is deliberately tag-based rather than `getDocument($id)`: Meilisearch
 * is not rolled back between tests, and the test database restarts ids at 1, so
 * a STALE document with the same id would satisfy an id-based check while this
 * test's own document had not been indexed yet.
 */
function onWaitForTag(string $tag, int $expected, int $attempts = 40): bool
{
    $index = (new \Meilisearch\Client(
        config('scout.meilisearch.host'),
        config('scout.meilisearch.key')
    ))->index(\App\Support\SearchIndexName::listing());

    for ($i = 0; $i < $attempts; $i++) {
        try {
            if (count($index->search($tag, ['limit' => 50])->getHits()) >= $expected) {
                return true;
            }
        } catch (\Throwable $e) {
            // Index not ready yet.
        }

        usleep(300000);
    }

    return false;
}

/** The `/search` result names for a tag-scoped query. */
function onSearchNames(string $tag, string $extra = ''): array
{
    $qs = 'q=' . urlencode($tag) . ($extra !== '' ? '&' . $extra : '');
    $props = test()->get('/search?' . $qs)->assertOk()->viewData('page')['props'];

    return collect($props['listings']['data'] ?? [])->pluck('name')->all();
}

/** The `/search` paginator itself, for a tag-scoped query. */
function onSearchPaginator(string $tag, string $extra = ''): array
{
    $qs = 'q=' . urlencode($tag) . ($extra !== '' ? '&' . $extra : '');

    return test()->get('/search?' . $qs)->assertOk()->viewData('page')['props']['listings'];
}

/** The `/directory` result names for a tag-scoped query. */
function onDirectoryNames(string $tag, string $extra = ''): array
{
    $qs = 'search=' . urlencode($tag) . ($extra !== '' ? '&' . $extra : '');
    $props = test()->get('/directory?' . $qs)->assertOk()->viewData('page')['props'];

    return collect($props['listings']['data'] ?? $props['listings'] ?? [])->pluck('name')->all();
}

// ═══ LIVE EVALUATION AT THE CURRENT TIME ════════════════════════════════════

test('a Listing open at the evaluation time matches the search filter', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $open = onListing($owner, $tag, 'Open', onHours($at->dayOfWeek, '09:00', '17:00'));

    expect(onWaitForTag($tag, 1))->toBeTrue('Precondition: the Listing should be indexed.');

    expect(onSearchNames($tag, 'open_now=1'))->toBe([$tag . ' Open']);
});

test('a Listing closed at the evaluation time does not match the search filter', function () {
    $at = Carbon::parse('2026-03-04 19:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $closed = onListing($owner, $tag, 'Closed', onHours($at->dayOfWeek, '09:00', '17:00'));

    expect(onWaitForTag($tag, 1))->toBeTrue('Precondition: the Listing should be indexed.');

    expect(onSearchNames($tag, 'open_now=1'))->toBe([]);
});

// ═══ THE DEFECT ITSELF: TIME MOVES, NOTHING IS REINDEXED ════════════════════

test('crossing an opening boundary changes the result without reindexing', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $listing = onListing($owner, $tag, 'Boundary', onHours($at->dayOfWeek, '09:00', '17:00'));
    $name = $tag . ' Boundary';

    expect(onWaitForTag($tag, 1))->toBeTrue('Precondition: the Listing should be indexed.');

    // Open at 10:00.
    expect(onSearchNames($tag, 'open_now=1'))->toBe([$name]);

    // Move PAST the closing time. No Listing is saved and the index is never
    // written again — the rule is evaluated live, which is the whole point.
    $this->travelTo(Carbon::parse('2026-03-04 17:30:00'));

    expect(onSearchNames($tag, 'open_now=1'))->toBe([]);

    // And back before the closing time: it returns, still with no write.
    $this->travelTo(Carbon::parse('2026-03-04 16:30:00'));

    expect(onSearchNames($tag, 'open_now=1'))->toBe([$name]);
});

// ═══ OVERRIDES FOLLOW THE EXISTING DOMAIN RULES ═════════════════════════════

test('a closed-day override excludes a Listing its weekly hours say is open', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $listing = onListing($owner, $tag, 'Holiday', onHours($at->dayOfWeek, '09:00', '17:00'));

    LocationHourOverride::create([
        'location_id' => $listing->location_id,
        'date' => $at->toDateString(),
        'is_closed' => true,
        'note' => 'Public holiday',
    ]);

    expect(onWaitForTag($tag, 1))->toBeTrue('Precondition: the Listing should be indexed.');

    expect(onSearchNames($tag, 'open_now=1'))->toBe([]);
});

test('a special-hours override opens a Listing whose weekly hours are closed', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();

    // Weekly hours on this day say closed; only the override opens it.
    $listing = onListing($owner, $tag, 'Special', [[
        'day_of_week' => $at->dayOfWeek,
        'opens_at' => '00:00',
        'closes_at' => '00:00',
        'is_closed' => true,
        'is_24h' => false,
    ]]);

    LocationHourOverride::create([
        'location_id' => $listing->location_id,
        'date' => $at->toDateString(),
        'is_closed' => false,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
        'note' => 'Special opening',
    ]);

    expect(onWaitForTag($tag, 1))->toBeTrue('Precondition: the Listing should be indexed.');

    // Judged SOLELY on the override.
    expect(onSearchNames($tag, 'open_now=1'))->toBe([$tag . ' Special']);
});

// ═══ PAGINATION IS NOT SILENTLY TRUNCATED ═══════════════════════════════════

test('the opening filter paginates the narrowed set instead of truncating a page', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $open = [];

    // More open Listings than one page (PER_PAGE = 12), plus closed decoys that
    // must not consume pagination slots.
    for ($i = 0; $i < 13; $i++) {
        $open[] = onListing($owner, $tag, 'Open' . $i, onHours($at->dayOfWeek, '09:00', '17:00'));
    }

    $closed = [
        onListing($owner, $tag, 'ClosedA', onHours($at->dayOfWeek, '18:00', '20:00')),
        onListing($owner, $tag, 'ClosedB', onHours($at->dayOfWeek, '18:00', '20:00')),
    ];

    expect(onWaitForTag($tag, 15))->toBeTrue('Precondition: every Listing should be indexed.');

    $page1 = onSearchPaginator($tag, 'open_now=1');

    // 13 eligible results: a full first page and a real second page. A
    // post-retrieval filter would have returned a short page here, or a total
    // that counted rows the filter then removed.
    expect($page1['total'])->toBe(13)
        ->and($page1['per_page'])->toBe(12)
        ->and($page1['last_page'])->toBe(2)
        ->and(count($page1['data']))->toBe(12);

    $page2 = onSearchPaginator($tag, 'open_now=1&page=2');

    expect(count($page2['data']))->toBe(1);
});

// ═══ ORDINARY SEARCH IS UNCHANGED ═══════════════════════════════════════════

test('ordinary search without the opening filter is unchanged', function () {
    $at = Carbon::parse('2026-03-04 19:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $open = onListing($owner, $tag, 'OrdinaryOpen', onHours($at->dayOfWeek, '09:00', '17:00'));
    $closed = onListing($owner, $tag, 'OrdinaryClosed', onHours($at->dayOfWeek, '09:00', '17:00'));

    expect(onWaitForTag($tag, 2))->toBeTrue('Precondition: both Listings should be indexed.');

    // No `open_now`: BOTH are ordinary search results, even though one is
    // currently closed.
    $names = collect(onSearchNames($tag))->sort()->values()->all();

    expect($names)->toBe([$tag . ' OrdinaryClosed', $tag . ' OrdinaryOpen']);
});

test('the search and directory opening filters agree', function () {
    $at = Carbon::parse('2026-03-04 10:00:00');
    $this->travelTo($at);

    $tag = onTag();
    $owner = onOwner();
    $open = onListing($owner, $tag, 'AgreeOpen', onHours($at->dayOfWeek, '09:00', '17:00'));
    $closed = onListing($owner, $tag, 'AgreeClosed', onHours($at->dayOfWeek, '18:00', '20:00'));

    expect(onWaitForTag($tag, 2))->toBeTrue('Precondition: both Listings should be indexed.');

    $search = collect(onSearchNames($tag, 'open_now=1'))->sort()->values()->all();
    $directory = collect(onDirectoryNames($tag, 'open_now=true'))->sort()->values()->all();

    // The live predicate is defined ONCE, so the two engines cannot disagree.
    expect($search)->toBe($directory)
        ->and($search)->toBe([$tag . ' AgreeOpen']);
});
