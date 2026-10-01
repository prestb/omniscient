<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\NotificationHelper;
use App\Services\SubscriptionService;

class SubscriptionController extends Controller
{
    /**
     * Phase 1: subscription domain logic (proration, credit carry-over,
     * downgrade detection, grace windows) is delegated to SubscriptionService.
     * The controller keeps only HTTP coordination + the payment hand-off.
     */
    public function __construct(
        private readonly SubscriptionService $subscriptions
    ) {
    }

    public function index()
    {
        $user = auth()->user();

        // PHASE 11 / WAVE 1D-5 — a Subscription is USER-owned. No representing
        // Business is selected for subscription pages; `hasBusiness` is a plain
        // existence check (an aggregate the UI legitimately wants), never a
        // representative pick.
        $hasBusiness = Business::where('owner_id', $user->id)->exists();

        $subscription = $user->active_subscription;

        if ($subscription) {
            $this->ensureSubscriptionData($subscription);
        }

        $pendingBusinesses = Business::where('owner_id', $user->id)
            ->whereIn('status', ['approved', 'draft', 'submitted'])
            ->get();

        $approvedCount = $pendingBusinesses->where('status', 'approved')->count();

        $subscriptionHistory = Subscription::where('user_id', $user->id)
            ->with(['plan', 'business'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // ✅ NEW — Total credit carried across all this user's subscriptions
        $totalCreditBalance = (float) Subscription::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->sum('credit_balance');

        return Inertia::render('Owner/Subscription/Index', [
            'business' => null,
            'subscription' => $subscription,
            'subscriptionHistory' => $subscriptionHistory,
            'hasBusiness' => $hasBusiness,
            'pendingBusinesses' => $pendingBusinesses,
            'approvedCount' => $approvedCount,
            'totalCreditBalance' => $totalCreditBalance, // ✅ NEW
        ]);
    }

    public function renew()
    {
        $user = auth()->user();

        $currentSubscription = $user->active_subscription;

        if ($currentSubscription) {
            $this->ensureSubscriptionData($currentSubscription);
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // ✅ Upgrade / renewal credit preview
        $upgradeCredit = null;
        if ($currentSubscription && $currentSubscription->isActive()) {
            $upgradeCredit = $currentSubscription->upgrade_credit_summary;
        }

        // ✅ NEW — Any credit already banked on OTHER subscriptions for this user
        //         (e.g. left over from a previous downgrade)
        $carriedCredit = (float) Subscription::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->sum('credit_balance');

        return Inertia::render('Owner/Subscription/Renew', [
            'business' => null,
            'currentSubscription' => $currentSubscription,
            'plans' => $plans,
            'hasSubscription' => $currentSubscription !== null,
            'upgradeCredit' => $upgradeCredit,
            'carriedCredit' => $carriedCredit, // ✅ NEW
        ]);
    }

    /**
     * Step 1 — the user picked a plan + duration.
     *
     * For upgrades we compute a proration credit now and pass the net amount
     * to Fapshi. If the credit exceeds the new plan price, the leftover is
     * stored as `credit_balance` on the new subscription row.
     */
    public function selectPlan(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'action_type' => 'required|in:new,renew,upgrade',
            'billing_type' => 'required|in:monthly,yearly',
            'duration' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();

        // PHASE 11 / WAVE 1D-5 — a Subscription is USER-owned, so NO Business is
        // required or invented here. This previously did
        // `Business::where('owner_id', $user->id)->first()` and, when none
        // existed, CREATED one named "<user>'s Business" purely to satisfy the
        // subscription write. Manufacturing an organization to satisfy
        // subscription ownership is the inverse of the account-scoped invariant
        // and is removed.

        $plan = Plan::find($validated['plan_id']);
        $billingType = $validated['billing_type'];
        $duration = $validated['duration'];
        $totalPrice = (float) $validated['total_price'];
        $actionType = $validated['action_type'];

        $durationMonths = $billingType === 'monthly' ? $duration : $duration * 12;
        $discountPercentage = $billingType === 'yearly' ? ($plan->yearly_discount_percentage ?? 15) : 0;

        $startDate = Carbon::now();
        $endDate = $billingType === 'monthly'
            ? $startDate->copy()->addMonths($duration)
            : $startDate->copy()->addYears($duration);

        $existingSubscription = $user->active_subscription;

        // ✅ Renewal: extend from current end_date if it's in the future
        if ($existingSubscription && $actionType === 'renew') {
            if ($existingSubscription->end_date && Carbon::parse($existingSubscription->end_date)->isFuture()) {
                $startDate = Carbon::parse($existingSubscription->end_date);
                $endDate = $billingType === 'monthly'
                    ? $startDate->copy()->addMonths($duration)
                    : $startDate->copy()->addYears($duration);
            }
        }

        // ============================================================
        // ✅ PRORATION — upgrade, downgrade, or renew while active
        //    Phase 1: computation delegated to SubscriptionService.
        // ============================================================
        $creditApplied = 0.0;
        $amountDue = $totalPrice;
        $upgradedFromId = null;
        $leftoverCredit = 0.0;

        $isActiveExisting = $existingSubscription && $existingSubscription->isActive();

        if (in_array($actionType, ['upgrade', 'renew']) && $isActiveExisting) {

            $proration = $this->subscriptions->prorationForChange(
                $existingSubscription,
                $plan,
                $totalPrice,
                $user
            );

            $creditApplied = $proration['credit_applied'];
            $leftoverCredit = $proration['leftover_credit'];
            $amountDue = $proration['amount_due'];

            // Only record the "upgraded from" link when the user was on an active sub
            $upgradedFromId = $existingSubscription->id;

            // ✅ Upgrades & downgrades reset the cycle from today
            if ($actionType === 'upgrade') {
                $startDate = Carbon::now();
                $endDate = $billingType === 'monthly'
                    ? $startDate->copy()->addMonths($duration)
                    : $startDate->copy()->addYears($duration);
            }
        }

        // ============================================================
        // Create or update the pending subscription row
        // ============================================================
        $subscription = Subscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'status' => Subscription::STATUS_PENDING,
            ],
            [
                // PHASE 11 / WAVE 1D-5 — `business_id` is OPTIONAL organization
                // context and is deliberately NOT written: this flow has no
                // explicit Business context, and inventing one is forbidden.
                'plan_id' => $plan->id,
                'start_date' => $actionType === 'new' ? null : $startDate,
                'end_date' => $actionType === 'new' ? null : $endDate,
                'duration_months' => $durationMonths,
                'total_price' => $totalPrice,
                'monthly_price' => $plan->price_monthly,
                'discount_percentage' => $discountPercentage,
                'credit_balance' => $leftoverCredit,
                'is_trial' => false,
                'failure_reason' => null,
                'upgraded_from_subscription_id' => $upgradedFromId,
                'action_type' => $actionType,
            ]
        );

        // ✅ Detect downgrade — fires whenever the user moves to a DIFFERENT plan.
        //    The frontend may send 'upgrade' | 'renew' | 'new' — we don't rely on it.
        //    `SubscriptionService::isDowngrade()` compares limits and returns true
        //    only if any enforced limit is strictly smaller on the new plan.
        if (
            $existingSubscription &&
            $existingSubscription->plan &&
            (int) $existingSubscription->plan_id !== (int) $plan->id
        ) {
            $isDowngrade = $this->subscriptions->isDowngrade($existingSubscription->plan, $plan);

            // Phase 3 — leaving unlimited capacity is ALSO treated as a
            // capacity reduction for PROTECTION purposes: it arms the same
            // grace window so the account enters a reversible over-quota
            // state instead of losing data. isDowngrade() itself is left
            // unchanged for backward compatibility.
            $leavesUnlimited = $this->subscriptions->leavesUnlimited($existingSubscription->plan, $plan);

            if ($isDowngrade || $leavesUnlimited) {
                $graceDate = $this->subscriptions->downgradeGraceDate()->toDateString();

                $saved = $subscription->update([
                    'downgrade_grace_ends_at' => $graceDate,
                ]);

                // Refresh to read the actual persisted value
                $subscription->refresh();

                \Log::info('Plan capacity reduction detected — grace period started', [
                    'reason' => $isDowngrade ? 'downgrade' : 'leaving_unlimited',
                    'old_plan' => $existingSubscription->plan->name,
                    'new_plan' => $plan->name,
                    'subscription_id' => $subscription->id,
                    'intended_grace_ends_at' => $graceDate,
                    'saved' => $saved,
                    'actual_grace_ends_at' => $subscription->downgrade_grace_ends_at?->format('Y-m-d'),
                    'user_id' => $user->id,
                ]);
            }
        }

        return redirect()->route('payment.fapshi.checkout', [
            'plan_id' => $plan->id,
            'duration' => $duration,
            'billing_type' => $billingType,
            'total_price' => $amountDue,
            'action_type' => $actionType,
            'subscription_id' => $subscription->id,
            'credit_applied' => $creditApplied,
        ]);
    }


    public function payment()
    {
        $planId = session('selected_plan_id');
        $billingPeriod = session('billing_period', 'yearly');
        $endDate = session('end_date');

        if (!$planId) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Please select a plan first.');
        }

        $plan = Plan::findOrFail($planId);

        $price = $billingPeriod === 'yearly'
            ? $plan->price_annual
            : $plan->price_monthly;

        // PHASE 11 / WAVE 1D-5 — a Subscription is USER-owned, so this page does
        // NOT require a Business. `selected_business_id` was previously read and
        // required here but is written NOWHERE in the repository, which made this
        // page unreachable; it has been removed. `business` is passed as optional
        // organization context and is null when none was explicitly chosen.
        return Inertia::render('Owner/Subscription/Payment', [
            'plan' => $plan,
            'business' => null,
            'billingPeriod' => $billingPeriod,
            'price' => $price,
            'endDate' => $endDate,
        ]);
    }

