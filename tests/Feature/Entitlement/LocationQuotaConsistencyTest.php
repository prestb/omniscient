<?php

use App\Models\Business;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * WAVE B - LOCATION QUOTA CONSISTENCY.
 *
 * The owner dashboard counted Locations through a Business relationship:
 *
 *     Location::whereHas('business', fn ($q) => $q->where('owner_id', $user->id))->count()
 *
 * which EXCLUDED standalone (business_id = NULL) Locations from the reported
 * usage. Enforcement was already correct - Owner\LocationController calls
 * $user->canAdd('locations') on every creation path, and canAdd() resolves through
 * HasPlanFeatures::getLocationsCount() -> Location::where('owner_id', $this->id)
 * - so reporting is now aligned with enforcement rather than the reverse.
 *
 * Helper names are prefixed `lb`; Pest shares one process across test files.
 */

function lbOwner(int $maxLocations): User
{
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => Plan::factory()->create(['max_locations' => $maxLocations, 'max_listings' => 50])->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return User::find($owner->id);
}

function lbLocation(User $owner, array $attrs = []): Location
{
    return Location::factory()->create(array_merge(['owner_id' => $owner->id], $attrs));
}

// ═══ 1 + 2 + 3. The ACCOUNT-OWNED count ═════════════════════════════════════

test('a standalone Location is included in account Location usage', function () {
    $owner = lbOwner(5);
    lbLocation($owner, ['business_id' => null]);

    expect($owner->getCurrentUsage('locations'))->toBe(1);
});

test('a Business-associated Location is included exactly once', function () {
    $owner = lbOwner(5);
    $business = Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);

    lbLocation($owner, ['business_id' => $business->id]);

    expect($owner->getCurrentUsage('locations'))->toBe(1);
});

test('standalone and Business-associated Locations are counted together', function () {
    $owner = lbOwner(5);
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    lbLocation($owner, ['business_id' => $business->id]);
    lbLocation($owner, ['business_id' => null]);
    lbLocation($owner, ['business_id' => null]);

    expect($owner->getCurrentUsage('locations'))->toBe(3);
});

test('Locations owned by another account are excluded', function () {
    $owner = lbOwner(5);
    $stranger = User::factory()->owner()->create();

    lbLocation($owner, ['business_id' => null]);
    lbLocation($stranger, ['business_id' => null]);

    expect($owner->getCurrentUsage('locations'))->toBe(1);

    // Even when the OTHER account's Location is attached to THIS account's Business.
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    lbLocation($stranger, ['business_id' => $business->id]);

    expect(User::find($owner->id)->getCurrentUsage('locations'))->toBe(1);
});

// ═══ 5. Dashboard and usage reporting agree with enforcement ════════════════

test('the dashboard reports standalone Locations, not only Business ones', function () {
    // `/owner/dashboard` returns its main payload only when the account holds an
    // approved or published organization (DashboardController:21-30), so the
    // fixture satisfies that prerequisite: a published Business plus the active
    // subscription created by lbOwner().
    $owner = lbOwner(5);
    Business::factory()->create(['owner_id' => $owner->id, 'status' => 'published']);
    lbLocation($owner, ['business_id' => null]);

    $props = $this->actingAs($owner)->get('/owner/dashboard')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['hasBusiness'])->toBeTrue()
        ->and($props['usage']['locations']['current'])->toBe(1)
        ->and($props['usage']['locations']['limit'])->toBe(5);
});

test('the usage page reports the same account-owned Location count', function () {
    $owner = lbOwner(5);
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    lbLocation($owner, ['business_id' => $business->id]);
    lbLocation($owner, ['business_id' => null]);

    $props = $this->actingAs($owner)->get('/owner/usage')
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['usage']['locations']['current'])->toBe(2);
});

// ═══ 4 + 6 + 7. Enforcement against the same count ══════════════════════════

test('with max_locations = 1 the first Location is allowed and the second is refused', function () {
    $owner = lbOwner(1);

    expect($owner->canAdd('locations'))->toBeTrue();

    lbLocation($owner, ['business_id' => null]);

    expect(User::find($owner->id)->canAdd('locations'))->toBeFalse();
    expect(User::find($owner->id)->remaining('locations'))->toBe(0);
});

