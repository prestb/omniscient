<?php

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-1 (continuation) — public browse discovers LISTINGS.
 *
 * A Business is an organization. Omniscient's public discovery layer must be
 * able to return every Listing of an organization independently, without ever
 * treating the Business itself as the discoverable result.
 */

test('the directory returns listings, not businesses', function () {
    $business = Business::factory()->published()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    $this->get('/directory')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Directory')
            ->has('listings.data', 1)
            ->where('listings.data.0.id', $listing->id)
            ->where('listings.data.0.type', 'listing')
            ->where('listings.data.0.listing_type', 'business'));
});

test('a business with no listings is never emitted as a public result', function () {
    Business::factory()->published()->create();

    $this->get('/directory')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
});

test('multiple listings under one business are returned as separate results', function () {
    $business = Business::factory()->published()->create();
    $a = Listing::factory()->forBusiness($business)->published()->create();
    $b = Listing::factory()->forBusiness($business)->published()->create();

    $this->get('/directory')
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($a, $b) {
            $page->has('listings.data', 2);

            $ids = collect($page->toArray()['props']['listings']['data'])->pluck('id')->sort()->values()->all();

            expect($ids)->toBe(collect([$a->id, $b->id])->sort()->values()->all());
        });
});

test('a locationless professional listing is still discoverable', function () {
    Listing::factory()->professional()->published()->create(['location_id' => null]);

    $this->get('/directory')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));
});

test('business-associated and standalone listings are both discoverable', function () {
    $business = Business::factory()->published()->create();

    Listing::factory()->forBusiness($business)->published()->create();
    Listing::factory()->published()->create(['business_id' => null, 'location_id' => null]);

    $this->get('/directory')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('listings.data', 2));
});

test('category filtering resolves through listing_categories', function () {
    $category = Category::factory()->create();

    $matching = Listing::factory()->published()->create();
    $matching->categories()->attach($category->id);

    $other = Listing::factory()->published()->create();

    $this->get('/directory?category=' . $category->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('listings.data', 1)
            ->where('listings.data.0.id', $matching->id));
});

test('city filtering resolves through the listing location', function () {
    $region = Region::factory()->create();
    $buea = City::factory()->create(['region_id' => $region->id, 'name' => 'Buea', 'slug' => 'buea']);
    $limbe = City::factory()->create(['region_id' => $region->id, 'name' => 'Limbe', 'slug' => 'limbe']);

    $business = Business::factory()->published()->create();

    $inBuea = Listing::factory()->forBusiness($business)->published()->create();
    $inBuea->location()->associate(Location::factory()->forBusiness($business)->inCity($buea)->create())->save();

    $inLimbe = Listing::factory()->forBusiness($business)->published()->create();
    $inLimbe->location()->associate(Location::factory()->forBusiness($business)->inCity($limbe)->create())->save();

    $this->get('/directory?city_id=' . $buea->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('listings.data', 1)
            ->where('listings.data.0.id', $inBuea->id));
});

test('category and location joins do not duplicate a listing row', function () {
    $category = Category::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    $business = Business::factory()->published()->create();
    $listing = Listing::factory()->forBusiness($business)->published()->create();
    $listing->categories()->attach($category->id);
    $listing->location()->associate(Location::factory()->forBusiness($business)->inCity($city)->create())->save();

    $this->get('/directory?category=' . $category->id . '&city_id=' . $city->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('listings.data', 1));
});

test('home discovery sections are listing-backed', function () {
    $featured = Listing::factory()->published()->create(['is_featured' => true]);
    $recent = Listing::factory()->published()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')
            ->has('featuredListings')
            ->has('recentListings')
            ->where('stats.listings', 2));
});