    public function processPayment(Request $request)
    {
        $user = Auth::user();

        $planId = session('selected_plan_id');
        $billingPeriod = session('billing_period', 'yearly');
        $endDate = session('end_date');

        if (!$planId) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Please select a plan first.');
        }

        $plan = Plan::findOrFail($planId);

        // PHASE 11 / WAVE 1D-5 — ONE ACTIVE SUBSCRIPTION PER USER.
        // A Subscription is USER-owned, so the guard is account-scoped. It is
        // NOT scoped to a Business: two Businesses must not permit two active
        // subscriptions for one account.
        $existingActive = $user->subscriptions()
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
                Subscription::STATUS_GRACE_PERIOD,
            ])
            ->exists();

        if ($existingActive) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'You already have an active subscription.');
        }

        // `user_id` is the authoritative owner. `business_id` is NOT written:
        // there is no explicit Business context in this flow (the session key
        // that once supplied one was never written anywhere), and inventing one
        // with businesses()->first() is exactly what this slice removes.
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
        ]);

        session()->forget(['selected_plan_id', 'billing_period', 'end_date']);

        try {
            $user->notify(new \App\Notifications\SubscriptionNotification(
                $subscription,
                'pending'
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send subscription notification: ' . $e->getMessage());
        }

        return redirect()->route('owner.subscription.index')
            ->with('success', 'Your subscription request has been submitted. Please wait for admin approval.');
    }

    public function status()
    {
        $user = Auth::user();

        // PHASE 11 / WAVE 1D-5 — account-scoped read. The authenticated User's
        // subscription state is derived from the User, never from an arbitrary
        // Business. A user with zero Businesses has a subscription state.
        $subscription = $user->subscriptions()
            ->with(['plan'])
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
                Subscription::STATUS_GRACE_PERIOD
            ])
            ->latest()
            ->first();

        return response()->json([
            'has_subscription' => $subscription !== null,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'status' => $subscription->status,
                'status_label' => $subscription->status_label,
                'status_badge' => $subscription->status_badge,
                'start_date' => $subscription->start_date?->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'days_remaining' => $subscription->days_remaining,
                'duration_months' => $subscription->duration_months,
                'total_price' => $subscription->total_price,
                'monthly_price' => $subscription->monthly_price,
                'discount_percentage' => $subscription->discount_percentage,
                'is_active' => $subscription->isActive(),
            ] : null,
        ]);
    }

    private function ensureSubscriptionData($subscription)
    {
        if (!$subscription) {
            return null;
        }

        $updated = false;

        if (!$subscription->duration_months && $subscription->start_date && $subscription->end_date) {
            $start = Carbon::parse($subscription->start_date);
            $end = Carbon::parse($subscription->end_date);
            $subscription->duration_months = $start->diffInMonths($end);
            $updated = true;
        }

        if (!$subscription->monthly_price && $subscription->plan) {
            $subscription->monthly_price = $subscription->plan->price_monthly ?? 0;
            $updated = true;
        }

        if (!$subscription->total_price && $subscription->duration_months && $subscription->monthly_price) {
            $basePrice = $subscription->monthly_price * $subscription->duration_months;
            $discount = $subscription->discount_percentage ?? 0;
            $subscription->total_price = $basePrice * (1 - ($discount / 100));
            $updated = true;
        }

        if (!$subscription->discount_percentage && $subscription->duration_months && $subscription->duration_months >= 12) {
            $subscription->discount_percentage = 15;
            $updated = true;
        }

        if ($subscription->duration_months && $subscription->duration_months >= 12 && $subscription->discount_percentage == 0) {
            $subscription->discount_percentage = 15;
            $updated = true;
        }

        if ($updated) {
            $subscription->save();
        }

        return $subscription;
    }

    private function calculateDaysRemaining($endDate)
    {
        if (!$endDate) {
            return null;
        }
        $now = now()->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        return $now->diffInDays($end, false);
    }

    // NOTE (Phase 1): `isPlanDowngrade()` was moved to SubscriptionService
    // so the enforcement rule lives in one place and is unit-testable.
}