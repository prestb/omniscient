<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\ListingCategoryPivot;
use App\Models\ListingService;
use App\Models\Location;
use App\Models\Review;
use App\Observers\ListingCategoryPivotObserver;
use App\Observers\ListingServiceObserver;
use App\Observers\LocationObserver;
use App\Observers\ReviewObserver;
use App\Observers\SubscriptionObserver;
use App\Models\Subscription;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

        // PHASE 21A - ONE password policy, in EVERY environment.
        //
        // Previously configured ONLY when the environment was `testing`, so
        // in production Password::defaults() fell back to Laravel's min(8)
        // and password RESET / CHANGE accepted trivial passwords while
        // registration demanded symbols. That is the weaker policy guarding
        // the operation that can replace an account's credentials.
        Password::defaults(function () {
            $rule = Password::min(8)->mixedCase()->letters()->numbers()->symbols();

            // `uncompromised()` performs an external HTTP call to the Have I
            // Been Pwned range API. Skipped ONLY in tests, for determinism and
            // speed - never in production.
            return app()->environment('testing') ? $rule : $rule->uncompromised();
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        RateLimiter::for('api-upload', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        Review::observe(ReviewObserver::class);

        Subscription::observe(SubscriptionObserver::class);

        // PHASE 23H — LISTING CHILD → SEARCH SYNCHRONIZATION.
        //
        // `Listing::toSearchableArray()` publishes `category_ids`,
        // `categories_names` (pivot) and `services_names` (ListingService), but
        // Scout only observes a Listing's OWN events. Changing a child left the
        // Meilisearch document stale. These two observers are the single
        // authoritative path that rebuilds the document from the current child
        // state; nothing else re-indexes a Listing for a child change.
        //
        // The pivot is observed via `Listing::categories()->using(...)`, which
        // is what makes attach/detach/sync fire model events at all.
        ListingCategoryPivot::observe(ListingCategoryPivotObserver::class);

        ListingService::observe(ListingServiceObserver::class);

        // PHASE 23H — the remaining SEARCH-AFFECTING children.
        //
        // `toSearchableArray()` also publishes Location fields (city, region,
        // country, address, is_open_now), `has_active_subscription` from the
        // owner's Subscription, and `rating`/`reviews_count` from the Listing's
        // own approved Reviews. Review and Subscription already have observers;
        // Location did not.
        Location::observe(LocationObserver::class);

    }
}