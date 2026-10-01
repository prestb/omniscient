<?php

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * PHASE 11 / WAVE 1D-5 — SUBSCRIPTION OWNERSHIP CONVERGENCE.
 *
 *   Subscription
 *   ├── user_id      = authoritative account owner
 *   └── business_id  = OPTIONAL organization context
 *
 * ONE ACTIVE SUBSCRIPTION PER USER. A Business never arbitrates Subscription
 * identity, ownership, cardinality or callback identity.
 */

function subPlan(): Plan
{
    return Plan::factory()->create(['max_listings' => 10]);
}

function processPaymentAs(User $user, Plan $plan)
{
    return test()->actingAs($user)
        ->withSession([
            'selected_plan_id' => $plan->id,
            'billing_period' => 'yearly',
            'end_date' => now()->addYear()->toDateString(),
        ])
        ->post(route('owner.subscription.process-payment'));
}

// ── A. Account ownership ────────────────────────────────────────────────────
test('processPayment writes user_id as the account owner', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();

    processPaymentAs($user, $plan)->assertRedirect();

    $subscription = Subscription::firstOrFail();

    expect($subscription->user_id)->toBe($user->id);
    expect($subscription->status)->toBe(Subscription::STATUS_PENDING);
});

test('processPayment succeeds for a user with no business at all', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();

    expect($user->businesses()->count())->toBe(0);

    processPaymentAs($user, $plan)->assertRedirect();

    $subscription = Subscription::firstOrFail();

    // Zero-Business is NOT a prerequisite for subscription ownership.
    expect($subscription->user_id)->toBe($user->id);
    // No Business was invented.
    expect(Business::where('owner_id', $user->id)->count())->toBe(0);
    expect($subscription->business_id)->toBeNull();
});

// ── B. One active per User (not per Business) ───────────────────────────────
test('an existing active subscription blocks a second one regardless of business', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    processPaymentAs($user, $plan)->assertRedirect();

    // Still exactly one subscription — nothing new created.
    expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
});

test('two businesses owned by one user do not permit two active subscriptions', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();

    $a = Business::factory()->create(['owner_id' => $user->id]);
    $b = Business::factory()->create(['owner_id' => $user->id]);

    // An active subscription attached to Business A.
    Subscription::factory()->create([
        'user_id' => $user->id,
        'business_id' => $a->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    processPaymentAs($user, $plan)->assertRedirect();

    // Business B existing does NOT allow a second active subscription.
    expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
    expect(Subscription::where('status', Subscription::STATUS_ACTIVE)->count())->toBe(1);
});

test('an active subscription attached to a different business still blocks the account', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();
    $other = Business::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'business_id' => $other->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_GRACE_PERIOD,
    ]);

    processPaymentAs($user, $plan)->assertRedirect();

    expect(Subscription::where('user_id', $user->id)->count())->toBe(1);
});

// ── C. status() is account-scoped ───────────────────────────────────────────
test('the status endpoint reads the account subscription with no business', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();
    $business = Business::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->create([
        'user_id' => $user->id,
        'business_id' => $business->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)->get(route('owner.subscription.status'))
        ->assertOk()
        ->assertJson(['has_subscription' => true]);

    // A user with NO business still gets an answer rather than a 404.
    $bare = User::factory()->owner()->create();
    Subscription::factory()->create([
        'user_id' => $bare->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    $this->actingAs($bare)->get(route('owner.subscription.status'))
        ->assertOk()
        ->assertJson(['has_subscription' => true]);
});

// ── D. selected_business_id is dead ─────────────────────────────────────────
test('the unwired selected_business_id mechanism is gone', function () {
    $code = File::get(app_path('Http/Controllers/Owner/SubscriptionController.php'));

    // Only a comment records its removal; no executable read or forget remains.
    $executable = collect(preg_split('/\R/', $code))
        ->reject(function (string $line) {
            $t = ltrim($line);
            return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
        })
        ->implode("\n");

    expect(str_contains($executable, 'selected_business_id'))->toBeFalse();
});

// ── E/F. Fapshi callback identity ───────────────────────────────────────────
test('no subscription path resolves a business by representative selection', function () {
    $strip = function (string $path): string {
        return collect(preg_split('/\R/', File::get($path)))
            ->reject(function (string $line) {
                $t = ltrim($line);
                return str_starts_with($t, '*') || str_starts_with($t, '//') || str_starts_with($t, '/*');
            })
            ->implode("\n");
    };

    foreach ([
        app_path('Http/Controllers/Owner/SubscriptionController.php'),
        app_path('Http/Controllers/Payment/FapshiController.php'),
    ] as $path) {
        $code = $strip($path);

        foreach (['businesses()->first', 'primaryBusiness', 'primaryListing'] as $forbidden) {
            expect(str_contains($code, $forbidden))
                ->toBeFalse(basename($path) . " must not contain executable: {$forbidden}");
        }
    }

    // The Fapshi fallback is account-scoped.
    $fapshi = $strip(app_path('Http/Controllers/Payment/FapshiController.php'));
    expect(str_contains($fapshi, 'Subscription::where(\'user_id\''))->toBeTrue();

    // No Business is manufactured anywhere in the subscription flow.
    expect(str_contains($strip(app_path('Http/Controllers/Owner/SubscriptionController.php')), 'Business::create'))
        ->toBeFalse();
});

// ── G. Account-scoped canonical relationships intact ────────────────────────
test('the account-level subscription relationships remain the canonical ones', function () {
    $user = User::factory()->owner()->create();
    $plan = subPlan();

    Subscription::factory()->create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);

    expect($user->subscriptions()->count())->toBe(1);
    expect($user->active_subscription)->not->toBeNull();
    expect($user->active_subscription->user_id)->toBe($user->id);
});
