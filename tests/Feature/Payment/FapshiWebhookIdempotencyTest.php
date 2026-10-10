<?php

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PHASE 24-R1 — FAPSHI WEBHOOK IDEMPOTENCY.
 *
 * Fapshi RETRIES webhooks and a retry is indistinguishable from a first
 * delivery. The defect these tests pin: `handleSuccessfulPayment()` RESETS
 * `start_date` and `end_date`, so a replayed SUCCESSFUL event silently EXTENDED
 * the subscription.
 *
 * The invariant is:
 *
 *     ONE SUCCESSFUL PAYMENT TRANSACTION -> ONE SUBSCRIPTION APPLICATION
 *
 * enforced by an atomic claim on `PaymentTransaction.confirmed_at` that only the
 * delivery flipping it from NULL can win. The polling path is guarded
 * separately (`status !== ACTIVE`), and the two are mutually idempotent because
 * EITHER of them sets `confirmed_at`.
 *
 * Helper names are prefixed `fw`; Pest shares one process across test files.
 */

const FW_SECRET = 'phase24r1-webhook-secret';

/**
 * A pending subscription plus a pending payment transaction for it.
 *
 * @return array{0: User, 1: Plan, 2: Subscription, 3: PaymentTransaction}
 */
function fwSetup(): array
{
    // The service reads the secret in its constructor, which the container runs
    // when the webhook route resolves it — so configuring it here is in time.
    config(['services.fapshi.webhook_secret' => FW_SECRET]);

    $owner = User::factory()->owner()->create(['email_verified_at' => now()]);

    $plan = Plan::factory()->create([
        'max_listings' => 5,
        'max_locations' => 3,
        'max_images' => 10,
        'features' => ['lead_capture' => true],
    ]);

    $subscription = Subscription::factory()->create([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_PENDING,
        'start_date' => null,
        'end_date' => null,
        'duration_months' => 12,
    ]);

    $transaction = PaymentTransaction::create([
        'user_id' => $owner->id,
        'subscription_id' => $subscription->id,
        'plan_id' => $plan->id,
        'transaction_id' => 'TX' . uniqid(),
        'amount' => 1000,
        'currency' => 'XAF',
        'status' => 'pending',
    ]);

    return [$owner, $plan, $subscription, $transaction];
}

/** Deliver a webhook exactly as Fapshi would. */
function fwDeliver(PaymentTransaction $transaction, string $status = 'SUCCESSFUL', ?string $secret = FW_SECRET)
{
    $headers = $secret === null ? [] : ['x-wh-secret' => $secret];

    return test()->withHeaders($headers)->postJson('/webhook/fapshi', [
        'transId' => $transaction->transaction_id,
        'status' => $status,
        'reason' => 'declined',
    ]);
}

// ═══ FIRST SUCCESS ═══════════════════════════════════════════════════════════

test('a successful webhook activates the subscription and confirms the transaction', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction)->assertOk();

    $subscription->refresh();
    $transaction->refresh();

    expect($subscription->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($subscription->start_date)->not->toBeNull()
        ->and($subscription->end_date)->not->toBeNull()
        ->and($transaction->confirmed_at)->not->toBeNull()
        ->and(strtolower((string) $transaction->status))->toBe('successful');
});

// ═══ THE REGRESSION: A DUPLICATE MUST NOT EXTEND ANYTHING ═══════════════════

test('a duplicate successful webhook does not change the subscription dates', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction)->assertOk();

    // Capture the applied state.
    $subscription->refresh();
    $transaction->refresh();

    $startAfterFirst = $subscription->start_date->toDateString();
    $endAfterFirst = $subscription->end_date->toDateString();
    $confirmedAfterFirst = $transaction->confirmed_at->toDateTimeString();
    $subscriptionsCount = Subscription::count();

    // Replay the SAME successful event. Fapshi retries.
    fwDeliver($transaction)->assertOk();

    $subscription->refresh();
    $transaction->refresh();

    expect($subscription->start_date->toDateString())->toBe($startAfterFirst)
        ->and($subscription->end_date->toDateString())->toBe($endAfterFirst)
        ->and($transaction->confirmed_at->toDateTimeString())->toBe($confirmedAfterFirst)
        ->and(Subscription::count())->toBe($subscriptionsCount);
});

