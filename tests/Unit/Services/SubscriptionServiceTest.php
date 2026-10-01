<?php

namespace Tests\Unit\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 — regression coverage for the extracted subscription domain logic.
 *
 * These tests pin the behaviour that previously lived inline in
 * Owner\SubscriptionController so the refactor is provably behaviour-preserving.
 */
class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService();
    }

    // ─────────────────────────────────────────────────────────────
    // Canonical subscription retrieval
    // ─────────────────────────────────────────────────────────────

    public function test_active_for_returns_canonical_active_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $this->createSubscriptionFor($user, $plan, ['status' => Subscription::STATUS_ACTIVE]);

        $this->assertNotNull($this->service->activeFor($user));
        $this->assertSame($plan->id, $this->service->activeFor($user)->plan_id);
    }

    public function test_active_for_returns_null_when_no_subscription(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->service->activeFor($user));
    }

    public function test_active_for_includes_grace_period_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $this->createSubscriptionFor($user, $plan, ['status' => Subscription::STATUS_GRACE_PERIOD]);

        $this->assertNotNull($this->service->activeFor($user));
    }

    // ─────────────────────────────────────────────────────────────
    // Downgrade detection
    // ─────────────────────────────────────────────────────────────

    public function test_lower_limits_is_a_downgrade(): void
    {
        $old = Plan::factory()->create($this->limits(businesses: 5));
        $new = Plan::factory()->create($this->limits(businesses: 1));

        $this->assertTrue($this->service->isDowngrade($old, $new));
    }

    public function test_higher_limits_is_not_a_downgrade(): void
    {
        // All enforced limits must be pinned — the factory randomizes the
        // others, and a single lower axis would (correctly) count as a downgrade.
        $old = Plan::factory()->create($this->limits(businesses: 1, others: 1));
        $new = Plan::factory()->create($this->limits(businesses: 5, others: 5));

        $this->assertFalse($this->service->isDowngrade($old, $new));
    }

    public function test_unlimited_target_is_not_a_downgrade(): void
    {
        $old = Plan::factory()->create($this->limits(businesses: 2, others: 2));
        $new = Plan::factory()->create($this->limits(businesses: -1, others: -1));

        $this->assertFalse($this->service->isDowngrade($old, $new));
    }

    public function test_leaving_unlimited_is_not_treated_as_a_downgrade(): void
    {
        // PRESERVED BEHAVIOUR (Phase 1): the original controller rule skips an
        // axis entirely when the OLD plan is unlimited:
        //     if ($oldUnlimited) continue;
        // So moving from an unlimited plan to a limited one is (by current
        // definition) NOT flagged as a downgrade. Extracting the logic did
        // not change this. Documented here so any future change is deliberate.
        // See Phase 1 report §5.D — flagged as a behaviour to revisit in Phase 2.
        $old = Plan::factory()->create($this->limits(businesses: -1, others: -1));
        $new = Plan::factory()->create($this->limits(businesses: 3, others: 3));

        $this->assertFalse($this->service->isDowngrade($old, $new));
    }

    /**
     * Build a deterministic set of enforced plan limits so downgrade tests
     * are not polluted by factory randomization on unrelated axes.
     */
    private function limits(int $businesses, ?int $others = null): array
    {
        $others ??= $businesses;

        return [
            'max_listings' => $businesses,
            'max_locations' => $others,
            'max_services' => $others,
            'max_images' => $others,
            'max_coupons' => $others,
        ];
    }

    public function test_null_plans_are_never_a_downgrade(): void
    {
        $this->assertFalse($this->service->isDowngrade(null, Plan::factory()->create()));
        $this->assertFalse($this->service->isDowngrade(Plan::factory()->create(), null));
    }

    // ─────────────────────────────────────────────────────────────
    // Proration
    // ─────────────────────────────────────────────────────────────

    public function test_proration_applies_full_credit_when_it_exceeds_price(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        // Active sub with lots of unused value + banked credit.
        $existing = $this->createSubscriptionFor($user, $plan, [
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(360),
            'total_price' => 100000,
            'credit_balance' => 50000,
        ]);

        $result = $this->service->prorationForChange($existing, $plan, 1000, $user);

        // Credit far exceeds the 1000 price → dues floor at the Fapshi minimum.
        $this->assertGreaterThanOrEqual(1000, $result['credit_applied']);
        $this->assertSame(100.0, $result['amount_due']);
        $this->assertGreaterThan(0, $result['leftover_credit']);
    }

    public function test_proration_returns_full_price_when_no_credit(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $existing = $this->createSubscriptionFor($user, $plan, [
            'status' => Subscription::STATUS_ACTIVE,
            // Expired window → no proration credit
            'start_date' => now()->subDays(400),
            'end_date' => now()->subDays(10),
            'total_price' => 100000,
            'credit_balance' => 0,
        ]);

        $result = $this->service->prorationForChange($existing, $plan, 5000, $user);

        $this->assertSame(0.0, $result['credit_applied']);
        $this->assertSame(5000.0, $result['amount_due']);
    }

    public function test_proration_never_returns_zero_amount_due(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $existing = $this->createSubscriptionFor($user, $plan, [
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'total_price' => 100000,
            'credit_balance' => 100000,
        ]);

        $result = $this->service->prorationForChange($existing, $plan, 100, $user);

        // Fapshi requires a positive integer >= 100.
        $this->assertGreaterThanOrEqual(100, $result['amount_due']);
    }

    // ─────────────────────────────────────────────────────────────
    // Grace window
    // ─────────────────────────────────────────────────────────────

    public function test_grace_active_when_downgrade_grace_in_future(): void
    {
        $user = User::factory()->create();
        $sub = $this->createSubscriptionFor($user, Plan::factory()->create(), [
            'downgrade_grace_ends_at' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertTrue($this->service->graceActiveFor($sub));
    }

    public function test_grace_inactive_when_window_passed(): void
    {
        $user = User::factory()->create();
        $sub = $this->createSubscriptionFor($user, Plan::factory()->create(), [
            'downgrade_grace_ends_at' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($this->service->graceActiveFor($sub));
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function createSubscriptionFor(User $user, Plan $plan, array $overrides = []): Subscription
    {
        return Subscription::factory()->create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(350),
        ], $overrides));
    }
}
