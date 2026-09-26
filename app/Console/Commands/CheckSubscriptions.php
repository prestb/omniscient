<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSubscriptions extends Command
{
    protected $signature = 'subscriptions:check';
    protected $description = 'Check subscription statuses and update expired ones';

    public function handle()
    {
        $this->info('Checking subscription statuses...');

        // Get all active subscriptions
        $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->orWhere('status', Subscription::STATUS_EXPIRING_SOON)
            ->get();

        $updated = 0;

        foreach ($subscriptions as $subscription) {
            $daysRemaining = $subscription->days_remaining;
            
            if ($daysRemaining === null) continue;

            // Check if expired
            if ($daysRemaining <= 0) {
                $subscription->markAsExpired();
                $this->warn("Subscription #{$subscription->id} marked as expired");
                $updated++;
                continue;
            }

            // Check if expiring soon (within threshold)
            $threshold = config('subscriptions.expiring_soon_threshold', 30);
            if ($daysRemaining <= $threshold && $subscription->status !== Subscription::STATUS_EXPIRING_SOON) {
                $subscription->markAsExpiringSoon();
                $this->info("Subscription #{$subscription->id} marked as expiring soon ({$daysRemaining} days remaining)");
                $updated++;
            }
        }

        // Check grace period subscriptions
        $gracePeriodSubscriptions = Subscription::where('status', Subscription::STATUS_GRACE_PERIOD)
            ->whereNotNull('grace_period_ends_at')
            ->get();

        foreach ($gracePeriodSubscriptions as $subscription) {
            if (now()->greaterThan($subscription->grace_period_ends_at)) {
                $subscription->markAsExpired();
                $this->warn("Subscription #{$subscription->id} grace period expired");
                $updated++;
            }
        }

        $this->info("Completed. Updated {$updated} subscriptions.");
        Log::info("Subscription check completed. Updated {$updated} subscriptions.");

        return 0;
    }
}