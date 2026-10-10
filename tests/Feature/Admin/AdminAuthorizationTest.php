<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 27 — ADMIN AUTHORIZATION BOUNDARY AND DELETION SAFEGUARDS.
 *
 * Two confirmed defects are pinned here.
 *
 * 1. `CheckRole` short-circuited on `admin` BEFORE the declared-role check, so an
 *    ordinary admin also satisfied `role:super_admin`. The nested super-admin
 *    route group was therefore not protected, and `/admin/super-dashboard` was
 *    reachable by any admin.
 *
 * 2. `Admin\UserController::destroy` guarded on whether the user's BUSINESSES had
 *    an active subscription — the Business-scoped billing model PHASE 24-R1
 *    removed — so an account with an active subscription of its own but no
 *    Business could be deleted, taking its paid entitlement with it.
 *
 * Helper names are prefixed `adm`; Pest shares one process across test files.
 */

function admAdmin(): User
{
    return User::factory()->create(['role' => 'admin', 'status' => 'active']);
}

function admSuperAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
}

function admSubscribedOwner(): User
{
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => Plan::factory()->create(['max_listings' => 5])->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    return $owner;
}

// ═══ SUPER-ADMIN BOUNDARY ═══════════════════════════════════════════════════

test('an admin can access the admin dashboard', function () {
    $this->actingAs(admAdmin())->get('/admin/dashboard')->assertOk();
});

test('a super admin can access the admin dashboard', function () {
    $this->actingAs(admSuperAdmin())->get('/admin/dashboard')->assertOk();
});

test('a super admin can access the super admin dashboard', function () {
    $this->actingAs(admSuperAdmin())->get('/admin/super-dashboard')->assertOk();
});

test('an admin is FORBIDDEN from the super admin dashboard', function () {
    // The defect: `admin` short-circuited past `role:super_admin`.
    $this->actingAs(admAdmin())->get('/admin/super-dashboard')->assertForbidden();
});

test('an admin is forbidden from the super-admin-only actions', function () {
    $admin = admAdmin();

    // These already had in-controller guards; the middleware is what changed.
    $this->actingAs($admin)->post('/admin/actions/cache-clear')->assertForbidden();
    $this->actingAs($admin)->post('/admin/actions/maintenance')->assertForbidden();
});

test('an owner cannot reach either admin dashboard', function () {
    $owner = User::factory()->owner()->create();

    // The outer `role:admin,super_admin` group redirects before the nested group.
    $this->actingAs($owner)->get('/admin/dashboard')->assertRedirect(route('owner.dashboard'));
    $this->actingAs($owner)->get('/admin/super-dashboard')->assertRedirect(route('owner.dashboard'));
});

test('a regular user cannot reach either admin dashboard', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

    $this->actingAs($user)->get('/admin/dashboard')->assertRedirect(route('user.dashboard'));
    $this->actingAs($user)->get('/admin/super-dashboard')->assertRedirect(route('user.dashboard'));
});

test('a super admin still reaches the super-admin-only actions', function () {
    $this->actingAs(admSuperAdmin())->post('/admin/actions/cache-clear')->assertRedirect();
});

// ═══ PRESERVED CONTRACTS ════════════════════════════════════════════════════

test('an admin retains access to owner routes', function () {
    // Deliberately unchanged: `role:owner` never mentions super_admin, so the
    // admin short-circuit still applies.
    $this->actingAs(admAdmin())->get('/owner/dashboard')->assertOk();
});

test('a super admin retains access to owner routes', function () {
    $this->actingAs(admSuperAdmin())->get('/owner/dashboard')->assertOk();
});

// ═══ ACCOUNT SUBSCRIPTION DELETION GUARD ════════════════════════════════════

test('an account with an active subscription cannot be deleted, even with no businesses', function () {
    $admin = admAdmin();
    $owner = admSubscribedOwner();

    // The whole point: the removed Business-scoped guard could not see this.
    expect($owner->businesses()->count())->toBe(0);
    expect($owner->active_subscription)->not->toBeNull();

    $this->actingAs($admin)->delete("/admin/users/{$owner->id}")->assertRedirect();

    expect(User::find($owner->id))->not->toBeNull();
});

test('an account with no active subscription follows the existing deletion policy', function () {
    $admin = admAdmin();
    $plain = User::factory()->create(['role' => 'user', 'status' => 'active']);

    expect($plain->active_subscription)->toBeNull();

    $this->actingAs($admin)
        ->delete("/admin/users/{$plain->id}")
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($plain->id))->toBeNull();
});

test('a grace-period subscription still protects the account', function () {
    $admin = admAdmin();
    $owner = User::factory()->owner()->create();

    Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => Plan::factory()->create()->id,
        'status' => Subscription::STATUS_GRACE_PERIOD,
    ]);

    // `active_subscription` encodes the entitling statuses, not just `active`.
    expect($owner->active_subscription)->not->toBeNull();

    $this->actingAs($admin)->delete("/admin/users/{$owner->id}")->assertRedirect();

    expect(User::find($owner->id))->not->toBeNull();
});

test('the last super admin safeguard remains intact', function () {
    $admin = admAdmin();
    $superAdmin = admSuperAdmin();

    $this->actingAs($admin)->delete("/admin/users/{$superAdmin->id}")->assertRedirect();

    expect(User::find($superAdmin->id))->not->toBeNull();
});
