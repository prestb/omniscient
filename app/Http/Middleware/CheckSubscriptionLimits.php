<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckSubscriptionLimits
{
    public function handle(Request $request, Closure $next, $type = null)
    {
        $user = Auth::user();

        // Super admin bypass
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // ✅ Use the correct property: active_subscription (not activeSubscription)
        $subscription = $user->active_subscription;

        if (!$subscription) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'No active subscription',
                    'message' => 'You need an active subscription to perform this action.',
                ], 403);
            }

            return redirect()->route('owner.subscription.index')
                ->with('error', 'You need an active subscription to perform this action.');
        }

        // Check specific limits
        if ($type === 'create_business') {
            if (!$subscription->canCreateBusiness()) {
                $maxBusinesses = $subscription->plan->max_businesses ?? 0;
                $currentBusinesses = Business::where('owner_id', $user->id)
                    ->whereNotIn('status', ['deleted', 'rejected'])
                    ->count();

                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'Business limit reached',
                        'message' => "You have reached the maximum limit of {$maxBusinesses} businesses. Please upgrade your plan.",
                        'max_businesses' => $maxBusinesses,
                        'current_businesses' => $currentBusinesses,
                    ], 403);
                }

                return redirect()->back()->with('error', "You have reached the maximum limit of {$maxBusinesses} businesses. Please upgrade your plan.");
            }
        }

        if ($type === 'create_branch') {
            $businessId = $request->route('business') ?? $request->business_id;

            if ($businessId) {
                if (!$subscription->canCreateBranch($businessId)) {
                    $maxBranches = $subscription->plan->max_branches ?? 0;

                    if ($request->wantsJson()) {
                        return response()->json([
                            'error' => 'Branch limit reached',
                            'message' => "You have reached the maximum limit of {$maxBranches} branches. Please upgrade your plan.",
                        ], 403);
                    }

                    return redirect()->back()->with('error', "You have reached the maximum limit of {$maxBranches} branches. Please upgrade your plan.");
                }
            }
        }

        return $next($request);
    }
}