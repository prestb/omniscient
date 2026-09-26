<?php

namespace App\Console\Commands;

use App\Mail\HighFailedJobsAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorFailedJobs extends Command
{
    protected $signature = 'monitor:failed-jobs
                            {--force : Bypass the cooldown for testing}';

    protected $description = 'Alert by email if too many queue jobs failed recently.';

    private const COOLDOWN_KEY = 'monitor.failed_jobs.cooldown';

    public function handle(): int
    {
        $threshold = (int) env('FAILED_JOBS_THRESHOLD', 10);
        $windowMinutes = (int) env('FAILED_JOBS_WINDOW_MINUTES', 15);
        $cooldownMinutes = (int) env('FAILED_JOBS_COOLDOWN_MINUTES', 30);
        $recipient = env('FAILED_JOBS_ALERT_EMAIL', config('mail.from.address'));

        if (!$recipient) {
            $this->error('No recipient configured. Set FAILED_JOBS_ALERT_EMAIL in .env');
            return self::FAILURE;
        }

        $since = now()->subMinutes($windowMinutes);

        // ✅ Count + fetch recent failures in one query
        $recent = DB::table('failed_jobs')
            ->where('failed_at', '>=', $since)
            ->orderByDesc('failed_at')
            ->limit(20)
            ->get();

        $count = $recent->count();

        $this->info(sprintf(
            'Failed jobs in last %d min: %d (threshold: %d)',
            $windowMinutes,
            $count,
            $threshold,
        ));

        if ($count < $threshold) {
            return self::SUCCESS;
        }

        // ✅ Cooldown — avoid email floods during a persistent outage
        if (!$this->option('force') && Cache::has(self::COOLDOWN_KEY)) {
            $this->warn('Within cooldown window — skipping alert.');
            return self::SUCCESS;
        }

        try {
            Mail::to($recipient)->send(new HighFailedJobsAlert(
                totalFailed: $count,
                recentJobs: $recent->map(fn($row) => [
                    'id' => $row->id,
                    'queue' => $row->queue,
                    'failed_at' => \Carbon\Carbon::parse($row->failed_at)->diffForHumans(),
                    'exception_summary' => $this->summarizeException($row->exception),
                ])->all(),
                windowMinutes: $windowMinutes,
                threshold: $threshold,
            ));

            Cache::put(self::COOLDOWN_KEY, now(), $cooldownMinutes * 60);

            $this->info(sprintf(
                'Alert sent to %s. Cooldown set for %d minutes.',
                $recipient,
                $cooldownMinutes,
            ));

            Log::warning('High failed jobs alert sent', [
                'count' => $count,
                'threshold' => $threshold,
                'window_minutes' => $windowMinutes,
                'recipient' => $recipient,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to send alert: ' . $e->getMessage());
            Log::error('Failed to send failed-jobs alert', [
                'error' => $e->getMessage(),
                'count' => $count,
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Reduce a huge stack trace to its first useful line.
     */
    private function summarizeException(?string $exception): string
    {
        if (!$exception) {
            return 'Unknown error';
        }

        // First line is typically "ExceptionClass: message"
        $firstLine = strtok($exception, "\n");
        return strlen($firstLine) > 180
            ? substr($firstLine, 0, 177) . '...'
            : $firstLine;
    }
}