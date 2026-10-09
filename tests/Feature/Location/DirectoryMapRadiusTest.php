<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 22E-3 — SERVER-SIDE DIRECTORY MAP RADIUS FILTERING.
 *
 * Origin is Douala (4.0511, 9.7679). Reference distances, established with
 * ST_Distance_Sphere independently of the implementation:
 *
 *   same point   (4.0511, 9.7679) ->   0.000 km
 *   Buea         (4.1527, 9.2410) ->  59.520 km
 *
 * Helper names are prefixed: Pest shares one process across every test file.
 */

function dmrOwner(): User
{
    return User::factory()->owner()->create();
}

/** A published Listing whose Location carries the given coordinates. */
function dmrListingAt(?float $latitude, ?float $longitude): Listing
{
    $owner = dmrOwner();

    $location = Location::factory()->forOwner($owner)->standalone()->create([
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);

    return Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'location_id' => $location->id,
    ]);
}

function dmrMapIds(string $query): array
{
    $props = test()->get('/directory?view=map' . $query)->assertOk()->viewData('page')['props'];

    return collect($props['listings']['data'])->pluck('id')->all();
}

function dmrDouala(): string
{
    return '&latitude=4.0511&longitude=9.7679';
}

// ═══ RADIUS SELECTION ═══════════════════════════════════════════════════════

test('a listing inside the selected radius is included', function () {
    $near = dmrListingAt(4.0511, 9.7679);   // 0 km from the origin

    $ids = dmrMapIds(dmrDouala() . '&radius=25');

    expect($ids)->toContain($near->id);
});

test('a listing outside the selected radius is excluded', function () {
    $near = dmrListingAt(4.0511, 9.7679);   //   0 km
    $far = dmrListingAt(4.1527, 9.2410);    //  59.5 km (Buea)

    $ids = dmrMapIds(dmrDouala() . '&radius=25');

    expect($ids)->toContain($near->id);
    expect($ids)->not->toContain($far->id);
});

test('a listing without coordinates is excluded when a radius is active', function () {
    $near = dmrListingAt(4.0511, 9.7679);
    $coordless = dmrListingAt(null, null);

    $ids = dmrMapIds(dmrDouala() . '&radius=25');

    expect($ids)->toContain($near->id);
    expect($ids)->not->toContain($coordless->id);
});

// ═══ VALIDATION ═════════════════════════════════════════════════════════════

test('out-of-range latitude is rejected', function () {
    $this->get('/directory?view=map&latitude=91&longitude=9.7679&radius=25')
        ->assertSessionHasErrors('latitude');
});

test('out-of-range longitude is rejected', function () {
    $this->get('/directory?view=map&latitude=4.0511&longitude=181&radius=25')
        ->assertSessionHasErrors('longitude');
});

test('an unsupported radius is rejected', function () {
    $this->get('/directory?view=map&latitude=4.0511&longitude=9.7679&radius=7')
        ->assertSessionHasErrors('radius');
});

test('supplying only one origin coordinate is rejected', function () {
    $this->get('/directory?view=map&latitude=4.0511&radius=25')
        ->assertSessionHasErrors('longitude');

    $this->get('/directory?view=map&longitude=9.7679&radius=25')
        ->assertSessionHasErrors('latitude');
});

test('a radius without an origin is rejected', function () {
    $this->get('/directory?view=map&radius=25')
        ->assertSessionHasErrors(['latitude', 'longitude']);
});

// ═══ COMPOSITION AND NON-REGRESSION ═════════════════════════════════════════

test('existing filters still apply alongside the radius', function () {
    $category = \App\Models\Category::factory()->create();

    $insideMatching = dmrListingAt(4.0511, 9.7679);
    $insideMatching->categories()->attach($category->id);

    $insideOther = dmrListingAt(4.0511, 9.7679);

    $ids = dmrMapIds(dmrDouala() . '&radius=25&category=' . $category->id);

    expect($ids)->toContain($insideMatching->id);
    expect($ids)->not->toContain($insideOther->id);
});

test('map mode without a radius keeps its previous behaviour', function () {
    $near = dmrListingAt(4.0511, 9.7679);
    $far = dmrListingAt(4.1527, 9.2410);    // outside any radius, but still returned

    $ids = dmrMapIds('');

    expect($ids)->toContain($near->id);
    expect($ids)->toContain($far->id);
});

test('list mode is unaffected by radius parameters', function () {
    $near = dmrListingAt(4.0511, 9.7679);
    $far = dmrListingAt(4.1527, 9.2410);

    $props = $this->get('/directory?latitude=4.0511&longitude=9.7679&radius=25')
        ->assertOk()
        ->viewData('page')['props'];

    // List mode paginates and is not radius-filtered.
    expect($props['viewMode'])->toBe('list');
    $ids = collect($props['listings']['data'])->pluck('id')->all();
    expect($ids)->toContain($near->id);
    expect($ids)->toContain($far->id);
});
