<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1C — CATEGORY CONVERGENCE.
 *
 * The canonical discovery taxonomy is:
 *
 *     Listing → listing_categories → Category
 *
 * An organization does NOT own a stored taxonomy. Its categories are DERIVED
 * from its Listings, and two Listings under one Business may differ.
 */

test('listing_categories is the canonical taxonomy and business_categories is gone', function () {
    expect(Schema::hasTable('listing_categories'))->toBeTrue();
    expect(Schema::hasTable('business_categories'))->toBeFalse();

    expect(Schema::hasColumn('listing_categories', 'listing_id'))->toBeTrue();
    expect(Schema::hasColumn('listing_categories', 'category_id'))->toBeTrue();
});

test('a listing can have multiple categories', function () {
    $listing = Listing::factory()->published()->create();
    $a = Category::factory()->create(['name' => 'Restaurants', 'slug' => 'restaurants']);
    $b = Category::factory()->create(['name' => 'Seafood', 'slug' => 'seafood']);

    $listing->categories()->attach([$a->id, $b->id]);

    expect($listing->categories()->count())->toBe(2);
    expect($listing->fresh()->categories->pluck('id')->sort()->values()->all())
        ->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

test('a category can have multiple listings', function () {
    $category = Category::factory()->create();

    $a = Listing::factory()->published()->create();
    $b = Listing::factory()->published()->create();

    $a->categories()->attach($category->id);
    $b->categories()->attach($category->id);

    expect($category->listings()->count())->toBe(2);
});

test('two listings of the same business can have different categories', function () {
    $business = Business::factory()->create();

    $restaurant = Listing::factory()->forBusiness($business)->published()->create(['name' => 'ABC Restaurant']);
    $hotel = Listing::factory()->forBusiness($business)->published()->create(['name' => 'ABC Hotel']);

    $restaurantCategory = Category::factory()->create(['name' => 'Restaurant', 'slug' => 'restaurant']);
    $hotelCategory = Category::factory()->create(['name' => 'Hotel', 'slug' => 'hotel']);

    $restaurant->categories()->attach($restaurantCategory->id);
    $hotel->categories()->attach($hotelCategory->id);

    // Each listing keeps ONLY its own category — no business-wide inheritance.
    expect($restaurant->categories()->pluck('categories.id')->all())->toBe([$restaurantCategory->id]);
    expect($hotel->categories()->pluck('categories.id')->all())->toBe([$hotelCategory->id]);
});

test('business categories are derived from its listings, not a stored pivot', function () {
    $business = Business::factory()->create();

    $a = Listing::factory()->forBusiness($business)->published()->create();
    $b = Listing::factory()->forBusiness($business)->published()->create();

    $catA = Category::factory()->create(['name' => 'Restaurants', 'slug' => 'restaurants']);
    $catB = Category::factory()->create(['name' => 'Hotels', 'slug' => 'hotels']);

    $a->categories()->attach($catA->id, ['is_primary' => true]);
    $b->categories()->attach($catB->id);

    // Derived union across the organization's listings…
    expect($business->categories->pluck('id')->sort()->values()->all())
        ->toBe(collect([$catA->id, $catB->id])->sort()->values()->all());

    // …and there is no stored organization-level row to disagree with it.
    expect(Schema::hasTable('business_categories'))->toBeFalse();
});

test('organization matching by category resolves through its listings', function () {
    $category = Category::factory()->create();

    $withListing = Business::factory()->published()->create();
    $listing = Listing::factory()->forBusiness($withListing)->published()->create();
    $listing->categories()->attach($category->id);

    $withoutListing = Business::factory()->published()->create();

    $matched = Business::query()
        ->withDiscoverableListingInCategory($category->id)
        ->pluck('id');

    expect($matched)->toContain($withListing->id);
    expect($matched)->not->toContain($withoutListing->id);
});

test('no production code references the removed business_categories taxonomy', function () {
    $offenders = [];

    foreach ([app_path(), base_path('routes'), resource_path('js')] as $root) {
        if (!File::isDirectory($root)) {
            continue;
        }

        foreach (File::allFiles($root) as $file) {
            if (!in_array($file->getExtension(), ['php', 'js', 'vue'], true)) {
                continue;
            }

            foreach (File::lines($file->getPathname()) as $number => $line) {
                if (!str_contains($line, 'business_categories')) {
                    continue;
                }

                // Documentation explaining the removal is legitimate; an
                // executable reference is not.
                $trimmed = ltrim($line);

                if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*')) {
                    continue;
                }

                $offenders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()) . ':' . ($number + 1);
            }
        }
    }

    expect($offenders)->toBe([], 'Stale business_categories references: ' . implode(', ', $offenders));
});
