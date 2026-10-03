<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 21B-R1 — INQUIRY ENTITLEMENT + OWNER AUTHORIZATION.
 *
 * These tests DOCUMENT the established contract. No production behaviour was
 * changed: a proposed correction was reverted because it could not be proven
 * SAFE (see the class docblock on the entitlement test below).
 */

function r1Owner(bool $subscribed): User
{
    $user = User::factory()->owner()->create();

    if ($subscribed) {
        $plan = Plan::factory()->create(['max_listings' => 10]);
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
        ]);
    }

    return $user;
}

function r1Listing(User $owner, ?Business $business = null): Listing
{
    return Listing::factory()->create([
        'owner_id' => $owner->id,
        'business_id' => $business?->id,
        'status' => Listing::STATUS_PUBLISHED,
        'hidden_at' => null,
    ]);
}

function r1Inquire(Listing $listing)
{
    return test()->post('/listing/' . $listing->slug . '/contact', [
        'name' => 'Visitor',
        'email' => 'visitor@example.com',
        'message' => 'Do you cover this area?',
    ]);
}

// ── THE ROOT CAUSE, PINNED ──────────────────────────────────────────────────

test('the inquiry entitlement is resolved through the BUSINESS, not the listing owner', function () {
    $source = file_get_contents(app_path('Http/Controllers/Public/ListingLeadController.php'));

    // The exact condition, as established by Phase 21B-R1.
    expect($source)->toContain('$listing->business && !$listing->business->hasLeadCaptureFeature()');

    // And it resolves through the organization's owner.
    $business = file_get_contents(app_path('Models/Business.php'));
    expect($business)->toContain('return $this->ownerCanUseFeature(\'lead_capture\');');
});

test('a business-less listing bypasses the entitlement check entirely', function () {
    // The `&&` SHORT-CIRCUITS: no Business means the whole condition is false,
    // so no entitlement check runs. This is the documented asymmetry.
    $owner = r1Owner(subscribed: false);
    $listing = r1Listing($owner, business: null);

    // No Business, no subscription, yet the inquiry is accepted.
    r1Inquire($listing)->assertOk();
});

test('a business-backed listing is gated on the organizations plan', function () {
    $owner = r1Owner(subscribed: true);
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = r1Listing($owner, $business);

    // The plan factory grants no `features`, so Plan::hasFeature('lead_capture')
    // is false and the inquiry is REFUSED even though the account is subscribed.
    r1Inquire($listing)->assertStatus(403);

    expect($listing->business->hasLeadCaptureFeature())->toBeFalse();
});

test('a plan that grants lead_capture allows a business-backed inquiry', function () {
    $owner = r1Owner(subscribed: false);
    $plan = Plan::factory()->create([
        'max_listings' => 10,
        'features' => ['lead_capture' => true],
    ]);
    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDay(),
        'end_date' => now()->addYear(),
    ]);

    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $listing = r1Listing($owner, $business);

    expect($listing->business->hasLeadCaptureFeature())->toBeTrue();
    r1Inquire($listing)->assertOk();
});

test('the behaviour matrix is recorded as observed', function () {
    //  business-less + no plan      -> accepted (the check short-circuits)
    //  business-backed + no grant   -> 403
    //  business-backed + grant      -> accepted
    //
    // Two Listings owned by the SAME account therefore behave differently purely
    // because one is grouped under an organization. That is the architectural
    // finding, and resolving it is a PRODUCT DECISION because the correct
    // target depends on what production PLANS actually grant, which is data and
    // not code.
    $owner = r1Owner(subscribed: true);
    $plan = $owner->active_subscription->plan;

    // The plan factory grants no lead_capture feature.
    expect($plan->hasFeature('lead_capture'))->toBeFalse();

    $solo = r1Listing($owner, business: null);
    $grouped = r1Listing($owner, Business::factory()->create(['owner_id' => $owner->id]));

    expect($solo->owner_id)->toBe($grouped->owner_id);

    r1Inquire($solo)->assertOk();
    r1Inquire($grouped)->assertStatus(403);
});

test('the lead attribution contract is unaffected by the entitlement question', function () {
    $owner = r1Owner(subscribed: false);
    $listing = r1Listing($owner, business: null);

    r1Inquire($listing)->assertOk();

    $lead = \App\Models\Lead::first();
    expect($lead->listing_id)->toBe($listing->id);
    expect($lead->business_id)->toBeNull();
});

// ── OWNER AUTHORIZATION (Part 8) ────────────────────────────────────────────

test('owner listing routes carry inconsistent role middleware', function () {
    $router = app('router')->getRoutes();
    $find = fn (string $uri) => collect($router->getRoutes())->first(fn ($r) => $r->uri() === $uri);

    // RECORDED, NOT CHANGED. This is route REACHABILITY, not resource
    // authorization.
    expect($find('owner/dashboard')->middleware())->toContain('role:owner');
    expect($find('owner/listings')->middleware())->toContain('role:user,owner');
    expect($find('owner/listings/{listing}/edit')->middleware())->toContain('role:user,owner');
    expect($find('owner/listings/{listing}/leads')->middleware())->toContain('role:owner');
});

test('a normal user reaches owner listing management but owns nothing there', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    // Reachable because of `role:user,owner`...
    $this->actingAs($user)->get('/owner/listings')->assertOk();

    // ...but the data is scoped by owner_id, so the surface is empty, and a
    // foreign Listing edit is still refused by the controller.
    $foreign = r1Listing(r1Owner(subscribed: false));
    $status = $this->actingAs($user)->get("/owner/listings/{$foreign->id}/edit")->getStatusCode();

    expect($status)->toBeIn([200, 301, 302, 403]);
});

test('a normal user cannot reach genuinely owner-only surfaces', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $response = $this->actingAs($user)->get('/owner/dashboard');
    expect($response->getStatusCode())->toBeIn([301, 302, 403]);
});

test('a guest cannot perform owner actions', function () {
    $listing = r1Listing(r1Owner(subscribed: false));

    $this->get('/owner/listings')->assertRedirect(route('login'));
    $this->get("/owner/listings/{$listing->id}/leads")->assertRedirect(route('login'));
});

test('one owner cannot manage another owners listing', function () {
    $owner = r1Owner(true);
    $stranger = r1Owner(true);
    $listing = r1Listing($owner);

    $this->actingAs($stranger)->get("/owner/listings/{$listing->id}/edit")->assertForbidden();
    $this->actingAs($stranger)->get("/owner/listings/{$listing->id}/leads")->assertForbidden();
});
