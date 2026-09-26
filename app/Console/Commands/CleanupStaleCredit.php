<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupStaleCredit extends Command
{
    protected $signature = 'subscriptions:cleanup-credit
                            {--dry-run : Show what would change without modifying the DB}
                            {--user= : Only process this user_id}';

    protected $description = 'Zero out credit_balance on cancelled/expired subscriptions (already spent or transferred)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyUser = $this->option('user');

        $this->info($dryRun
            ? '🔍 DRY RUN — no changes will be made.'
            : '🧹 Zeroing credit_balance on stale subscriptions...');

        // Find all subscriptions that:
        // - are NOT active (cancelled, expired, failed, etc.)
        // - still hold a non-zero credit_balance
        $query = Subscription::query()
            ->whereNotIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
                Subscription::STATUS_GRACE_PERIOD,
            ])
            ->where('credit_balance', '>', 0)
            ->when($onlyUser, fn ($q) => $q->where('user_id', $onlyUser));

        $stale = $query->get();

        if ($stale->isEmpty()) {
            $this->info('✅ No stale credit found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->warn("Found {$stale->count()} subscription(s) holding stale credit:\n");

        $total = 0;

        foreach ($stale as $sub) {
            $this->line(sprintf(
                '  • Sub #%d (user %d, plan %d, status %s) → credit %s',
                $sub->id,
                $sub->user_id,
                $sub->plan_id,
                $sub->status,
                number_format($sub->credit_balance, 2)
            ));
            $total += (float) $sub->credit_balance;

            if (!$dryRun) {
                $sub->update(['credit_balance' => 0]);
            }
        }

        $this->line('');
        $this->info(sprintf(
            '%s Total stale credit: %s FCFA across %d subscription(s).',
            $dryRun ? '🔍 DRY RUN:' : '✅ Done.',
            number_format($total, 2),
            $stale->count()
        ));

        if ($dryRun) {
            $this->info('Run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}