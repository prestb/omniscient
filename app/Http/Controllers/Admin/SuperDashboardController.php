<?php
// app/Http/Controllers/Admin/SuperDashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\SentryService;

class SuperDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ============== KPIs ==============
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_businesses' => Business::count(),
            'published_businesses' => Business::where('status', 'published')->count(),

            // ✅ From payment_transactions table
            'total_revenue' => PaymentTransaction::where('status', 'successful')->sum('amount'),
            'this_month_revenue' => PaymentTransaction::where('status', 'successful')
                ->whereMonth('confirmed_at', now()->month)
                ->whereYear('confirmed_at', now()->year)
                ->sum('amount'),

            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'expiring_subscriptions' => Subscription::where('status', 'expiring_soon')->count(),
            'expired_subscriptions' => Subscription::whereIn('status', ['expired', 'grace_period'])->count(),
            'suspended_subscriptions' => Subscription::where('status', 'suspended')->count(),

            // ============== NEEDS ATTENTION ==============
            'pending_businesses' => Business::whereIn('status', ['submitted', 'pending'])->count(),
            'reviews_pending' => Review::where('status', Review::STATUS_PENDING)->count(),
            'failed_payments' => PaymentTransaction::where('status', 'failed')->count(),

            // ============== KPI BADGE METRICS ==============
            'businesses_this_month' => Business::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // User growth (MoM)
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $usersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $stats['user_growth'] = $usersLastMonth > 0
            ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : null;
        $stats['user_growth_delta'] = $usersThisMonth - $usersLastMonth;

        // Revenue growth (MoM)
        $lastMonthRevenue = PaymentTransaction::where('status', 'successful')
            ->whereMonth('confirmed_at', now()->subMonth()->month)
            ->whereYear('confirmed_at', now()->subMonth()->year)
            ->sum('amount');
        $stats['revenue_change'] = $lastMonthRevenue > 0
            ? round((($stats['this_month_revenue'] - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;
        $stats['revenue_change_delta'] = $stats['this_month_revenue'] - $lastMonthRevenue;

        // ============== PLATFORM HEALTH ==============
        $scheduler = $this->getSchedulerHealth();
        $diskBreakdown = $this->getDiskBreakdown();
        $load = $this->getLoadAverage();

        $platformHealth = [
            'queue_depth' => $this->safeCount('jobs'),
            'failed_jobs' => $this->safeCount('failed_jobs'),
            'disk_used_percent' => $this->getDiskUsedPercent(),
            'disk_used_gb' => $this->getDiskUsedGb(),
            'disk_total_gb' => $this->getDiskTotalGb(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),

            // ✅ Scheduler heartbeat
            'scheduler_last_run' => $scheduler['last_run'],
            'scheduler_status' => $scheduler['status'],
            'scheduler_seconds_ago' => $scheduler['seconds_ago'],

            // ✅ Disk breakdown
            'db_size_gb' => $diskBreakdown['db_size_gb'],
            'db_size_percent' => $diskBreakdown['db_size_percent'],
            'storage_size_gb' => $diskBreakdown['storage_size_gb'],
            'logs_size_gb' => $diskBreakdown['logs_size_gb'],

            // ✅ Load average (Linux/Mac only)
            'load_1m' => $load['load_1m'],
            'load_5m' => $load['load_5m'],
            'load_15m' => $load['load_15m'],
            'load_available' => $load['available'],
            'cpu_cores' => $load['cpu_cores'],
        ];

        // ============== CHART DATA ==============
        $revenueData = $this->getRevenueChartData();
        $businessGrowthData = $this->getBusinessGrowthChartData();
        $subscriptionStatusData = $this->getSubscriptionStatusData();

        // Recent Businesses
        $recentBusinesses = Business::with(['owner', 'primaryLocation'])
            ->latest()
            ->take(10)
            ->get();

        // Top Plans
        $topPlans = Plan::withCount([
            'subscriptions' => function ($query) {
                $query->where('status', 'active');
            }
        ])
            ->orderBy('subscriptions_count', 'desc')
            ->take(5)
            ->get();

        // Recent Transactions
        $recentPayments = PaymentTransaction::with(['user', 'plan', 'subscription'])
            ->where('status', 'successful')
            ->orderBy('confirmed_at', 'desc')
            ->take(10)
            ->get();

        // ============== MAINTENANCE MODE STATE ==============
        $maintenance = $this->getMaintenanceState();

        // ============== SENTRY ERROR HEALTH ==============
        $sentry = (new SentryService())->getErrorSummary();

        return Inertia::render('Admin/SuperDashboard', [
            'stats' => $stats,
            'platformHealth' => $platformHealth,
            'sentry' => $sentry,
            'charts' => [
                'revenue' => $revenueData,
                'business_growth' => $businessGrowthData,
                'subscription_status' => $subscriptionStatusData,
            ],
            'recentBusinesses' => $recentBusinesses,
            'topPlans' => $topPlans,
            'recentPayments' => $recentPayments,
            'maintenance' => $maintenance,
        ]);
    }


    // ============== MAINTENANCE MODE STATE ==============

    /**
     * Read current maintenance-mode state from storage/framework/down.
     * Returns: [active, since, by, allow_ips]
     */
    private function getMaintenanceState(): array
    {
        try {
            if (!app()->isDownForMaintenance()) {
                return [
                    'active' => false,
                    'since' => null,
                    'by' => null,
                    'allow_ips' => [],
                    'bypass_url' => null,
                ];
            }

            $downFile = storage_path('framework/down');

            if (!\Illuminate\Support\Facades\File::exists($downFile)) {
                return [
                    'active' => true,
                    'since' => null,
                    'by' => null,
                    'allow_ips' => [],
                ];
            }

            $payload = json_decode(\Illuminate\Support\Facades\File::get($downFile), true) ?: [];

            $since = isset($payload['time'])
                ? \Carbon\Carbon::createFromTimestamp($payload['time'])->toIso8601String()
                : null;

            $secret = $payload['secret'] ?? null;

            return [
                'active' => true,
                'since' => $since,
                'by' => $payload['by'] ?? null,
                'allow_ips' => $payload['allow'] ?? [],
                'bypass_url' => $secret ? url('/' . $secret) : null,
            ];
        } catch (\Throwable $e) {
            return [
                'active' => false,
                'since' => null,
                'by' => null,
                'allow_ips' => [],
                'bypass_url' => null,
            ];
        }
    }

    /**
     * Safe count — returns 0 if the table doesn't exist.
     * Prevents errors on fresh installs without queue tables.
     */
    private function safeCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getDiskUsedPercent(): float
    {
        try {
            $total = disk_total_space(base_path());
            $free = disk_free_space(base_path());
            if (!$total || $total <= 0)
                return 0;
            return round((($total - $free) / $total) * 100, 1);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getDiskUsedGb(): float
    {
        try {
            $total = disk_total_space(base_path());
            $free = disk_free_space(base_path());
            if (!$total)
                return 0;
            return round(($total - $free) / 1073741824, 1); // bytes → GB
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getDiskTotalGb(): float
    {
        try {
            $total = disk_total_space(base_path());
            if (!$total)
                return 0;
            return round($total / 1073741824, 1);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ============== SCHEDULER HEARTBEAT ==============

    /**
     * Reads the scheduler heartbeat from cache.
     *
     * The heartbeat is written by a `Schedule::call(...)` in routes/console.php
     * that runs every minute. If the scheduler process isn't running, the
     * heartbeat key will be missing or stale.
     */
    private function getSchedulerHealth(): array
    {
        try {
            $lastRun = cache()->get('scheduler:last_run');

            if (!$lastRun) {
                return [
                    'last_run' => null,
                    'status' => 'down',
                    'seconds_ago' => null,
                ];
            }

            $lastRunCarbon = \Carbon\Carbon::parse($lastRun);
            $secondsAgo = max(0, now()->diffInSeconds($lastRunCarbon, false) * -1);

            // Healthy: < 2 min since last tick
            // Stale: 2–5 min
            // Down: > 5 min
            if ($secondsAgo < 120) {
                $status = 'healthy';
            } elseif ($secondsAgo < 300) {
                $status = 'stale';
            } else {
                $status = 'down';
            }

            return [
                'last_run' => $lastRunCarbon->toIso8601String(),
                'status' => $status,
                'seconds_ago' => (int) $secondsAgo,
            ];
        } catch (\Throwable $e) {
            return [
                'last_run' => null,
                'status' => 'down',
                'seconds_ago' => null,
            ];
        }
    }

    // ============== DISK BREAKDOWN ==============

    /**
     * Compute sizes of the DB, storage/app, and storage/logs.
     * All wrapped in try/catch with safe fallbacks.
     */
    private function getDiskBreakdown(): array
    {
        $diskTotalGb = $this->getDiskTotalGb();

        $dbSizeGb = $this->getDbSizeGb();
        $storageSizeGb = $this->getDirSizeGb(storage_path('app'));
        $logsSizeGb = $this->getDirSizeGb(storage_path('logs'));

        $dbSizePercent = $diskTotalGb > 0
            ? round(($dbSizeGb / $diskTotalGb) * 100, 2)
            : 0;

        return [
            'db_size_gb' => $dbSizeGb,
            'db_size_percent' => $dbSizePercent,
            'storage_size_gb' => $storageSizeGb,
            'logs_size_gb' => $logsSizeGb,
        ];
    }

    /**
     * MySQL/MariaDB database size, queried from information_schema.
     * Returns 0.0 on failure or non-MySQL drivers.
     */
    private function getDbSizeGb(): float
    {
        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            if (!in_array($driver, ['mysql', 'mariadb'])) {
                return 0.0;
            }

            $dbName = config("database.connections.{$connection}.database");

            $result = DB::selectOne(
                'SELECT SUM(data_length + index_length) AS size_bytes
                 FROM information_schema.TABLES
                 WHERE table_schema = ?',
                [$dbName]
            );

            if (!$result || !$result->size_bytes) {
                return 0.0;
            }

            return round($result->size_bytes / 1073741824, 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Recursive directory size in GB. Skips symlinks to avoid double-count.
     */
    private function getDirSizeGb(string $path): float
    {
        try {
            if (!is_dir($path)) {
                return 0.0;
            }

            $bytes = 0;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && !$file->isLink()) {
                    $bytes += $file->getSize();
                }
            }

            return round($bytes / 1073741824, 2);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    // ============== LOAD AVERAGE ==============

    /**
     * Linux/Mac only. Returns nulls on Windows or if unavailable.
     * Also reports CPU core count so the UI can normalize.
     */
    private function getLoadAverage(): array
    {
        try {
            if (!function_exists('sys_getloadavg')) {
                return [
                    'load_1m' => null,
                    'load_5m' => null,
                    'load_15m' => null,
                    'available' => false,
                    'cpu_cores' => null,
                ];
            }

            $load = sys_getloadavg();

            if (!is_array($load) || count($load) < 3) {
                return [
                    'load_1m' => null,
                    'load_5m' => null,
                    'load_15m' => null,
                    'available' => false,
                    'cpu_cores' => null,
                ];
            }

            $cores = $this->getCpuCoreCount();

            return [
                'load_1m' => round($load[0], 2),
                'load_5m' => round($load[1], 2),
                'load_15m' => round($load[2], 2),
                'available' => true,
                'cpu_cores' => $cores,
            ];
        } catch (\Throwable $e) {
            return [
                'load_1m' => null,
                'load_5m' => null,
                'load_15m' => null,
                'available' => false,
                'cpu_cores' => null,
            ];
        }
    }

    /**
     * Best-effort CPU core detection on Linux.
     * Falls back to null (UI handles it).
     */
    private function getCpuCoreCount(): ?int
    {
        try {
            if (is_readable('/proc/cpuinfo')) {
                $count = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
                return $count > 0 ? $count : null;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return null;
    }

    private function getRevenueChartData()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $revenue = PaymentTransaction::where('status', 'successful')
                ->whereMonth('confirmed_at', $month->month)
                ->whereYear('confirmed_at', $month->year)
                ->sum('amount');

            $data[] = $revenue / 1000;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenue (XAF)',
                    'data' => $data,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function getBusinessGrowthChartData()
    {
        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $count = Business::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();

            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'New Businesses',
                    'data' => $data,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    private function getSubscriptionStatusData()
    {
        $statuses = [
            'Active' => Subscription::where('status', 'active')->count(),
            'Expiring Soon' => Subscription::where('status', 'expiring_soon')->count(),
            'Grace Period' => Subscription::where('status', 'grace_period')->count(),
            'Expired' => Subscription::where('status', 'expired')->count(),
            'Suspended' => Subscription::where('status', 'suspended')->count(),
            'Cancelled' => Subscription::where('status', 'cancelled')->count(),
        ];

        $colors = [
            '#22c55e',
            '#eab308',
            '#f97316',
            '#ef4444',
            '#8b5cf6',
            '#6b7280',
        ];

        return [
            'labels' => array_keys($statuses),
            'datasets' => [
                [
                    'data' => array_values($statuses),
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
        ];
    }
}