<?php

namespace Tests\Unit\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanEnforcementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — unlimited → limited downgrade semantics.
 *
 * isDowngrade() (Phase 1) is UNCHANGED. leavesUnlimited() is the new,
 * explicitly-named corrected semantics. Enforcement must never delete data.
 */
class DowngradeSemanticsTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService();
    }

    private function limits(int $businesses, int $others = 5): array
    {
        return [
            'max_listings' => $businesses,
            'max_locations' => $others,
            'max_services' => $others,
            'max_images' => $others,
            'max_coupons' => $others,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // leavesUnlimited()
    // ─────────────────────────────────────────────────────────────

    public function test_leaving_unlimited_is_detected(): void
    {
        $old = Plan::factory()->create($this->limits(-1, -1));
        $new = Plan::factory()->create($this->limits(3, 3));

        $this->assertTrue($this->service->leavesUnlimited($old, $new));
    }

    public function test_leaving_unlimited_uses_999_sentinel_too(): void
    {
        $old = Plan::factory()->create($this->limits(999, 999));
        $new = Plan::factory()->create($this->limits(3, 3));

        $this->assertTrue($this->service->leavesUnlimited($old, $new));
    }

    public function test_unlimited_to_unlimited_is_not_leaving_unlimited(): void
    {
        $old = Plan::factory()->create($this->limits(-1, -1));
        $new = Plan::factory()->create($this->limits(-1, -1));

        $this->assertFalse($this->service->leavesUnlimited($old, $new));
    }

    public function test_limited_to_lower_is_not_leaving_unlimited(): void
    {
        $old = Plan::factory()->create($this->limits(5));
        $new = Plan::factory()->create($this->limits(2));

        $this->assertFalse($this->service->leavesUnlimited($old, $new));
    }

    public function test_limited_to_unlimited_is_not_leaving_unlimited(): void
    {
        $old = Plan::factory()->create($this->limits(2));
        $new = Plan::factory()->create($this->limits(-1, -1));

        $this->assertFalse($this->service->leavesUnlimited($old, $new));
    }

    public function test_null_plans_are_never_leaving_unlimited(): void
    {
        $this->assertFalse($this->service->leavesUnlimited(null, Plan::factory()->create()));
        $this->assertFalse($this->service->leavesUnlimited(Plan::factory()->create(), null));
    }

    // ─────────────────────────────────────────────────────────────
    // isDowngrade() unchanged (regression)
    // ─────────────────────────────────────────────────────────────

    public function test_is_downgrade_still_ignores_the_unlimited_axis(): void
    {
        $old = Plan::factory()->create($this->limits(-1, -1));
        $new = Plan::factory()->create($this->limits(3, 3));

        // Preserved Phase 1 behaviour: isDowngrade() returns false here.
        $this->assertFalse($this->service->isDowngrade($old, $new));
    }

    // ─────────────────────────────────────────────────────────────
    // No data deletion on enforcement
    // ─────────────────────────────────────────────────────────────

    public function test_over_quota_enforcement_hides_rather_than_deletes(): void
    {
        $user = User::factory()->owner()->create();
        $plan = Plan::factory()->create($this->limits(1));

        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            // Grace already elapsed → enforcement hides.
            'downgrade_grace_ends_at' => now()->subDay(),
        ]);

        Business::factory()->count(3)->create(['owner_id' => $user->id]);

        $enforcer = app(PlanEnforcementService::class);
        $enforcer->enforce($sub);

        // Rows still exist — none deleted.
        $this->assertSame(3, Business::where('owner_id', $user->id)->count());

        // Exactly the surplus (2) are hidden; the oldest stays visible.
        $this->assertSame(2, Business::where('owner_id', $user->id)->whereNotNull('hidden_at')->count());
    }

    public function test_grace_active_prevents_hiding(): void
    {
        $user = User::factory()->owner()->create();
        $plan = Plan::factory()->create($this->limits(1));

        $sub = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'downgrade_grace_ends_at' => now()->addDays(7),
        ]);

        Business::factory()->count(3)->create(['owner_id' => $user->id]);

        $enforcer = app(PlanEnforcementService::class);
        $enforcer->enforce($sub);

        // Nothing hidden while grace is active; nothing deleted.
        $this->assertSame(3, Business::where('owner_id', $user->id)->count());
        $this->assertSame(0, Business::where('owner_id', $user->id)->whereNotNull('hidden_at')->count());
    }
}
