<?php

use App\Models\Location;
use App\Models\LocationHourOverride;
use App\Models\Business;
use App\Models\LocationHour;

test('location is open when weekly hours cover now', function () {
    $business = Business::factory()->published()->create();
    $branch = Location::factory()->forBusiness($business)->primary()->create();

    LocationHour::factory()
        ->forDay(now()->dayOfWeek)
        ->openBetween('00:00', '23:59')
        ->create(['location_id' => $branch->id]);

    expect($branch->fresh()->is_open_now)->toBeTrue();
});

test('location is closed when today has a closed override', function () {
    $business = Business::factory()->published()->create();
    $branch = Location::factory()->forBusiness($business)->primary()->create();

    // Weekly hours say open all day
    LocationHour::factory()
        ->forDay(now()->dayOfWeek)
        ->openBetween('00:00', '23:59')
        ->create(['location_id' => $branch->id]);

    // But today has a closed override
    LocationHourOverride::factory()
        ->onDate(today())
        ->closed()
        ->create(['location_id' => $branch->id]);

    expect($branch->fresh()->is_open_now)->toBeFalse();
});

test('location uses special hours override when now is inside the window', function () {
    $business = Business::factory()->published()->create();
    $branch = Location::factory()->forBusiness($business)->primary()->create();

    $today = today();

    // Weekly hours say open 9-17
    LocationHour::factory()
        ->forDay($today->dayOfWeek)
        ->openBetween('09:00', '17:00')
        ->create(['location_id' => $branch->id]);

    // Override for THIS SPECIFIC DATE — open 10-14 only
    LocationHourOverride::factory()
        ->create([
            'location_id' => $branch->id,
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

test('location today_special_hours accessor returns the override when applicable', function () {
    $business = Business::factory()->published()->create();
    $branch = Location::factory()->forBusiness($business)->primary()->create();

    $today = today();

    LocationHourOverride::factory()->create([
        'location_id' => $branch->id,
        'date' => $today,
        'is_closed' => false,
        'opens_at' => '10:00',
        'closes_at' => '14:00',
    ]);

    
    $fresh = $branch->fresh();
    expect($fresh->today_special_hours)->not->toBeNull();
    // ...
});

test('location today_special_hours is null when override is closed', function () {
    $business = Business::factory()->published()->create();
    $branch = Location::factory()->forBusiness($business)->primary()->create();

    LocationHourOverride::factory()
        ->onDate(today())
        ->closed()
        ->create(['location_id' => $branch->id]);

    $fresh = $branch->fresh();
    expect($fresh->today_special_hours)->toBeNull()
        ->and($fresh->today_override)->not->toBeNull();
});