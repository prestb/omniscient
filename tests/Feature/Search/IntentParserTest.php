<?php

use App\Models\Category;
use App\Models\City;
use App\Services\SearchIntentParser;
use Illuminate\Support\Facades\Cache;

// ✅ Parser caches category/city maps — clear so fresh test data is picked up
beforeEach(function () {
    Cache::flush();
});

test('parses "restaurants in buea" into category and city ids', function () {
    $category = Category::factory()->create([
        'name' => 'Restaurants',
        'slug' => 'restaurants',
        'is_active' => true,
    ]);
    $city = City::factory()->create([
        'name' => 'Buea',
        'slug' => 'buea',
        'is_active' => true,
    ]);

    $result = app(SearchIntentParser::class)->parse('restaurants in buea');

    expect($result['category_id'])->toBe($category->id)
        ->and($result['city_id'])->toBe($city->id)
        ->and($result['cleaned_query'])->toBeNull();
});

test('parses "near" as a separator like "in"', function () {
    $category = Category::factory()->create(['name' => 'Hotels', 'slug' => 'hotels']);
    $city = City::factory()->create(['name' => 'Douala', 'slug' => 'douala']);

    $result = app(SearchIntentParser::class)->parse('hotels near douala');

    expect($result['category_id'])->toBe($category->id)
        ->and($result['city_id'])->toBe($city->id);
});

test('returns null intent for unmatched queries', function () {
    $result = app(SearchIntentParser::class)->parse('random words');

    expect($result['category_id'])->toBeNull()
        ->and($result['city_id'])->toBeNull()
        ->and($result['cleaned_query'])->toBe('random words');
});