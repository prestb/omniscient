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

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $business = Business::where('owner_id', $user->id)->first();

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
            'business' => $business,
            'subscription' => $subscription,
            'subscriptionHistory' => $subscriptionHistory,
            'hasBusiness' => $business !== null,
            'pendingBusinesses' => $pendingBusinesses,
            'approvedCount' => $approvedCount,
            'totalCreditBalance' => $totalCreditBalance, // ✅ NEW
        ]);
    }

    public function renew()
    {
        $user = auth()->user();
        $business = Business::where('owner_id', $user->id)->first();

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
            'business' => $business,
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

        $business = Business::where('owner_id', $user->id)->first();

        if (!$business) {
            $business = Business::create([
                'owner_id' => $user->id,
                'name' => $user->name . "'s Business",
                'slug' => \Illuminate\Support\Str::slug($user->name . '-business-' . $user->id),
                'status' => 'draft',
            ]);
        }

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
        // ============================================================
        $creditApplied = 0.0;
        $amountDue = $totalPrice;
        $upgradedFromId = null;
        $leftoverCredit = 0.0;

        $isActiveExisting = $existingSubscription && $existingSubscription->isActive();

        if (in_array($actionType, ['upgrade', 'renew']) && $isActiveExisting) {

            // (1) Unused value of current plan
            $prorationCredit = $existingSubscription->calculateUpgradeCredit();

            // (2) Any banked credit already sitting on the current sub
            $bankedCredit = (float) ($existingSubscription->credit_balance ?? 0);

            // (3) Any banked credit on OTHER subs for this user
            $otherBankedCredit = (float) Subscription::where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->where('id', '!=', $existingSubscription->id)
                ->sum('credit_balance');

            $availableCredit = $prorationCredit + $bankedCredit + $otherBankedCredit;

            $creditApplied = min($availableCredit, $totalPrice);
            $leftoverCredit = max(0, $availableCredit - $totalPrice);

            // Fapshi requires a positive integer; min 100 FCFA
            $amountDue = max(100, (int) round($totalPrice - $creditApplied));

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
                'business_id' => $business->id,
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
        //    `isPlanDowngrade` compares limits and returns true only if any
        //    enforced limit is strictly smaller on the new plan.
        if (
            $existingSubscription &&
            $existingSubscription->plan &&
            (int) $existingSubscription->plan_id !== (int) $plan->id
        ) {
            $isDowngrade = $this->isPlanDowngrade($existingSubscription->plan, $plan);

            if ($isDowngrade) {
                $graceDate = now()->addDays(7)->toDateString();

                $saved = $subscription->update([
                    'downgrade_grace_ends_at' => $graceDate,
                ]);

                // Refresh to read the actual persisted value
                $subscription->refresh();

                \Log::info('Downgrade detected — grace period started', [
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
        $businessId = session('selected_business_id');
        $billingPeriod = session('billing_period', 'yearly');
        $endDate = session('end_date');

        if (!$planId || !$businessId) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Please select a plan first.');
        }

        $plan = Plan::findOrFail($planId);
        $business = Business::findOrFail($businessId);

        $price = $billingPeriod === 'yearly'
            ? $plan->price_annual
            : $plan->price_monthly;

        return Inertia::render('Owner/Subscription/Payment', [
            'plan' => $plan,
            'business' => $business,
            'billingPeriod' => $billingPeriod,
            'price' => $price,
            'endDate' => $endDate,
        ]);
    }

    public function processPayment(Request $request)
    {
        $user = Auth::user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->back()->with('error', 'No business found.');
        }

        $planId = session('selected_plan_id');
        $billingPeriod = session('billing_period', 'yearly');
        $endDate = session('end_date');

        if (!$planId) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'Please select a plan first.');
        }

        $plan = Plan::findOrFail($planId);

        $existingActive = $business->subscriptions()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->exists();

        if ($existingActive) {
            return redirect()->route('owner.subscription.index')
                ->with('error', 'You already have an active subscription.');
        }

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_PENDING,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
        ]);

        session()->forget(['selected_plan_id', 'selected_business_id', 'billing_period', 'end_date']);

        try {
            $business->owner->notify(new \App\Notifications\SubscriptionNotification(
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
        $business = $user->businesses()->first();

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $subscription = $business->subscriptions()
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

    /**
     * Determine if moving from $oldPlan to $newPlan reduces any enforced limit.
     *
     * A "downgrade" for enforcement purposes means: any of the enforced limits
     * (businesses, branches, services, images, coupons) is strictly smaller
     * on the new plan compared to the old plan.
     *
     * Unlimited limits (-1 / 999) are ignored: going from limited → unlimited
     * is an upgrade, and going from unlimited → any number is a downgrade.
     */
    private function isPlanDowngrade($oldPlan, $newPlan): bool
    {
        if (!$oldPlan || !$newPlan) {
            return false;
        }

        $limits = ['max_businesses', 'max_branches', 'max_services', 'max_images', 'max_coupons'];

        foreach ($limits as $limit) {
            $old = (int) ($oldPlan->{$limit} ?? 0);
            $new = (int) ($newPlan->{$limit} ?? 0);

            $oldUnlimited = ($old === -1 || $old === 999);
            $newUnlimited = ($new === -1 || $new === 999);

            // Unlimited → anything (or) anything → unlimited = not a downgrade on this axis
            if ($oldUnlimited)
                continue;
            if ($newUnlimited)
                continue;

            if ($new < $old) {
                return true;
            }
        }

        return false;
    }

}