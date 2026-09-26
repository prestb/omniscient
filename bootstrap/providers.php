<?php

return [
    /*
     * Application Service Providers
     */
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RateLimitServiceProvider::class,
    /*
     * Package Service Providers
     */
    // Spatie\Permission\PermissionServiceProvider::class,
    // Add Scout Service Provider
    Laravel\Scout\ScoutServiceProvider::class,
];