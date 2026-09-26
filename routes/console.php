<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ScheduledNotification;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ============== SCHEDULER HEARTBEAT ==============
Schedule::call(function () {
    cache()->put('scheduler:last_run', now()->toIso8601String(), now()->addMinutes(10));
})->everyMinute()->name('scheduler-heartbeat');

// ============== BACKUPS ==============
// ✅ Database + user files backup, runs daily at 2:00 AM
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->name('backup-db')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:run --only-files')
    ->dailyAt('02:30')
    ->name('backup-files')
    ->withoutOverlapping()
    ->onOneServer();

// ✅ Retention — runs daily at 3:00 AM after backups complete
Schedule::command('backup:clean')
    ->dailyAt('03:00')
    ->name('backup-clean')
    ->withoutOverlapping()
    ->onOneServer();

// ✅ Health check — weekly, to catch silent failures
Schedule::command('backup:monitor')
    ->dailyAt('04:00')  // ✅ Daily so failures are caught within 24h
    ->name('backup-monitor')
    ->withoutOverlapping();

// ✅ Failed queue jobs monitor — alerts by email if threshold exceeded
Schedule::command('monitor:failed-jobs')
    ->everyFiveMinutes()
    ->name('monitor-failed-jobs')
    ->withoutOverlapping(); 


// ============== SUBSCRIPTION CHECKS ==============
Schedule::command('subscriptions:check')->daily();


// ============== SCHEDULED NOTIFICATIONS ==============
Schedule::call(function () {
    $due = ScheduledNotification::due()->get();

    \Log::info('Scheduler running', ['due_count' => $due->count()]);

    foreach ($due as $notification) {
        app(\App\Http\Controllers\Admin\NotificationController::class)
            ->processScheduledNotification($notification);
    }
})->everyMinute()->name('process-scheduled-notifications');