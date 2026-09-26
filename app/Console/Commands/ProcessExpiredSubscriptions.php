<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:process-expired';
    protected $description = 'Process all expired subscriptions (check, grace period, suspend)';

    public function handle()
    {
        $this->info('Processing expired subscriptions...');
        Log::info('Processing expired subscriptions...');

        // Step 1: Check expiring subscriptions
        $this->call('subscriptions:check-expiring');

        // Step 2: Process grace period expirations
        $this->call('subscriptions:process-grace-period');

        $this->info('Expired subscriptions processing completed.');
        Log::info('Expired subscriptions processing completed.');

        return Command::SUCCESS;
    }
}