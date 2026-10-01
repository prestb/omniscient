<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\ListingMigrationAnalyzer;
use App\Services\MigrationReadinessService;
use Illuminate\Console\Command;

/**
 * Phase 3/4 — READ-ONLY migration readiness report.
 *
 *   php artisan listing:migration-dry-run {business} [--readiness]
 *
 * Produces a human-readable version of ListingMigrationAnalyzer::analyze().
 * With --readiness it also prints the MigrationReadinessService verdict
 * (READY / NOT_READY + blocking reasons). It NEVER modifies the database.
 */
class ListingMigrationDryRun extends Command
{
    protected $signature = 'listing:migration-dry-run
                            {business : The ID of the business to analyse}
                            {--readiness : Also print the migration readiness verdict}';

    protected $description = 'Read-only: show what would move if a Business/Branches became independent Listings (no DB changes)';

    public function handle(
        ListingMigrationAnalyzer $analyzer,
        MigrationReadinessService $readiness
    ): int {
        $business = Business::withTrashed()->find($this->argument('business'));

        if (!$business) {
            $this->error("Business #{$this->argument('business')} not found.");
            return self::FAILURE;
        }

        $report = $analyzer->analyze($business);

        $this->info('READ-ONLY MIGRATION ANALYSIS — no data will be modified.');
        $this->newLine();

        $b = $report['business'];
        $this->line("Business: {$b['name']} (#{$b['id']})");
        $this->line("Owner:    User #{$b['owner']['id']} ({$b['owner']['name']})");
        $this->line("Type:     {$b['listing_type']}   Status: {$b['status']}");
        $this->newLine();

        $this->comment('Candidate Listings:');
        if (empty($report['candidates'])) {
            $this->line('  (none — business has no branches)');
        }
        foreach ($report['candidates'] as $c) {
            $loc = array_filter([$c['location']['city'], $c['location']['region'], $c['location']['address']]);
            $primary = $c['is_primary'] ? ' [primary]' : '';
            $this->line(sprintf(
                '  - %s   type=%s   location=%s%s',
                $c['name'],
                $c['type'],
                implode(', ', $loc) ?: 'n/a',
                $primary
            ));
        }
        $this->newLine();

        foreach ($report['classification'] as $bucket => $items) {
            $this->comment(strtoupper($bucket) . ':');
            foreach ($items as $item) {
                $count = array_key_exists('count', $item) ? " ({$item['count']})" : '';
                $this->line("  - {$item['entity']}{$count}: {$item['reason']}");
            }
            $this->newLine();
        }

        $this->comment('Requires product policy:');
        $this->line('  ' . implode(', ', $report['requires_policy']));
        $this->newLine();

        if ($this->option('readiness')) {
            $verdict = $readiness->report($business);

            $this->comment('Migration readiness:');
            $this->line("  Verdict: {$verdict['readiness']}");
            foreach ($verdict['tally'] as $class => $count) {
                $this->line("  {$class}: {$count}");
            }

            if (!empty($verdict['blocking_reasons'])) {
                $this->newLine();
                $this->comment('Blocking reasons:');
                foreach ($verdict['blocking_reasons'] as $reason) {
                    $this->line("  - {$reason}");
                }
            }
            $this->newLine();
        }

        $this->info('Done. No data was modified.');
        return self::SUCCESS;
    }
}
