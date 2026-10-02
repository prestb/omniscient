<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 19E — BUSINESS PUBLIC REACHABILITY.
 *
 * Phase 19D observed a published, non-hidden Business returning 404 from
 * /business/{slug}. Investigation established the cause:
 *
 *     DirectoryController::show(), lines 469-475
 *
 *         $owner = $business->owner;
 *         $hasActiveSubscription = $owner && $owner->active_subscription !== null;
 *         if (!$hasActiveSubscription) { abort(404); }
 *
 * The public Business ORGANIZATION page is deliberately gated on the owner
 * having an active subscription. The 19D fixture created a Business with no
 * owner and no subscription, so the gate correctly fired. That was a FIXTURE
 * error, not a product defect.
 *
 * This file pins the whole visibility contract so it can never be mistaken for
 * a regression again.
 *
 * NOTE: this is PAID VISIBILITY of an organization page. It is NOT the
 * paid-as-verification semantic that Phases 19B/19C removed — nothing here
 * claims a Listing is "verified" because someone paid.
 */

/** A Business whose owner holds an active subscription: publicly reachable. */
function reachableBusiness(array $attrs = []): Business
{
    $plan = Plan::factory()->create(['max_listings' => 10]);
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    return Business::factory()->published()->create(
        array_merge(['owner_id' => $owner->id, 'hidden_at' => null], $attrs)
    );
}

// ── A. Published + subscribed Business ──────────────────────────────────────

test('a published business with an active subscription is publicly reachable', function () {
    $business = reachableBusiness();

    $this->get('/business/' . $business->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/BusinessProfile'));
});

// ── B. Canonical Listing ────────────────────────────────────────────────────

test('the canonical listing remains publicly reachable', function () {
    $business = reachableBusiness();
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    $this->get('/listing/' . $listing->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/ListingProfile'));
});

// ── C. Hidden Business ──────────────────────────────────────────────────────

test('a hidden business is not publicly reachable even when subscribed', function () {
    $business = reachableBusiness(['hidden_at' => now()]);

    $this->get('/business/' . $business->slug)->assertNotFound();
});

// ── D. Unpublished Business ─────────────────────────────────────────────────

test('an unpublished business is not publicly reachable even when subscribed', function () {
    $business = reachableBusiness(['status' => Business::STATUS_DRAFT]);

    $this->get('/business/' . $business->slug)->assertNotFound();
});

// ── The subscription gate, stated explicitly ────────────────────────────────

test('a business without an active subscription is not publicly reachable', function () {
    // This is the exact condition the Phase 19D fixture hit: published and
    // visible, but the owner holds no subscription.
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->published()->create([
        'owner_id' => $owner->id,
        'hidden_at' => null,
    ]);

    expect($owner->active_subscription)->toBeNull();

    $this->get('/business/' . $business->slug)->assertNotFound();
});

test('the subscription gate is status-driven, not end_date-driven', function () {
    // INVESTIGATED, NOT ASSUMED.
    //
    // User::getActiveSubscriptionAttribute() filters on
    //     status IN ('active', 'expiring_soon', 'grace_period')
    // and NEVER consults end_date. Expiry is a STATUS TRANSITION performed by
    // the scheduled App\Console\Commands\CheckExpiringSubscriptions command.
    //
    // So a row that is still status='active' with a past end_date keeps the
    // public Business page open until that command flips the status. That is
    // the implemented contract, and this test records it rather than asserting
    // an assumption the code does not honour.
    $plan = Plan::factory()->create(['max_listings' => 10]);
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subYear(),
        'end_date' => now()->subDay(),
    ]);

    $business = Business::factory()->published()->create([
        'owner_id' => $owner->id,
        'hidden_at' => null,
    ]);

    // Status still 'active' -> the accessor returns it -> page stays open.
    expect($owner->active_subscription)->not->toBeNull();
    $this->get('/business/' . $business->slug)->assertOk();
});

test('a lapsed subscription closes the page once its status is transitioned', function () {
    // The other half of the same contract: once the status is no longer one of
    // the active states, the organization page closes.
    $plan = Plan::factory()->create(['max_listings' => 10]);
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_EXPIRED,
        'start_date' => now()->subYear(),
        'end_date' => now()->subDay(),
    ]);

    $business = Business::factory()->published()->create([
        'owner_id' => $owner->id,
        'hidden_at' => null,
    ]);

    expect($owner->active_subscription)->toBeNull();
    $this->get('/business/' . $business->slug)->assertNotFound();
});

test('the subscription gate applies to the organization page only, not the listings', function () {
    // The Listing is the canonical DISCOVERABLE entity and stays reachable even
    // when the organization page is closed. Exactly the asymmetry Phase 19D saw.
    $owner = User::factory()->owner()->create();
    $business = Business::factory()->published()->create([
        'owner_id' => $owner->id,
        'hidden_at' => null,
    ]);
    $listing = Listing::factory()->forBusiness($business)->published()->create();

    $this->get('/business/' . $business->slug)->assertNotFound();
    $this->get('/listing/' . $listing->slug)->assertOk();
});

// ── E. Business-less Professional ───────────────────────────────────────────

test('a business-less professional remains publicly reachable without any business route', function () {
    $owner = User::factory()->owner()->create();
    $listing = Listing::factory()->published()->create([
        'owner_id' => $owner->id,
        'business_id' => null,
        'location_id' => null,
    ]);

    expect($listing->business_id)->toBeNull();

    $this->get('/listing/' . $listing->slug)->assertOk();
});

// ── Business → Listings contract ────────────────────────────────────────────

test('a reachable business exposes its listings as distinct public identities', function () {
    $business = reachableBusiness(['slug' => 'acme-group']);

    $a = Listing::factory()->forBusiness($business)->published()->create(['slug' => 'acme-one']);
    $b = Listing::factory()->forBusiness($business)->published()->create(['slug' => 'acme-two']);

    $this->get('/business/acme-group')->assertOk();

    // Each Listing keeps its own canonical identity, independent of the
    // organization page.
    $this->get('/listing/acme-one')->assertOk();
    $this->get('/listing/acme-two')->assertOk();

    expect($a->slug)->not->toBe($b->slug);
    expect($business->slug)->not->toBe($a->slug);
});

test('a business with zero listings is still a reachable organization page', function () {
    // Business -> zero or more Listings. Zero is a valid state.
    $business = reachableBusiness();

    expect($business->listings()->count())->toBe(0);
    $this->get('/business/' . $business->slug)->assertOk();
});