test('a standalone Location consumes the allowance, not only a Business one', function () {
    $owner = lbOwner(1);
    lbLocation($owner, ['business_id' => null]);

    // The old Business-shaped count reported 0 here, so the quota looked unused.
    expect(User::find($owner->id)->getCurrentUsage('locations'))->toBe(1)
        ->and(User::find($owner->id)->canAdd('locations'))->toBeFalse();
});

test('max_locations = 0 does not permit a new Location', function () {
    $owner = lbOwner(0);

    expect($owner->canAdd('locations'))->toBeFalse()
        ->and($owner->remaining('locations'))->toBe(0);
});

test('max_locations = -1 is unlimited', function () {
    $owner = lbOwner(-1);
    lbLocation($owner, ['business_id' => null]);

    expect(User::find($owner->id)->canAdd('locations'))->toBeTrue()
        ->and(User::find($owner->id)->remaining('locations'))->toBe(-1);
});

test('the legacy 999 alias is still unlimited for Locations', function () {
    $owner = lbOwner(999);
    lbLocation($owner, ['business_id' => null]);

    expect(User::find($owner->id)->canAdd('locations'))->toBeTrue();
});

test('an unconfigured Location limit does not grant unlimited creation', function () {
    // `plans.max_locations` is a NOT NULL column, so a null limit cannot be
    // persisted; the decision is therefore pinned at the layer that makes it.
    // Wave A1 policy: null is UNCONFIGURED and fails CLOSED, never open.
    $plan = new Plan(['max_locations' => null]);

    expect($plan->isUnlimited('locations'))->toBeFalse()
        ->and($plan->getLimit('locations'))->toBe(0)
        ->and(\App\Models\Plan::isUnlimitedLimit(null))->toBeFalse();
});
// ═══ 8. Cross-account protection ════════════════════════════════════════════

test('another account\'s Location cannot be viewed, updated or deleted', function () {
    // The authority is LocationPolicy, whose `owns()` is
    // `super_admin || location.owner_id === user.id`. Owner Location routes are
    // business-scoped, so the contract is asserted where it is defined rather
    // than against a guessed path.
    $owner = lbOwner(5);
    $stranger = User::factory()->owner()->create();
    $foreign = lbLocation($stranger, ['business_id' => null, 'name' => 'Not yours']);

    expect($owner->can('view', $foreign))->toBeFalse()
        ->and($owner->can('update', $foreign))->toBeFalse()
        ->and($owner->can('delete', $foreign))->toBeFalse()
        ->and($stranger->can('update', $foreign))->toBeTrue()
        ->and($foreign->fresh()->name)->toBe('Not yours');
});
// ═══ 9 + 10. Unchanged neighbouring policies ════════════════════════════════

test('the Listing quota is unchanged by Wave B', function () {
    $owner = lbOwner(5);
    $listing = Listing::factory()->published()->create(['owner_id' => $owner->id, 'business_id' => null]);
    $owner = User::find($owner->id);

    expect($owner->getCurrentUsage('listings'))->toBe(1)
        ->and($owner->canAdd('listings'))->toBeTrue();

    // Locations must not be counted as Listings.
    lbLocation($owner, ['business_id' => null]);
    lbLocation($owner, ['business_id' => null]);

    expect(User::find($owner->id)->getCurrentUsage('listings'))->toBe(1);
});

test('Business organizations remain independent of the Listing quota', function () {
    $owner = lbOwner(5);
    $owner->forceFill(['role' => 'admin'])->save();
    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

    $ownerUser = User::factory()->owner()->create();
    Subscription::factory()->create([
        'user_id' => $ownerUser->id,
        'plan_id' => Plan::factory()->create(['max_listings' => 1])->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    Business::factory()->create(['owner_id' => $ownerUser->id, 'status' => 'published']);
    $pending = Business::factory()->create(['owner_id' => $ownerUser->id, 'status' => 'submitted']);

    $this->actingAs($admin)->post("/admin/businesses/{$pending->id}/approve")->assertRedirect();

    expect($pending->fresh()->status)->toBe(Business::STATUS_PUBLISHED);
});
