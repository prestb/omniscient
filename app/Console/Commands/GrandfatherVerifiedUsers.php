<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrandfatherVerifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:grandfather-verified {--force : Run without confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time backfill: mark all currently-unverified users as verified (grandfather clause).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = User::whereNull('email_verified_at')->count();

        if ($count === 0) {
            $this->info('No users need to be verified. Nothing to do.');
            return self::SUCCESS;
        }

        $this->warn("About to mark {$count} user(s) as verified (email_verified_at = now()).");
        $this->line('This affects users in EVERY role: user, owner, admin, super_admin.');

        if (app()->environment('production') && !$this->option('force')) {
            $this->error('Refusing to run in production without --force.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('Proceed?', true)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $updated = User::whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->info("✅ Grandfathered {$updated} user(s) as verified.");

        return self::SUCCESS;
    }
}