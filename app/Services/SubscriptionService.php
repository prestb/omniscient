<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

/**
 * SubscriptionService
 *
 * Canonical home for subscription *domain logic*. Controllers should
 * coordinate HTTP concerns (validation, redirects) and delegate the
 * business rules here.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * EXTRACTED IN PHASE 1
 * ─────────────────────────────────────────────────────────────────────────
 * The Phase 0 audit found substantial subscription logic living inside
 * `Owner\SubscriptionController` (proration, credit carry-over, downgrade
 * detection, sibling cancellation) plus duplicated plan-comparison logic.
 * This service centralises the read/decision side so it can be tested in
 * isolation and reused by Fapshi payment callbacks.
 *
 * The methods here are intentionally SIDE-EFFECT-LIGHT: they compute and
 * describe. Persistence of a new subscription row still happens in the
 * controller (which owns the transaction boundary and payment hand-off),
 * but every *calculation* it performs comes from here.
 */
class SubscriptionService
{
    /**
     * The canonical way to retrieve an account's current subscription.
     *
     * Phase 1 decision: `$user->active_subscription` (the memoized
     * attribute on the User model) IS the canonical access pattern. This
     * method exists so there is a single documented entry point and so
     * future code never needs to choose between `active_subscription` and
     * `activeSubscription` by guesswork.
     *
     * NOTE: `$user->activeSubscription` (camelCase, the *relation*) and
     * `$user->active_subscription` (the *accessor*) both resolve to the
     * same data. The accessor is preferred because it includes the
     * grace-period status and memoizes per model instance (no N+1).
     */
    public function activeFor(User $user): ?Subscription
    {
        return $user->active_subscription;
    }

    /**
     * Determine whether moving from $oldPlan to $newPlan is a downgrade for
     * ENFORCEMENT purposes.
     *
     * A downgrade means: any enforced limit is strictly smaller on the new
     * plan. Unlimited limits (-1 / 999) are ignored on both sides — going
     * limited → unlimited is an upgrade; unlimited → limited is a downgrade.
     *
     * (Extracted verbatim from SubscriptionController::isPlanDowngrade so
     *  the rule lives in one place.)
     */
    public function isDowngrade(?Plan $oldPlan, ?Plan $newPlan): bool
    {
        if (!$oldPlan || !$newPlan) {
            return false;
        }

        foreach ($this->enforcedLimits() as $limit) {
            $old = (int) ($oldPlan->{$limit} ?? 0);
            $new = (int) ($newPlan->{$limit} ?? 0);

            $oldUnlimited = ($old === -1 || $old === 999);
            $newUnlimited = ($new === -1 || $new === 999);

            if ($oldUnlimited || $newUnlimited) {
                continue;
            }

            if ($new < $old) {
                return true;
            }
        }

        return false;
    }

    /**
     * Phase 3 — did this change LEAVE unlimited capacity?
     *
     * True iff the OLD plan was unlimited on any enforced axis and the NEW
     * plan is finite on that same axis. This is the corrected semantics for
     * "unlimited → limited": a genuine reduction in capacity that must arm
     * the protection machinery (grace window → reversible hiding), WITHOUT
     * deleting any data.
     *
     * It is deliberately a SEPARATE method from {@see isDowngrade()} so that:
     *   - the historic isDowngrade() contract and its tests are untouched, and
     *   - the controller can opt into the safer behaviour explicitly.
     */
    public function leavesUnlimited(?Plan $oldPlan, ?Plan $newPlan): bool
    {
        if (!$oldPlan || !$newPlan) {
            return false;
        }

        foreach ($this->enforcedLimits() as $limit) {
            $old = (int) ($oldPlan->{$limit} ?? 0);
            $new = (int) ($newPlan->{$limit} ?? 0);

            $oldUnlimited = ($old === -1 || $old === 999);
            $newUnlimited = ($new === -1 || $new === 999);

            if ($oldUnlimited && !$newUnlimited) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the proration / credit picture when an account with an active
     * subscription changes plan.
     *
     * Returns a pure value object describing what credit is available and
     * what is left over after paying for the new plan. No writes.
     *
     * @return array{
     *     available_credit: float,
     *     credit_applied: float,
     *     leftover_credit: float,
     *     amount_due: float
     * }
     */
    public function prorationForChange(
        Subscription $existing,
        Plan $newPlan,
        float $totalPrice,
        User $user
    ): array {
        // (1) Unused value of the current plan (time-based proration).
        $prorationCredit = $existing->calculateUpgradeCredit();

        // (2) Any banked credit already sitting on the current subscription.
        $bankedCredit = (float) ($existing->credit_balance ?? 0);

        // (3) Any banked credit on OTHER subscriptions for this user.
        $otherBankedCredit = (float) Subscription::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('id', '!=', $existing->id)
            ->sum('credit_balance');

        $availableCredit = $prorationCredit + $bankedCredit + $otherBankedCredit;

        $creditApplied = min($availableCredit, $totalPrice);
        $leftoverCredit = max(0, $availableCredit - $totalPrice);

        // Fapshi requires a positive integer; min 100 FCFA.
        $amountDue = max(100, (int) round($totalPrice - $creditApplied));

        return [
            'available_credit' => round($availableCredit, 2),
            'credit_applied' => round($creditApplied, 2),
            'leftover_credit' => round($leftoverCredit, 2),
            'amount_due' => (float) $amountDue,
        ];
    }

    /**
     * The grace window applied when a downgrade is detected.
     */
    public function downgradeGraceDate(): Carbon
    {
        return now()->addDays($this->downgradeGraceDays());
    }

    public function downgradeGraceDays(): int
    {
        // Reuse the subscription config so the value is not hard-coded.
        return (int) config('subscription.grace_period_days', 7);
    }

    /**
     * Whether the supplied account currently has grace active on its
     * active subscription (downgrade_grace_ends_at in the future).
     */
    public function graceActiveFor(?Subscription $subscription): bool
    {
        if (!$subscription || !$subscription->downgrade_grace_ends_at) {
            return false;
        }

        return Carbon::parse($subscription->downgrade_grace_ends_at)->isFuture();
    }

    /**
     * The enforced plan limits used for downgrade comparison. Centralised
     * here so adding a new limited resource is a single-line change.
     *
     * @return array<int, string>
     */
    protected function enforcedLimits(): array
    {
        return [
            'max_listings',
            'max_locations',
            'max_services',
            'max_images',
            'max_coupons',
        ];
    }
}
