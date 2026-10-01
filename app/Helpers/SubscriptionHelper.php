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
                'max_listings' => $subscription->plan->max_listings,
                'max_locations' => $subscription->plan->max_locations,
                'max_images' => $subscription->plan->max_images,
            ],
            'usage' => [
                'listings' => \App\Models\Listing::countFor($user),
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
            'listings' => $subscription->plan->max_listings ?? 0,
            'locations' => $subscription->plan->max_locations ?? 0,
            'images' => $subscription->plan->max_images ?? 0,
        ];

        if ($limits[$type] === -1) return false;

        $counts = [
            'listings' => \App\Models\Listing::countFor($user),
        ];

        return ($counts[$type] ?? 0) >= $limits[$type];
    }
}