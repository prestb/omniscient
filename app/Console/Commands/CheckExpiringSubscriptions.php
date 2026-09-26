<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Helpers\NotificationHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expiring';
    protected $description = 'Check and update expiring subscriptions';

    public function handle()
    {
        $this->info('Checking expiring subscriptions...');
        Log::info('Checking expiring subscriptions...');

        $count = 0;

        $subscriptions = Subscription::needsExpirationCheck()->get();

        foreach ($subscriptions as $subscription) {
            // Check if should be marked as expiring soon
            if ($subscription->shouldBeExpiringSoon() && $subscription->status === Subscription::STATUS_ACTIVE) {
                $subscription->markAsExpiringSoon();
                $count++;

                // ✅ Null-safe recipient lookup
                $recipient = $subscription->user ?? $subscription->business?->owner;

                if ($recipient) {
                    NotificationHelper::send(
                        $recipient,
                        'Subscription Expiring Soon ⚠️',
                        'Your subscription to "' . ($subscription->plan->name ?? 'your plan') . '" will expire on ' . $subscription->end_date->format('F j, Y') . '. Please renew to continue.',
                        route('owner.subscription.index'),
                        ['subscription_id' => $subscription->id],
                        'subscription_expiring'
                    );
                } else {
                    Log::warning("CheckExpiringSubscriptions: no recipient for subscription #{$subscription->id}");
                }

                $businessName = $subscription->business?->name ?? 'N/A';
                $this->line("Marked as expiring soon: {$businessName}");
                Log::info("Marked as expiring soon: Subscription {$subscription->id}");
            }

            // Check if expired and needs grace period
            if ($subscription->hasExpired() && in_array($subscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_EXPIRING_SOON])) {
                $gracePeriodDays = config('subscription.grace_period_days', 7);
                $subscription->expireWithGracePeriod($gracePeriodDays);
                $count++;

                // ✅ Null-safe recipient lookup
                $recipient = $subscription->user ?? $subscription->business?->owner;

                if ($recipient) {
                    NotificationHelper::send(
                        $recipient,
                        'Subscription Expired ⛔',
                        'Your subscription to "' . ($subscription->plan->name ?? 'your plan') . '" has expired. You are now in a ' . $gracePeriodDays . '-day grace period.',
                        route('owner.subscription.index'),
                        ['subscription_id' => $subscription->id],
                        'subscription_expired'
                    );
                } else {
                    Log::warning("CheckExpiringSubscriptions: no recipient for expired subscription #{$subscription->id}");
                }

                $businessName = $subscription->business?->name ?? 'N/A';
                $this->line("Moved to grace period: {$businessName}");
                Log::info("Moved to grace period: Subscription {$subscription->id}");
            }
        }

        $this->info("Processed {$count} subscriptions.");
        Log::info("Processed {$count} subscriptions.");

        return Command::SUCCESS;
    }
}