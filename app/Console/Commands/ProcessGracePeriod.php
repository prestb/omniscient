<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessGracePeriod extends Command
{
    protected $signature = 'subscriptions:process-grace-period';
    protected $description = 'Process subscriptions that have exceeded their grace period';

    public function handle()
    {
        $this->info('Processing grace period expirations...');
        Log::info('Processing grace period expirations...');

        $count = 0;

        // Get subscriptions where grace period has expired
        $subscriptions = Subscription::gracePeriodExpired()->get();

        foreach ($subscriptions as $subscription) {
            $subscription->suspendAfterGracePeriod();
            $count++;

            $businessName = $subscription->business?->name ?? 'N/A';
            $graceEnd = $subscription->grace_period_ends_at?->format('Y-m-d') ?? 'N/A';

            $this->line("Suspended: {$businessName} (grace period ended: {$graceEnd})");
            Log::info("Suspended: Subscription {$subscription->id} - {$businessName}");
        }

        $this->info("Suspended {$count} subscriptions.");
        Log::info("Suspended {$count} subscriptions.");

        return Command::SUCCESS;
    }
}