<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21C-R1 §17 — REGRESSION: the `min_rating` directory filter.
 *
 * The public directory filter used raw SQL:
 *
 *     (SELECT AVG(rating) FROM reviews WHERE reviews.business_id = listings.business_id ...)
 *
 * When this phase dropped `reviews.business_id`, every `min_rating` request
 * started raising a SQL error. Nothing caught it, because no test exercised the
 * filter — the string-level checks all passed while the endpoint was broken.
 *
 * This test drives the REAL public directory route. It does not assert on SQL
 * text; it asserts that the endpoint works and filters correctly under
 * Listing-owned reputation.
 */

function mrOwner(): User
{
    $owner = User::factory()->owner()->create();

    $plan = Plan::factory()->create([
        'tier' => 'free',
        'max_listings' => 10,
        'max_locations' => 3,
        'max_images' => 10,
        'features' => ['lead_capture' => true],
    ]);

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return $owner;
}

function mrListing(User $owner, string $name, ?Business $business = null): Listing
{
    return Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => $business?->id,
        'name' => $name,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
}

test('the directory min_rating filter does not error', function () {
    $owner = mrOwner();
    $listing = mrListing($owner, 'Regression Plumbing');

    Review::factory()->for($listing)->approved()->rated(5)->create();

    // The defect surfaced as a 500. This is the assertion that would have caught it.
    $this->get('/directory?min_rating=4')->assertOk();
});

test('the directory min_rating filter keeps listings at or above the threshold', function () {
    $owner = mrOwner();
    $high = mrListing($owner, 'Rated High Co');
    $low = mrListing($owner, 'Rated Low Co');

    Review::factory()->for($high)->approved()->rated(5)->create();
    Review::factory()->for($low)->approved()->rated(2)->create();

    $props = $this->get('/directory?min_rating=4')->assertOk()->viewData('page')['props'];

    $names = collect($props['listings']['data'] ?? $props['listings'] ?? [])->pluck('name')->all();

    expect($names)->toContain('Rated High Co');
    expect($names)->not->toContain('Rated Low Co');
});

test('the directory min_rating filter excludes listings with no reviews', function () {
    $owner = mrOwner();
    $reviewed = mrListing($owner, 'Has Reviews Co');
    mrListing($owner, 'Unreviewed Co');

    Review::factory()->for($reviewed)->approved()->rated(5)->create();

    $props = $this->get('/directory?min_rating=1')->assertOk()->viewData('page')['props'];
    $names = collect($props['listings']['data'] ?? $props['listings'] ?? [])->pluck('name')->all();

    expect($names)->toContain('Has Reviews Co');
    expect($names)->not->toContain('Unreviewed Co');
});

test('the min_rating filter uses the LISTING own rating not a sibling', function () {
    // The raw SQL used to match the owning Business's reviews. Under Listing
    // ownership each Listing is judged on its OWN approved reviews.
    $owner = mrOwner();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $rated = mrListing($owner, 'Sibling Rated Co', $business);
    $unrated = mrListing($owner, 'Sibling Unrated Co', $business);

    Review::factory()->for($rated)->approved()->rated(5)->create();

    $props = $this->get('/directory?min_rating=4')->assertOk()->viewData('page')['props'];
    $names = collect($props['listings']['data'] ?? $props['listings'] ?? [])->pluck('name')->all();

    expect($names)->toContain('Sibling Rated Co');
    // Its sibling shares the organization but has no reviews of its own.
    expect($names)->not->toContain('Sibling Unrated Co');
});

test('the min_rating filter ignores unapproved reviews', function () {
    $owner = mrOwner();
    $listing = mrListing($owner, 'Pending Only Co');

    Review::factory()->for($listing)->pending()->rated(5)->create();

    $props = $this->get('/directory?min_rating=1')->assertOk()->viewData('page')['props'];
    $names = collect($props['listings']['data'] ?? $props['listings'] ?? [])->pluck('name')->all();

    // Pending reviews never contribute to public reputation.
    expect($names)->not->toContain('Pending Only Co');
});
