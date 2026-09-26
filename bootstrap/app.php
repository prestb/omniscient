<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use App\Http\Middleware\TrackBusinessView;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\CheckSubscriptionLimits;
use App\Http\Middleware\BlockImpersonatedAdmins;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Allow plain-text cookies written by JavaScript to pass through
        //    EncryptCookies unencrypted. Without this, Laravel's default
        //    cookie decryption silently drops them and the directory filter
        //    restore (server-side redirect) never fires.
        $middleware->encryptCookies(except: [
            'directory_filters_v1',
            'directory_view_mode_v1',
        ]);

        // Add CORS middleware globally
        $middleware->append(HandleCors::class);


        // ✅ Trust proxy headers (required behind CDN / reverse proxy).
        //    Hostinger's CDN + any standard proxy sets X-Forwarded-For,
        //    so `at: '*'` is the practical choice — no known IP ranges to
        //    pin for Hostinger's CDN. This also lets Laravel detect the
        //    original HTTPS protocol when the CDN terminates SSL.
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        );
    

        // Add Inertia middleware to web group
        $middleware->web(append: [
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            BlockImpersonatedAdmins::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'role' => CheckRole::class,
            'track.business' => TrackBusinessView::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'subscription.limits' => CheckSubscriptionLimits::class,
            'plan.feature' => \App\Http\Middleware\CheckPlanFeature::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimit::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerifiedNotice::class,
        ]);

        // Rate limiting middleware groups
        $middleware->appendToGroup('api', [
            Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
        ]);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            // For Inertia requests, let Laravel handle them normally
            // The custom error views in resources/views/errors will be used for non-Inertia requests
    
            // Optional: Return custom Inertia error pages
            if (in_array($response->getStatusCode(), [401, 403, 404, 419, 429, 500, 503]) && $request->header('X-Inertia')) {
                // Uncomment below if you want to use Inertia error pages instead of Blade
                // return \Inertia\Inertia::render('Error', ['status' => $response->getStatusCode()])
                //     ->toResponse($request)
                //     ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();