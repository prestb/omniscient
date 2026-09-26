<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateSubscriptions extends Command
{
    protected $signature = 'subscriptions:cleanup-duplicates
                            {--dry-run : Show what would change without modifying the DB}
                            {--user= : Only process this user_id}';

    protected $description = 'Cancel all but the newest active subscription for each user';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyUser = $this->option('user');

        $this->info($dryRun ? '🔍 DRY RUN — no changes will be made.' : '🧹 Cleaning up duplicate active subscriptions...');

        // Find every user with more than one active subscription
        $query = Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->when($onlyUser, fn ($q) => $q->where('user_id', $onlyUser))
            ->select('user_id', DB::raw('COUNT(*) as active_count'))
            ->groupBy('user_id')
            ->having('active_count', '>', 1);

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicates found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->warn("Found {$duplicates->count()} user(s) with multiple active subscriptions.\n");

        $totalCancelled = 0;

        foreach ($duplicates as $row) {
            $userId = $row->user_id;

            // Get all active subs for this user, newest first
            $subs = Subscription::where('user_id', $userId)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->whereNull('deleted_at')
                ->orderByDesc('payment_confirmed_at')
                ->orderByDesc('id')
                ->get();

            $keep = $subs->first();
            $cancel = $subs->slice(1);

            $this->line("User #{$userId}:");
            $this->line("  ✅ KEEP   → sub #{$keep->id} (plan {$keep->plan_id}, activated {$keep->payment_confirmed_at})");

            foreach ($cancel as $sub) {
                $this->line("  ❌ CANCEL → sub #{$sub->id} (plan {$sub->plan_id}, activated {$sub->payment_confirmed_at})");

                if (!$dryRun) {
                    $sub->update([
                        'status'         => Subscription::STATUS_CANCELLED,
                        'cancelled_at'   => now(),
                        'failure_reason' => 'Superseded by subscription #' . $keep->id . ' (cleanup)',
                    ]);
                    $totalCancelled++;
                }
            }

            $this->line('');
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN COMPLETE. Run without --dry-run to apply.');
        } else {
            $this->info("✅ Done. Cancelled {$totalCancelled} stale subscription(s).");
        }

        return self::SUCCESS;
    }
}