test('many duplicates never extend the subscription', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction)->assertOk();

    $subscription->refresh();
    $endAfterFirst = $subscription->end_date->toDateString();

    // A retry storm.
    foreach (range(1, 4) as $ignored) {
        fwDeliver($transaction)->assertOk();
    }

    expect($subscription->fresh()->end_date->toDateString())->toBe($endAfterFirst);
});

// ═══ CROSS-PATH IDEMPOTENCY ═════════════════════════════════════════════════

test('a webhook arriving after polling already confirmed the transaction is idempotent', function () {
    [, , $subscription, $transaction] = fwSetup();

    // Simulate the POLLING path having already applied the payment: it sets
    // `confirmed_at` and activates the subscription.
    $subscription->update([
        'status' => Subscription::STATUS_ACTIVE,
        'start_date' => now()->subDays(3)->toDateString(),
        'end_date' => now()->addMonths(12)->toDateString(),
    ]);

    $transaction->update(['status' => 'successful', 'confirmed_at' => now()->subDays(3)]);

    $subscription->refresh();
    $startBefore = $subscription->start_date->toDateString();
    $endBefore = $subscription->end_date->toDateString();

    fwDeliver($transaction)->assertOk();

    // The late webhook must not re-apply and reset the dates.
    expect($subscription->fresh()->start_date->toDateString())->toBe($startBefore)
        ->and($subscription->fresh()->end_date->toDateString())->toBe($endBefore);
});

test('a webhook followed by another webhook keeps the first application', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction)->assertOk();

    $subscription->refresh();
    $applied = $subscription->end_date->toDateString();

    // Second delivery: the claim is already taken.
    fwDeliver($transaction)->assertOk();

    expect($subscription->fresh()->end_date->toDateString())->toBe($applied);
});

// ═══ FAILURE PATHS ══════════════════════════════════════════════════════════

test('a failed webhook does not claim the transaction, so a later success still applies', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction, 'FAILED')->assertOk();

    $transaction->refresh();
    $subscription->refresh();

    // The failure is recorded but must NOT consume the one-shot claim.
    expect($transaction->confirmed_at)->toBeNull()
        ->and($subscription->status)->toBe(Subscription::STATUS_PENDING);

    // The genuine success must still be applied exactly once.
    fwDeliver($transaction)->assertOk();

    expect($subscription->fresh()->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($transaction->fresh()->confirmed_at)->not->toBeNull();
});

test('an unknown transaction reference keeps its safe failure behaviour', function () {
    fwSetup();

    test()->withHeaders(['x-wh-secret' => FW_SECRET])
        ->postJson('/webhook/fapshi', ['transId' => 'DOES-NOT-EXIST', 'status' => 'SUCCESSFUL'])
        ->assertOk();

    expect(PaymentTransaction::where('transaction_id', 'DOES-NOT-EXIST')->exists())->toBeFalse();
});

test('an invalid webhook secret is still rejected', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction, 'SUCCESSFUL', 'wrong-secret')->assertStatus(403);

    // Nothing was applied.
    expect($subscription->fresh()->status)->toBe(Subscription::STATUS_PENDING)
        ->and($transaction->fresh()->confirmed_at)->toBeNull();
});

test('a webhook with no secret is rejected when one is configured', function () {
    [, , $subscription, $transaction] = fwSetup();

    fwDeliver($transaction, 'SUCCESSFUL', null)->assertStatus(403);

    expect($subscription->fresh()->status)->toBe(Subscription::STATUS_PENDING);
});
