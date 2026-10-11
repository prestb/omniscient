<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WAVE A2 - BUSINESS ORGANIZATIONS DO NOT CONSUME THE LISTING QUOTA.
 *
 * `Admin\BusinessController::attemptAutoPublish()` (called from `approve()`) used to
 * gate publication on the account's LISTING allowance while counting BUSINESSES:
 *
 *     $maxBusinesses = $activeSubscription->plan->max_listings ?? 1;
 *     if ($isUnlimited || ($otherBusinesses + 1) <= $maxBusinesses) { ...publish... }
 *
 * That coupled organization approval to a quota that governs Listings only, so an
 * account could be refused publication of a second organization because it had a
 * one-Listing plan. The guards that are NOT the Listing quota - the organization must
 * have an owner, and that owner must hold an active subscription - are preserved and
 * pinned below.
 *
 * Helper names are prefixed `a2`; Pest shares one process across test files.
 */

function a2Admin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function a2SubscribedOwner(int $maxListings): User
{
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => Plan::factory()->create(['max_listings' => $maxListings])->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return $owner;
}

// ═══ 1 + 2. Approval is no longer limited by the Listing allowance ═══════════

test('an account at its listing quota can still have a Business organization published', function () {
    $owner = a2SubscribedOwner(1);

    // Saturate the organization slot the OLD rule counted: one already published org
    // means `($otherBusinesses + 1) <= 1` is false, so approval used to leave the
    // second organization unpublished.
    Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);

    $pending = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'submitted']);

    $this->actingAs(a2Admin())
        ->post("/admin/businesses/{$pending->id}/approve")
        ->assertRedirect();

    expect($pending->fresh()->status)->toBe(Business::STATUS_PUBLISHED);
});

test('multiple organizations can be published without consuming the listing quota', function () {
    $owner = a2SubscribedOwner(1);

    $statuses = [];
    foreach ([1, 2, 3] as $ignored) {
        $pending = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'submitted']);

        $this->actingAs(a2Admin())
            ->post("/admin/businesses/{$pending->id}/approve")
            ->assertRedirect();

        $statuses[] = $pending->fresh()->status;
    }

    expect($statuses)->toBe([
        Business::STATUS_PUBLISHED,
        Business::STATUS_PUBLISHED,
        Business::STATUS_PUBLISHED,
    ]);
});

// ═══ 3. Organizations are not Listings ══════════════════════════════════════

test('publishing organizations does not change the account listing count', function () {
    $owner = a2SubscribedOwner(2);
    $before = Listing::where('owner_id', $owner->id)->count();

    for ($i = 0; $i < 3; $i++) {
        $pending = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'submitted']);

        $this->actingAs(a2Admin())->post("/admin/businesses/{$pending->id}/approve")->assertRedirect();
    }

    expect($before)->toBe(0)
        ->and(Listing::where('owner_id', $owner->id)->count())->toBe(0)
        ->and(Business::where('owner_id', $owner->id)->count())->toBe(3);
});

// ═══ 4. The Listing quota itself is untouched ═══════════════════════════════

test('the listing quota still governs actual Listings', function () {
    $owner = a2SubscribedOwner(1);
    $owner = User::find($owner->id);

    // Below the limit.
    expect(\App\Helpers\SubscriptionHelper::hasReachedLimit($owner, 'listings'))->toBeFalse();

    Listing::factory()->published()->create(['owner_id' => $owner->id, 'business_id' => null]);

    // At the limit.
    expect(\App\Helpers\SubscriptionHelper::hasReachedLimit(User::find($owner->id), 'listings'))->toBeTrue();
});

test('the listing quota still blocks another Listing once reached', function () {
    $owner = a2SubscribedOwner(1);
    Listing::factory()->published()->create(['owner_id' => $owner->id, 'business_id' => null]);

    // HasPlanFeatures::canAdd() is the trait's own gate and must agree.
    expect(User::find($owner->id)->canAdd('listings'))->toBeFalse();
});

// ═══ 5 + 6. Preserved, unrelated guards ═════════════════════════════════════

test('an owner without an active subscription is still not auto-published', function () {
    // This guard is NOT the Listing quota: organization public reachability is
    // deliberately subscription-dependent (Business::isPubliclyReachable()).
    $owner = User::factory()->owner()->create();
    $pending = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'submitted']);

    $this->actingAs(a2Admin())
        ->post("/admin/businesses/{$pending->id}/approve")
        ->assertRedirect();

    expect($pending->fresh()->status)->not->toBe(Business::STATUS_PUBLISHED);
});

// NOTE: the `if (!$owner) return;` guard in attemptAutoPublish() is DEFENSIVE ONLY.
// `businesses.owner_id` is a NOT NULL foreign key
// (2026_08_31_000006_create_businesses_table.php:13), so an ownerless organization
// cannot exist and the guard is unreachable. It is deliberately NOT tested and
// deliberately NOT removed in this wave.
test('only an administrator may approve an organization', function () {
    $owner = a2SubscribedOwner(1);
    $pending = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'submitted']);

    // A plain owner must not gain administrative privilege from this change.
    $this->actingAs($owner)
        ->post("/admin/businesses/{$pending->id}/approve")
        ->assertRedirect(route('owner.dashboard'));

    expect($pending->fresh()->status)->not->toBe(Business::STATUS_PUBLISHED);
});
