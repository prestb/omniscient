<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 11 — SUBSCRIPTION CARDINALITY HARDENING.
 *
 * Subscription is USER-owned. At most ONE subscription per User may be in an
 * active lifecycle state (active | expiring_soon | grace_period). The database
 * enforces this via a virtual generated column + unique index, so a concurrent
 * double-submit cannot produce two active subscriptions.
 */

function plan(): Plan
{
    return Plan::factory()->create(['max_listings' => 10]);
}

function sub(User $user, Plan $plan, string $status): Subscription
{
    return Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => $status,
    ]);
}

test('a user may hold many historical or inactive subscriptions', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    // Valid non-active lifecycle states from the schema enum:
    // pending, active, expiring_soon, expired, grace_period, suspended, cancelled
    sub($user, $plan, 'expired');
    sub($user, $plan, 'cancelled');
    sub($user, $plan, 'suspended');

    expect(Subscription::where('user_id', $user->id)->count())->toBe(3);
});

test('the database rejects a second active subscription for the same user', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    sub($user, $plan, Subscription::STATUS_ACTIVE);

    expect(fn() => sub($user, $plan, Subscription::STATUS_GRACE_PERIOD))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
});

test('every active lifecycle state occupies the same single slot', function (string $first, string $second) {
    $user = User::factory()->owner()->create();
    $plan = plan();

    sub($user, $plan, $first);

    expect(fn() => sub($user, $plan, $second))
        ->toThrow(UniqueConstraintViolationException::class);
})->with([
    ['active', 'active'],
    ['active', 'expiring_soon'],
    ['grace_period', 'active'],
    ['expiring_soon', 'grace_period'],
]);

test('a different user can hold an active subscription independently', function () {
    $plan = plan();
    $a = User::factory()->owner()->create();
    $b = User::factory()->owner()->create();

    sub($a, $plan, Subscription::STATUS_ACTIVE);
    sub($b, $plan, Subscription::STATUS_ACTIVE);

    expect(Subscription::where('status', Subscription::STATUS_ACTIVE)->count())->toBe(2);
});

test('moving the active subscription to an inactive state frees the slot', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    $first = sub($user, $plan, Subscription::STATUS_ACTIVE);
    $first->update(['status' => 'expired']);

    // The slot is free again.
    sub($user, $plan, Subscription::STATUS_ACTIVE);

    expect(Subscription::where('user_id', $user->id)->count())->toBe(2);
    expect(Subscription::where('user_id', $user->id)->where('status', Subscription::STATUS_ACTIVE)->count())->toBe(1);
});

test('an inactive subscription can become active when no other active exists', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    $old = sub($user, $plan, 'expired');
    $old->update(['status' => Subscription::STATUS_ACTIVE]);

    expect($old->fresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});

test('the generated columns resolve status correctly', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    $active = sub($user, $plan, Subscription::STATUS_ACTIVE);
    $expired = sub($user, $plan, 'expired');

    expect((int) $active->fresh()->active_user_id)->toBe($user->id);
    expect($active->fresh()->pending_user_id)->toBeNull();

    expect($expired->fresh()->active_user_id)->toBeNull();
    expect($expired->fresh()->pending_user_id)->toBeNull();
});

test('a user may hold only one pending subscription', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    sub($user, $plan, Subscription::STATUS_PENDING);

    expect(fn() => sub($user, $plan, Subscription::STATUS_PENDING))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('processPayment still works and writes the account owner', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    $this->actingAs($user)
        ->withSession([
            'selected_plan_id' => $plan->id,
            'billing_period' => 'yearly',
            'end_date' => now()->addYear()->toDateString(),
        ])
        ->post(route('owner.subscription.process-payment'))
        ->assertRedirect();

    $subscription = Subscription::firstOrFail();

    expect($subscription->user_id)->toBe($user->id);
    expect($subscription->status)->toBe(Subscription::STATUS_PENDING);
});

test('processPayment refuses a second request while one is pending', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    sub($user, $plan, Subscription::STATUS_PENDING);

    $this->actingAs($user)
        ->withSession([
            'selected_plan_id' => $plan->id,
            'billing_period' => 'yearly',
            'end_date' => now()->addYear()->toDateString(),
        ])
        ->post(route('owner.subscription.process-payment'))
        ->assertRedirect();

    // No duplicate and no 500 — the existing error flow handles it.
    expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
});

test('the account-scoped status endpoint remains correct', function () {
    $user = User::factory()->owner()->create();
    $plan = plan();

    sub($user, $plan, Subscription::STATUS_ACTIVE);

    $this->actingAs($user)->get(route('owner.subscription.status'))
        ->assertOk()
        ->assertJson(['has_subscription' => true]);

    $bare = User::factory()->owner()->create();
    sub($bare, $plan, Subscription::STATUS_ACTIVE);

    $this->actingAs($bare)->get(route('owner.subscription.status'))
        ->assertOk()
        ->assertJson(['has_subscription' => true]);
});
