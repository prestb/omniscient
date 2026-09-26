<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\PlanEnforcementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforcePlanLimits extends Command
{
    protected $signature = 'enforce:plan-limits
                            {--dry-run : Show what would change without modifying the DB}
                            {--user= : Only process this user_id}';

    protected $description = 'Hide excess resources for downgraded subscriptions after grace period ends';

    public function handle(PlanEnforcementService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyUser = $this->option('user');

        $this->info($dryRun
            ? '🔍 DRY RUN — no DB changes will be made.'
            : '🔒 Enforcing plan limits...');

        // Process all subscriptions that are active OR expiring_soon
        // (a downgraded sub is still active — that's the point)
        $subscriptions = Subscription::query()
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_EXPIRING_SOON,
            ])
            ->when($onlyUser, fn ($q) => $q->where('user_id', $onlyUser))
            ->with(['plan', 'user'])
            ->get();

        $this->info("Processing {$subscriptions->count()} subscription(s)...");

        $totalHidden = 0;
        $totalRestored = 0;

        foreach ($subscriptions as $subscription) {
            if ($dryRun) {
                $this->line("  [DRY] Would process subscription #{$subscription->id} (user {$subscription->user_id}, plan {$subscription->plan_id})");
                continue;
            }

            try {
                $result = $service->enforce($subscription);

                if ($result['hidden'] > 0 || $result['restored'] > 0) {
                    $this->line(sprintf(
                        '  ✓ Sub #%d (user %d): hid %d, restored %d',
                        $subscription->id,
                        $subscription->user_id,
                        $result['hidden'],
                        $result['restored']
                    ));
                    Log::info("Plan enforcement applied", [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'hidden' => $result['hidden'],
                        'restored' => $result['restored'],
                        'breakdown' => $result['breakdown'],
                    ]);
                }

                $totalHidden += $result['hidden'];
                $totalRestored += $result['restored'];
            } catch (\Throwable $e) {
                $this->error("  ✗ Sub #{$subscription->id}: {$e->getMessage()}");
                Log::error("Plan enforcement failed", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if (!$dryRun) {
            $this->info("✅ Done. Hidden: {$totalHidden}, Restored: {$totalRestored}.");
            Log::info("Plan enforcement complete.", [
                'total_hidden' => $totalHidden,
                'total_restored' => $totalRestored,
            ]);
        } else {
            $this->info('🔍 DRY RUN complete. Run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}