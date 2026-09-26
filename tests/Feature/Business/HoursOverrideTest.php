<?php

use App\Models\Branch;
use App\Models\BranchHourOverride;
use App\Models\Business;
use App\Models\BusinessHour;

test('branch is open when weekly hours cover now', function () {
    $business = Business::factory()->published()->create();
    $branch = Branch::factory()->forBusiness($business)->primary()->create();

    BusinessHour::factory()
        ->forDay(now()->dayOfWeek)
        ->openBetween('00:00', '23:59')
        ->create(['branch_id' => $branch->id]);

    expect($branch->fresh()->is_open_now)->toBeTrue();
});

test('branch is closed when today has a closed override', function () {
    $business = Business::factory()->published()->create();
    $branch = Branch::factory()->forBusiness($business)->primary()->create();

    // Weekly hours say open all day
    BusinessHour::factory()
        ->forDay(now()->dayOfWeek)
        ->openBetween('00:00', '23:59')
        ->create(['branch_id' => $branch->id]);

    // But today has a closed override
    BranchHourOverride::factory()
        ->onDate(today())
        ->closed()
        ->create(['branch_id' => $branch->id]);

    expect($branch->fresh()->is_open_now)->toBeFalse();
});

test('branch uses special hours override when now is inside the window', function () {
    $business = Business::factory()->published()->create();
    $branch = Branch::factory()->forBusiness($business)->primary()->create();

    $today = today();

    // Weekly hours say open 9-17
    BusinessHour::factory()
        ->forDay($today->dayOfWeek)
        ->openBetween('09:00', '17:00')
        ->create(['branch_id' => $branch->id]);

    // Override for THIS SPECIFIC DATE — open 10-14 only
    BranchHourOverride::factory()
        ->create([
            'branch_id' => $branch->id,
            'date' => $today,
            'is_closed' => false,
            'opens_at' => '10:00',
            'closes_at' => '14:00',
        ]);

    // At 11:00 → inside the override window
    \Carbon\Carbon::setTestNow($today->copy()->setTime(11, 0));
    expect($branch->fresh()->is_open_now)->toBeTrue();

    // At 15:00 → outside the override window (weekly says open)
    \Carbon\Carbon::setTestNow($today->copy()->setTime(15, 0));
    expect($branch->fresh()->is_open_now)->toBeFalse();

    \Carbon\Carbon::setTestNow(); // Reset
});

test('branch today_special_hours accessor returns the override when applicable', function () {
    $business = Business::factory()->published()->create();
    $branch = Branch::factory()->forBusiness($business)->primary()->create();

    $today = today();

    BranchHourOverride::factory()->create([
        'branch_id' => $branch->id,
        'date' => $today,
        'is_closed' => false,
        'opens_at' => '10:00',
        'closes_at' => '14:00',
    ]);

    
    $fresh = $branch->fresh();
    expect($fresh->today_special_hours)->not->toBeNull();
    // ...
});

test('branch today_special_hours is null when override is closed', function () {
    $business = Business::factory()->published()->create();
    $branch = Branch::factory()->forBusiness($business)->primary()->create();

    BranchHourOverride::factory()
        ->onDate(today())
        ->closed()
        ->create(['branch_id' => $branch->id]);

    $fresh = $branch->fresh();
    expect($fresh->today_special_hours)->toBeNull()
        ->and($fresh->today_override)->not->toBeNull();
});