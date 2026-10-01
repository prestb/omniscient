<?php

use App\Models\Business;
use App\Models\Listing;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1B — `listing_id` is a REQUIRED ownership foreign key.
 *
 * A Listing-owned child cannot exist without a Listing. These tests assert the
 * invariant at the DATABASE level (raw inserts, not Eloquent relations), so a
 * regression in the schema — or a false fallback owner — cannot pass silently.
 */

dataset('listing_owned_tables', [
    'listing_services' => ['listing_services', ['name' => 'Embroidery']],
    'listing_images' => ['listing_images', ['path' => 'listings/x/logo.png', 'type' => 'logo']],
    'listing_contacts' => ['listing_contacts', ['type' => 'phone', 'value' => '+237600000000']],
    'listing_analytics' => ['listing_analytics', ['date' => '2026-01-01']],
]);

test('listing_id is NOT NULL on every listing-owned table', function () {
    foreach (['listing_services', 'listing_images', 'listing_contacts', 'listing_analytics'] as $table) {
        $column = collect(Schema::getColumns($table))->firstWhere('name', 'listing_id');

        expect($column)->not->toBeNull("{$table}.listing_id must exist");
        expect($column['nullable'])->toBeFalse("{$table}.listing_id must be NOT NULL");
    }
});

test('no listing-owned table retains business_id', function () {
    foreach (['listing_services', 'listing_images', 'listing_contacts', 'listing_analytics'] as $table) {
        expect(Schema::hasColumn($table, 'business_id'))
            ->toBeFalse("{$table} must not retain business_id");
    }
});

test('the database rejects an orphan child row with a NULL listing_id', function (string $table, array $payload) {
    expect(fn () => DB::table($table)->insert($payload + ['listing_id' => null]))
        ->toThrow(QueryException::class);
})->with('listing_owned_tables');

test('the database rejects a child row referencing an unknown listing', function (string $table, array $payload) {
    expect(fn () => DB::table($table)->insert($payload + ['listing_id' => 99999999]))
        ->toThrow(QueryException::class);
})->with('listing_owned_tables');

test('a child row persists when it has a real listing', function (string $table, array $payload) {
    $business = Business::factory()->create();
    $listing = Listing::factory()->forBusiness($business)->create();

    DB::table($table)->insert($payload + ['listing_id' => $listing->id]);

    expect(DB::table($table)->where('listing_id', $listing->id)->count())->toBe(1);
})->with('listing_owned_tables');
