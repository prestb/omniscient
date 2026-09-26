<?php

namespace App\Observers;

use App\Models\Subscription;

class SubscriptionObserver
{
    /**
     * Whenever a subscription becomes active, cancel every OTHER active
     * subscription for the same user. Prevents duplicates from any code path.
     */
    public function updated(Subscription $subscription): void
    {
        if (!$subscription->wasChanged('status')) {
            return;
        }

        if ($subscription->status !== Subscription::STATUS_ACTIVE) {
            return;
        }

        Subscription::where('user_id', $subscription->user_id)
            ->where('id', '!=', $subscription->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
                Subscription::STATUS_GRACE_PERIOD,
            ])
            ->update([
                'status'         => Subscription::STATUS_CANCELLED,
                'cancelled_at'   => now(),
                'failure_reason' => 'Superseded by subscription #' . $subscription->id . ' (observer)',
            ]);
    }
}