<?php

use App\Models\Location;
use App\Models\User;
use App\Support\LocationDistance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * PHASE 22E-2 — BACKEND DISTANCE PRIMITIVE.
 *
 * Expected values were established INDEPENDENTLY of the implementation under
 * test, using MariaDB's `ST_Distance_Sphere` (a different method from the
 * Haversine expression being verified). The two agree to within one metre on
 * every pair below, which is why the tolerances are tight rather than generous.
 *
 * Helper names are prefixed: Pest shares one process across every test file.
 */

/** Haversine distance in km, measured through the real production expression. */
function ldKm(float $fromLat, float $fromLng, int $locationId): ?float
{
    [$sql, $bindings] = LocationDistance::kmExpression($fromLat, $fromLng);

    $row = DB::table('locations')
        // The documented caller contract: NULL coordinates are excluded.
        ->whereNotNull('locations.latitude')
        ->whereNotNull('locations.longitude')
        ->where('locations.id', $locationId)
        ->selectRaw($sql . ' AS distance_km', $bindings)
        ->first();

    return $row?->distance_km === null ? null : (float) $row->distance_km;
}

function ldInsert(float $latitude, float $longitude): int
{
    return DB::table('locations')->insertGetId([
        'owner_id' => User::factory()->owner()->create()->id,
        'name' => 'Distance probe',
        'status' => Location::STATUS_ACTIVE,
        'is_primary' => false,
        'sort_order' => 0,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ═══ SAME POINT ═════════════════════════════════════════════════════════════

test('an identical coordinate pair returns exactly zero', function () {
    $id = ldInsert(4.0511, 9.7679);

    expect(ldKm(4.0511, 9.7679, $id))->toBe(0.0);
});

// ═══ KNOWN ASYMMETRIC PAIR ══════════════════════════════════════════════════

test('a known asymmetric coordinate pair returns the expected distance', function () {
    // Buea (4.1527, 9.2410) -> Douala (4.0511, 9.7679).
    // Independent oracle: ST_Distance_Sphere = 59.520 km.
    $id = ldInsert(4.0511, 9.7679);

    expect(ldKm(4.1527, 9.2410, $id))
        ->toEqualWithDelta(59.520, 0.05);
});

// ═══ ARGUMENT ORDER — LATITUDE FIRST, LONGITUDE SECOND ══════════════════════

test('reversing latitude and longitude arguments produces a detectably different distance', function () {
    // A HIGH-LATITUDE pair is used deliberately. Near the equator the two
    // orderings give nearly identical answers — the Cameroon pair above differs
    // by only 0.12 km (59.520 vs 59.639), so a tolerance-based assertion would
    // pass for either ordering and prove nothing.
    //
    // The Location is FIXED at (lat 60, lng 20). Only the REQUEST coordinates
    // swap: the correct call is (lat 60, lng 10), the reversed call is
    // (lat 10, lng 60). Independent oracle (ST_Distance_Sphere):
    //   555.444 km correct, 6466.798 km reversed.
    $id = ldInsert(60.0, 20.0);

    $correct = ldKm(60.0, 10.0, $id);
    expect($correct)->toEqualWithDelta(555.444, 0.5);

    // The reversed call must be the WRONG, far larger value — asserted
    // explicitly so a future argument-order regression cannot hide inside the
    // tolerance.
    $reversed = ldKm(10.0, 60.0, $id);
    expect($reversed)->toEqualWithDelta(6466.798, 0.5);
    expect(abs($correct - $reversed))->toBeGreaterThan(5000);
});

// ═══ NULL-COORDINATE EXCLUSION ══════════════════════════════════════════════

test('locations with null coordinates are excluded from distance results', function () {
    $withCoords = ldInsert(4.0511, 9.7679);

    // A coordinate-less Location is valid (both NULL), but has no distance.
    DB::table('locations')->insert([
        'owner_id' => User::factory()->owner()->create()->id,
        'name' => 'No coordinates',
        'status' => Location::STATUS_ACTIVE,
        'is_primary' => false,
        'sort_order' => 0,
        'latitude' => null,
        'longitude' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    [$sql, $bindings] = LocationDistance::kmExpression(4.1527, 9.2410);

    // The documented caller contract excludes it...
    $filtered = DB::table('locations')
        ->whereNotNull('locations.latitude')
        ->whereNotNull('locations.longitude')
        ->selectRaw($sql . ' AS distance_km', $bindings)
        ->pluck('distance_km');

    expect($filtered)->toHaveCount(1);

    // ...and without that filter the row is NOT usable: its distance is NULL
    // rather than a number, which is exactly why the contract exists.
    $unfiltered = DB::table('locations')
        ->selectRaw($sql . ' AS distance_km', $bindings)
        ->pluck('distance_km');

    expect($unfiltered)->toHaveCount(2);
    expect($unfiltered->filter(fn ($d) => $d === null))->toHaveCount(1);
    expect($unfiltered->filter(fn ($d) => $d !== null))->toHaveCount(1);

    expect(ldKm(4.1527, 9.2410, $withCoords))->toBeGreaterThan(0);
});

// ═══ CLAMP SAFETY ═══════════════════════════════════════════════════════════

test('the haversine intermediate value is clamped so the result is never null', function () {
    [$sql, $bindings] = LocationDistance::kmExpression(0.0, 0.0);

    // The upper clamp is present in the emitted SQL.
    expect($sql)->toContain('LEAST(1,');
    expect($bindings)->toBe([0.0, 0.0, 0.0]);

    // Antipodal-ish extremes drive the intermediate term toward its maximum,
    // where float drift could exceed 1 and make ASIN a domain error (or, as
    // ACOS does on this engine, silently return NULL).
    //
    // (0.0, 180.0) against a request of (0.0, 0.0) is exactly antipodal: the
    // intermediate term is 1.0, the boundary the clamp exists to protect.
    foreach ([[90.0, 180.0], [-90.0, -180.0], [90.0, -180.0], [0.0, 180.0]] as [$lat, $lng]) {
        $id = ldInsert($lat, $lng);

        $value = ldKm(0.0, 0.0, $id);

        expect($value)->not->toBeNull()
            ->and(is_finite($value))->toBeTrue()
            ->and($value)->toBeGreaterThan(0);
    }
});

// ═══ BINDING ORDER ══════════════════════════════════════════════════════════

test('bindings are ordered latitude, latitude, longitude and are never interpolated', function () {
    [$sql, $bindings] = LocationDistance::kmExpression(12.34, 56.78);

    // Exact documented order: latitude appears twice.
    expect($bindings)->toBe([12.34, 12.34, 56.78]);

    // Three placeholders, and no coordinate literal anywhere in the SQL.
    expect(substr_count($sql, '?'))->toBe(3);
    expect($sql)->not->toContain('12.34');
    expect($sql)->not->toContain('56.78');
});
