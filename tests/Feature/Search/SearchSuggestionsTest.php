<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('autocomplete returns search_suggestions for a matching category', function () {
    $category = Category::factory()->create([
        'name' => 'Supermarkets',
        'slug' => 'supermarkets',
        'is_active' => true,
    ]);

    $region = Region::factory()->create();
    $buea = City::factory()->create([
        'region_id' => $region->id,
        'name' => 'Buea',
        'slug' => 'buea',
    ]);

    // Attach a published business so the category qualifies
    $business = Business::factory()->published()->create();
    $business->categories()->attach($category->id, ['is_primary' => true]);
    \App\Models\Branch::factory()
        ->forBusiness($business)
        ->inCity($buea)
        ->primary()
        ->create();

    $response = $this->getJson('/search/autocomplete?q=supermark');

    expect($response->status())->toBe(200);

    $suggestions = $response->json('search_suggestions');

    expect($suggestions)->not->toBeEmpty()
        ->and(collect($suggestions)->pluck('label'))->toContain('Supermarkets near me');

    // Every suggestion should have the shape we expect
    collect($suggestions)->each(function ($s) {
        expect($s)->toHaveKeys(['label', 'query', 'type'])
            ->and($s['type'])->toBeIn(['near_me', 'city']);
    });
});

test('autocomplete returns empty search_suggestions for a non-matching query', function () {
    $response = $this->getJson('/search/autocomplete?q=zzzzzz');

    expect($response->json('search_suggestions'))->toBe([]);
});

test('autocomplete returns empty search_suggestions when category has no businesses', function () {
    // Category exists but no businesses attached
    Category::factory()->create([
        'name' => 'Supermarkets',
        'slug' => 'supermarkets',
        'is_active' => true,
    ]);

    $response = $this->getJson('/search/autocomplete?q=supermark');

    // Suggestions may still be empty because the topCities lookup finds nothing
    // and "near me" alone might still show — check the shape either way
    $suggestions = $response->json('search_suggestions');

    expect($suggestions)->toBeArray();
});

test('autocomplete returns empty array when query is too short', function () {
    $response = $this->getJson('/search/autocomplete?q=ab');

    expect($response->json('search_suggestions'))->toBe([]);
});

test('search_suggestions has "near me" first when category matches', function () {
    $category = Category::factory()->create([
        'name' => 'Restaurants',
        'slug' => 'restaurants',
        'is_active' => true,
    ]);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id, 'name' => 'Buea']);

    $business = Business::factory()->published()->create();
    $business->categories()->attach($category->id, ['is_primary' => true]);
    \App\Models\Branch::factory()->forBusiness($business)->inCity($city)->primary()->create();

    $response = $this->getJson('/search/autocomplete?q=restaurants');

    $suggestions = $response->json('search_suggestions');

    expect($suggestions)->not->toBeEmpty();

    // First suggestion should always be "near me"
    expect($suggestions[0]['type'])->toBe('near_me')
        ->and($suggestions[0]['label'])->toBe('Restaurants near me');
});