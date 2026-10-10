<?php

namespace App\Observers;

use App\Models\Listing;
use App\Models\Subscription;
use App\Support\ListingSearchSync;

class SubscriptionObserver
{
    /**
     * Whenever a subscription becomes active, cancel every OTHER active
     * subscription for the same user. Prevents duplicates from any code path.
     */
    public function updated(Subscription $subscription): void
    {
        if (! $subscription->wasChanged('status')) {
            return;
        }

        // PHASE 23H — `Listing::toSearchableArray()` publishes
        // `has_active_subscription`, read from the OWNER's active subscription,
        // and `/search` filters on it. A subscription expiring, being cancelled
        // or entering grace therefore changes which Listings are discoverable.
        $this->syncOwnerListings($subscription);

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

    /**
     * PHASE 23H — a subscription appearing or disappearing changes the owner's
     * paid-visibility state just as a status change does.
     *
     * The bulk `update()` above deliberately does NOT sync the superseded
     * subscriptions' owners: superseding means the account still HAS an active
     * subscription, so `has_active_subscription` stays true and the published
     * document is unchanged.
     */
    public function created(Subscription $subscription): void
    {
        $this->syncOwnerListings($subscription);
    }

    public function deleted(Subscription $subscription): void
    {
        $this->syncOwnerListings($subscription);
    }

    public function restored(Subscription $subscription): void
    {
        $this->syncOwnerListings($subscription);
    }

    /**
     * `has_active_subscription` is ACCOUNT-scoped, so it is the owner's Listings
     * that must be rebuilt — there is no Business in this path.
     */
    private function syncOwnerListings(Subscription $subscription): void
    {
        if ($subscription->user_id === null) {
            return;
        }

        ListingSearchSync::forListingIds(
            Listing::where('owner_id', $subscription->user_id)->pluck('id')
        );
    }
}
