<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use App\Helpers\NotificationHelper;
use App\Models\User;
use App\Models\Subscription;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Subscription Expiration Checks
        $schedule->command('subscriptions:process-expired')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/subscriptions.log'));

        // Check for expiring subscriptions daily at 9 AM
        $schedule->call(function () {
            // ──────── 7 days remaining ────────
            $expiringUsers7Days = User::whereHas('subscriptions', function ($query) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->whereDate('end_date', '=', now()->addDays(7)->toDateString());
            })->get();

            foreach ($expiringUsers7Days as $user) {
                NotificationHelper::send(
                    $user,
                    'Subscription Expiring Soon ⚠️',
                    'Your subscription will expire in 7 days. Please renew to keep your business active.',
                    route('owner.subscription.index'),
                    ['days' => 7],
                    'subscription_expiring'
                );
            }

            // ──────── 3 days remaining ────────
            $expiringUsers3Days = User::whereHas('subscriptions', function ($query) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->whereDate('end_date', '=', now()->addDays(3)->toDateString());
            })->get();

            foreach ($expiringUsers3Days as $user) {
                NotificationHelper::send(
                    $user,
                    'Subscription Expiring Soon ⚠️',
                    'Your subscription will expire in 3 days. Please renew to keep your business active.',
                    route('owner.subscription.index'),
                    ['days' => 3],
                    'subscription_expiring'
                );
            }

            // ──────── Tomorrow ──────── (critical — force through preferences)
            $expiringUsersTomorrow = User::whereHas('subscriptions', function ($query) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->whereDate('end_date', '=', now()->addDay()->toDateString());
            })->get();

            foreach ($expiringUsersTomorrow as $user) {
                NotificationHelper::send(
                    $user,
                    'Subscription Expiring Tomorrow ⚠️',
                    'Your subscription expires tomorrow! Renew now to avoid interruption.',
                    route('owner.subscription.index'),
                    ['days' => 1],
                    'subscription_expiring',
                    true // force — last warning
                );
            }

            // ──────── Expired (auto-suspend businesses) ────────
            // NOTE: subscriptions() returns ALL subs (not just active),
            // so we can actually find expired ones.
            $expiredUsers = User::whereHas('subscriptions', function ($query) {
                $query->whereDate('end_date', '<', now()->toDateString())
                    ->whereIn('status', [
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_EXPIRING_SOON,
                        Subscription::STATUS_GRACE_PERIOD,
                    ]);
            })->get();

            foreach ($expiredUsers as $user) {
                // Force-send — critical
                NotificationHelper::send(
                    $user,
                    'Subscription Expired ⛔',
                    'Your subscription has expired. Please renew to reactivate your business.',
                    route('owner.subscription.index'),
                    [],
                    'subscription_expired',
                    true
                );

                // NOTE: Business suspension is handled by subscriptions:process-grace-period
                // after the grace period ends — do NOT suspend here.
            }

            Log::info('Subscription expiry notifications sent', [
                '7_days' => $expiringUsers7Days->count(),
                '3_days' => $expiringUsers3Days->count(),
                'tomorrow' => $expiringUsersTomorrow->count(),
                'expired' => $expiredUsers->count(),
            ]);
        })->dailyAt('09:00');

        // Process scheduled notifications every minute
        $schedule->call(function () {
            $controller = app(\App\Http\Controllers\Admin\NotificationController::class);

            $dueNotifications = \App\Models\ScheduledNotification::due()->get();

            foreach ($dueNotifications as $notification) {
                $controller->processScheduledNotification($notification);
            }
        })->everyMinute()->name('process-scheduled-notifications');

        // ✅ Enforce plan limits (hide excess resources after downgrade grace period)
        $schedule->command('enforce:plan-limits')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/plan-enforcement.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}