<?php

namespace App\Helpers;

use App\Models\Business;
use App\Models\User;

class SubscriptionHelper
{
    /**
     * Get subscription status for a user
     */
    public static function getStatus(User $user)
    {
        $subscription = $user->activeSubscription;
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'no_subscription',
                'message' => 'No active subscription',
            ];
        }

        return [
            'has_subscription' => true,
            'plan' => $subscription->plan->name,
            'status' => $subscription->status,
            'status_label' => $subscription->status_label,
            'days_remaining' => $subscription->days_remaining,
            'end_date' => $subscription->end_date,
            'limits' => [
                'max_businesses' => $subscription->plan->max_businesses,
                'max_branches' => $subscription->plan->max_branches,
                'max_images' => $subscription->plan->max_images,
            ],
            'usage' => [
                'businesses' => Business::where('owner_id', $user->id)
                    ->whereNotIn('status', ['deleted', 'rejected'])
                    ->count(),
            ],
        ];
    }

    /**
     * Check if user has reached a specific limit
     */
    public static function hasReachedLimit(User $user, $type)
    {
        $subscription = $user->activeSubscription;
        if (!$subscription) return true;

        $limits = [
            'businesses' => $subscription->plan->max_businesses ?? 0,
            'branches' => $subscription->plan->max_branches ?? 0,
            'images' => $subscription->plan->max_images ?? 0,
        ];

        if ($limits[$type] === -1) return false;

        $counts = [
            'businesses' => Business::where('owner_id', $user->id)
                ->whereNotIn('status', ['deleted', 'rejected'])
                ->count(),
        ];

        return ($counts[$type] ?? 0) >= $limits[$type];
    }
}