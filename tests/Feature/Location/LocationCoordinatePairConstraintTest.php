<?php

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 22E-1 — COORDINATE PAIR INTEGRITY (DATABASE LEVEL).
 *
 * `LocationLifecycleTest` already covers the APPLICATION validation rule
 * (`required_with`). These tests exercise the table-level CHECK constraint, so
 * they deliberately bypass Eloquent with raw inserts. A validation rule only
 * protects HTTP callers; the constraint protects every writer — seeders,
 * imports, console commands and direct queries.
 *
 * Helper names are prefixed: Pest shares one process across every test file.
 */

const CP_CONSTRAINT = 'locations_coordinate_pair_check';

function cpOwnerId(): int
{
    return User::factory()->owner()->create()->id;
}

/** Raw insert that bypasses model validation and mass-assignment overrides. */
function cpInsert(int $ownerId, mixed $latitude, mixed $longitude): void
{
    DB::table('locations')->insert([
        'owner_id' => $ownerId,
        'name' => 'Coord probe',
        'status' => Location::STATUS_ACTIVE,
        'is_primary' => false,
        'sort_order' => 0,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function cpConstraintExists(): bool
{
    return DB::table('information_schema.TABLE_CONSTRAINTS')
        ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
        ->where('TABLE_NAME', 'locations')
        ->where('CONSTRAINT_NAME', CP_CONSTRAINT)
        ->exists();
}

// ═══ THE CONSTRAINT IS PRESENT ══════════════════════════════════════════════

test('the coordinate pair check constraint exists on locations', function () {
    expect(Schema::hasTable('locations'))->toBeTrue();
    expect(cpConstraintExists())->toBeTrue();
});

// ═══ ACCEPTED STATES ════════════════════════════════════════════════════════

test('a location with both coordinates null is accepted by the database', function () {
    cpInsert(cpOwnerId(), null, null);

    $row = DB::table('locations')->first();

    expect($row)->not->toBeNull();
    expect($row->latitude)->toBeNull();
    expect($row->longitude)->toBeNull();
});

test('a location with both coordinates populated is accepted by the database', function () {
    cpInsert(cpOwnerId(), 4.0511, 9.7679);

    $row = DB::table('locations')->first();

    expect($row)->not->toBeNull();
    expect((float) $row->latitude)->toBe(4.0511);
    expect((float) $row->longitude)->toBe(9.7679);
});

// ═══ REJECTED STATES — ENFORCED BY THE DATABASE, NOT BY VALIDATION ══════════

test('the database rejects a location with latitude but no longitude', function () {
    $ownerId = cpOwnerId();

    expect(fn () => cpInsert($ownerId, 4.0511, null))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(DB::table('locations')->count())->toBe(0);
});

test('the database rejects a location with longitude but no latitude', function () {
    $ownerId = cpOwnerId();

    expect(fn () => cpInsert($ownerId, null, 9.7679))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(DB::table('locations')->count())->toBe(0);
});

// ═══ THE CONSTRAINT HOLDS ON UPDATE TOO ════════════════════════════════════

test('the database rejects clearing only one coordinate of an existing location', function () {
    cpInsert(cpOwnerId(), 4.0511, 9.7679);
    $id = DB::table('locations')->value('id');

    expect(fn () => DB::table('locations')->where('id', $id)->update(['longitude' => null]))
        ->toThrow(\Illuminate\Database\QueryException::class);

    // The row is unchanged: both coordinates survive.
    $row = DB::table('locations')->where('id', $id)->first();
    expect((float) $row->latitude)->toBe(4.0511);
    expect((float) $row->longitude)->toBe(9.7679);
});
