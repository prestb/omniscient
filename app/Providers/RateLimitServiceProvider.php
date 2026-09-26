<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register the application's rate limiters.
     */
    public function boot(): void
    {
        // Auth: 6 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by($request->ip());
        });

        // Registration: 3 per hour per IP
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Password reset email request: 3 per hour per IP
        RateLimiter::for('password-email', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Password reset submit: 6 per hour per IP
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(6)->by($request->ip());
        });

        // Public contact form (site-wide): 3 per hour per IP
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        // Business lead submissions: 5 per hour per IP
        RateLimiter::for('lead', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Public review submissions: 3 per hour per IP and per user
        RateLimiter::for('review', function (Request $request) {
            return [
                Limit::perHour(3)->by($request->ip()),
                Limit::perHour(3)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        // Analytics tracking: 120 per minute per IP
        RateLimiter::for('analytics', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Search autocomplete: 60 per minute per IP
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Favorites toggle: 60 per hour per user
        RateLimiter::for('favorites', function (Request $request) {
            return Limit::perHour(60)->by($request->user()?->id ?: $request->ip());
        });

        // Coupon token generation: 10 per hour per user
        RateLimiter::for('coupon-token', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        // Redemption confirm: 20 per hour per user
        RateLimiter::for('redemption-confirm', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });

        // SuperAdmin destructive actions: 30 per hour per admin
        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perHour(30)->by($request->user()?->id ?: $request->ip());
        });

        // Impersonation: 20 per hour per admin
        RateLimiter::for('impersonate', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}