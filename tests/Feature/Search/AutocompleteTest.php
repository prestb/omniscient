<?php

use App\Models\Business;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('autocomplete only returns categories with published businesses', function () {
    // Empty category — should NOT appear
    $emptyCategory = Category::factory()->create([
        'name' => 'Empty Category',
        'slug' => 'empty-category',
        'is_active' => true,
    ]);

    // Real category with a published business — should appear
    $realCategory = Category::factory()->create([
        'name' => 'Restaurants',
        'slug' => 'restaurants',
        'is_active' => true,
    ]);

    $business = Business::factory()->published()->create();
    $business->categories()->attach($realCategory->id, ['is_primary' => true]);

    $response = $this->getJson('/search/autocomplete?q=rest');

    $categoryNames = collect($response->json('category_suggestions'))->pluck('name');

    expect($categoryNames)->toContain('Restaurants')
        ->and($categoryNames)->not->toContain('Empty Category');
});

test('autocomplete uses prefix and word-boundary matching', function () {
    $restaurants = Category::factory()->create([
        'name' => 'Restaurants',
        'slug' => 'restaurants',
        'is_active' => true,
    ]);
    $provision = Category::factory()->create([
        'name' => 'Provision Stores',
        'slug' => 'provision-stores',
        'is_active' => true,
    ]);
    $clothing = Category::factory()->create([
        'name' => 'Clothing Stores',
        'slug' => 'clothing-stores',
        'is_active' => true,
    ]);

    // ✅ Each category needs a published business to appear in results
    foreach ([$restaurants, $provision, $clothing] as $category) {
        $business = Business::factory()->published()->create();
        $business->categories()->attach($category->id, ['is_primary' => true]);
    }

    $response = $this->getJson('/search/autocomplete?q=res');

    $categoryNames = collect($response->json('category_suggestions'))->pluck('name');

    expect($categoryNames)->toContain('Restaurants')
        ->and($categoryNames)->not->toContain('Provision Stores')
        ->and($categoryNames)->not->toContain('Clothing Stores');
});