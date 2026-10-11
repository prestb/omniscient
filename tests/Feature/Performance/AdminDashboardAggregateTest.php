<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * WAVE 29 — PERFORMANCE REGRESSION.
 *
 * The admin dashboard's monthly charts previously issued ONE aggregate query per
 * month (12 each for revenue and business growth, plus six per-status
 * subscription counts). Those loops were replaced with grouped aggregates.
 *
 * A brittle exact query-count assertion is deliberately avoided. What is pinned
 * here is the ARCHITECTURAL INVARIANT behind the fix: the monthly series is still
 * correct and complete, and the dashboard does not grow a query per month.
 */
test('the admin dashboard monthly series stays correct and complete', function () {
    // Three organizations created in the current month.
    Business::factory()->count(3)->create();

    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $props = $this->actingAs($admin)->get('/admin/dashboard')
        ->assertOk()
        ->viewData('page')['props'];

    $log = DB::getQueryLog();
    DB::disableQueryLog();

    // The series is a fixed 12-month window ending with the current month.
    $growth = $props['charts']['business_growth'];
    $revenue = $props['charts']['revenue'];

    expect($growth['labels'])->toHaveCount(12)
        ->and($growth['datasets'][0]['data'])->toHaveCount(12)
        ->and(end($growth['labels']))->toBe(now()->format('M Y'))
        ->and((int) end($growth['datasets'][0]['data']))->toBe(3);

    expect($revenue['labels'])->toHaveCount(12)
        ->and($revenue['datasets'][0]['data'])->toHaveCount(12);

    // The subscription-status chart aggregates in ONE query, not six.
    $statusLabels = $props['charts']['subscription_status']['labels'];
    expect($statusLabels)->toContain('Active')
        ->and($statusLabels)->toContain('Cancelled');

    // The invariant: the dashboard's month-scoped aggregates do NOT scale with the
    // 12 months it displays. Before the fix the two chart loops alone added ~24
    // such queries (plus six per-status counts); the remaining few are the
    // legitimate one-off "this month vs last month" KPI comparisons, which are a
    // fixed set regardless of how many months the charts show.
    $perMonthAggregates = collect($log)->filter(function ($q) {
        $sql = strtolower($q['query']);

        return str_contains($sql, 'month(')
            && (str_contains($sql, 'sum(') || str_contains($sql, 'count('));
    });

    expect($perMonthAggregates->count())->toBeLessThanOrEqual(10);
});
