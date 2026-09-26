<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Grace Period Days
    |--------------------------------------------------------------------------
    |
    | The number of days after subscription expiration before the business
    | is suspended.
    |
    */
    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Expiring Soon Threshold
    |--------------------------------------------------------------------------
    |
    | The number of days before subscription expiration when the status
    | should change to "expiring_soon".
    |
    */
    'expiring_soon_threshold' => env('SUBSCRIPTION_EXPIRING_SOON_THRESHOLD', 30),

    /*
    |--------------------------------------------------------------------------
    | Check Frequency
    |--------------------------------------------------------------------------
    |
    | How often the subscription expiration check should run (in minutes).
    |
    */
    'check_frequency' => env('SUBSCRIPTION_CHECK_FREQUENCY', 60),
];