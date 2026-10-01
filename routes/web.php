<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// Serve uploaded files through Laravel.
// Hostinger's LiteSpeed refuses to serve anything under /storage/ directly,
// so we intercept the request here and stream the file ourselves.
Route::get('/storage/{path}', function (string $path) {
    $disk = Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('storage.serve');

// ============== CONTROLLERS ==============
// Public
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\DirectoryController;
use App\Http\Controllers\Public\SearchController;
use App\Http\Controllers\Public\PricingController;
use App\Http\Controllers\Public\ContactController as PublicContactController;
use App\Http\Controllers\Public\ReviewController as PublicReviewController;
use App\Http\Controllers\Public\CouponController as PublicCouponController;
use App\Http\Controllers\Public\ListingLeadController;
use App\Http\Controllers\Public\FavoriteController;

// Auth
use App\Http\Controllers\Auth\InvitationController as AuthInvitationController;
use App\Http\Controllers\ProfileController;

// User
use App\Http\Controllers\User\DashboardController as UserDashboardController;

// Owner
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Public\ListingController as PublicListingController;
use App\Http\Controllers\Owner\ListingController as OwnerListingController;
use App\Http\Controllers\Owner\BusinessController as OwnerBusinessController;
use App\Http\Controllers\Owner\LocationController as OwnerLocationController;
use App\Http\Controllers\Owner\ServiceController;
use App\Http\Controllers\Owner\ContactController;
use App\Http\Controllers\Owner\ImageController;
use App\Http\Controllers\Owner\ListingImageController;
use App\Http\Controllers\Owner\HourController;
use App\Http\Controllers\Owner\SubscriptionController as OwnerSubscriptionController;
use App\Http\Controllers\Owner\AnalyticsController;
use App\Http\Controllers\Owner\NotificationController;
use App\Http\Controllers\Owner\ReviewController as OwnerReviewController;
use App\Http\Controllers\Owner\CouponController;
use App\Http\Controllers\Owner\LeadController as OwnerLeadController;
use App\Http\Controllers\Owner\ListingLeadController as OwnerListingLeadController;

// Admin
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SuperDashboardController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ReviewExportController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OwnerController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\RevenueController;

// Other
use App\Http\Controllers\Payment\FapshiController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\TestRatingController;

// Middleware
use App\Http\Middleware\TrackBusinessView;



// =============================================================
// ============== PUBLIC ROUTES =================================
// =============================================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');
Route::get('/business/{slug}', [DirectoryController::class, 'show'])
    ->name('business.show')
    ->middleware('track.business');

// PHASE 11 / WAVE 1D-2 — the canonical public Listing page.
// A Listing has its OWN slug and its OWN identity; it is not addressed
// through a Business.
Route::get('/listing/{slug}', [PublicListingController::class, 'show'])->name('listing.show');
Route::get('/categories', [DirectoryController::class, 'categories'])->name('categories');
Route::get('/locations', [DirectoryController::class, 'locations'])->name('locations');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])
    ->middleware('throttle:search')
    ->name('search.autocomplete');

// ✅ Location-aware category chips (IP-based, cached)
Route::get('/api/location-chips', [\App\Http\Controllers\Public\LocationChipsController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('location-chips');

// ✅ Explore feed — discovery page with carousels
Route::get('/explore', [\App\Http\Controllers\Public\ExploreController::class, 'index'])
    ->name('explore');

Route::get('/api/explore/row', [\App\Http\Controllers\Public\ExploreRowController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('explore.row');

// Public coupons
Route::get('/api/businesses/{business}/coupons', [PublicCouponController::class, 'forBusiness'])
    ->name('coupons.for-business');
Route::post('/api/coupons/{coupon}/redeem', [PublicCouponController::class, 'redeem'])
    ->name('coupons.redeem');

// ✅ Coupon redemption flow (QR-based)
Route::post('/api/coupons/{coupon}/generate-token', [PublicCouponController::class, 'generateToken'])
    ->middleware(['auth', 'throttle:coupon-token'])
    ->name('coupons.generate-token');

Route::get('/redeem/{token}', [\App\Http\Controllers\Public\RedemptionController::class, 'show'])
    ->name('coupons.redeem.show');

Route::post('/api/redemption/{token}/confirm', [\App\Http\Controllers\Public\RedemptionController::class, 'confirm'])
    ->middleware(['auth', 'throttle:redemption-confirm'])
    ->name('coupons.redeem.confirm');

// PHASE 12 — LISTING-ATTRIBUTED CONNECTION.
// An inquiry belongs to the LISTING that generated it. Route model binding on
// the slug means the visitor explicitly submits against the exact Listing they
// viewed, so no representative Listing is ever selected. Business context is
// derived server-side from the Listing and is never visitor-supplied.
//
// The former POST /business/{business:slug}/contact is RETIRED. A Business may
// own many Listings, so it could not unambiguously attribute an inquiry; its old
// behaviour wrote `listing_id = NULL`, which this phase makes impossible.
Route::post('/listing/{listing:slug}/contact', [ListingLeadController::class, 'storeListing'])
    ->middleware('throttle:lead')
    ->name('listing.contact');

// Public reviews
Route::prefix('business/{business}/reviews')->name('business.reviews.')->group(function () {
    Route::get('/', [PublicReviewController::class, 'index'])->name('index');
    Route::post('/', [PublicReviewController::class, 'store'])
        ->middleware('throttle:review')
        ->name('store');
});

// PHASE 11 / WAVE 1D-3 — CANONICAL LISTING-SCOPED TRACKING.
// The route parameter is the LISTING and resolves directly through route model
// binding. It is not a renamed Business, and the controller never calls
// Business::primaryListing().
//
// The four Business-keyed tracking routes that used to live here
// (/analytics/track-view/{business}, /analytics/track-click/{business}/{type}
// and their /owner/analytics equivalents) were DELETED in this wave. Analytics
// are Listing-owned, so there is no legitimate Business-keyed ingestion path
// and no compatibility wrapper is kept.
Route::post('/analytics/listing/{listing}/track-view', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackListingView'])
    ->middleware('throttle:analytics')
    ->name('analytics.listing.track-view');
Route::post('/analytics/listing/{listing}/track-click/{type}', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackListingClick'])
    ->middleware('throttle:analytics')
    ->name('analytics.listing.track-click');

// Contact
Route::get('/contact', [PublicContactController::class, 'index'])->name('contact');
Route::post('/contact', [PublicContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.submit');

// About
Route::get('/about', fn() => Inertia::render('Public/About'))->name('about');

// =============================================================
// ✅ CURATED COLLECTION PAGES — /{category}-in-{city}
//
// MUST BE REGISTERED LAST — the wildcard {slug} would otherwise
// shadow every other 1-segment route. The controller also 404s
// anything that doesn't resolve to a real category + city combo.
// =============================================================
Route::get('/{slug}', [\App\Http\Controllers\Public\CollectionController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+-in-[a-z0-9\-]+')
    ->name('collection.show');

// Webhook (public, CSRF excluded)
Route::post('/webhook/fapshi', [FapshiController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.fapshi');


// ============== INVITATION ROUTES ==============
Route::get('/invitations/accept/{token}', [AuthInvitationController::class, 'accept'])
    ->name('invitations.accept');
Route::post('/invitations/register', [AuthInvitationController::class, 'register'])
    ->name('invitations.register');


// ============== DASHBOARD REDIRECT ==============
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user) {
        return match ($user->role) {
            'super_admin' => redirect()->route('admin.super.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'owner' => redirect()->route('owner.dashboard'),
            default => redirect()->route('user.dashboard'),
        };
    }
    return redirect()->route('login');
})->middleware(['auth'])->name('dashboard');




// =============================================================
// ============== AUTHENTICATED ROUTES ==========================
// =============================================================
Route::middleware(['auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Payment
    Route::prefix('payment/fapshi')->name('payment.fapshi.')->group(function () {
        Route::get('/checkout', [FapshiController::class, 'checkout'])->name('checkout');
        Route::post('/initiate', [FapshiController::class, 'initiatePayment'])->name('initiate');
        Route::get('/status/{transId}', [FapshiController::class, 'checkStatus'])->name('status');
    });

    // Push notifications
    Route::prefix('push')->name('push.')->group(function () {
        Route::post('/subscribe', [PushSubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])->name('unsubscribe');
    });

    // Favorites — PHASE 11 / WAVE 1D-5A: favorites are LISTING-owned.
    // The favoritable entity is a Listing; the old Business-keyed routes are
    // removed with no compatibility alias.
    Route::post('/favorites/{listing}/toggle', [FavoriteController::class, 'toggle'])
        ->middleware('throttle:favorites')
        ->name('favorites.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy'])
        ->name('favorites.destroy');
});


// =============================================================
// ============== USER ROUTES (Regular Customers) ==============
// =============================================================
Route::middleware(['auth', 'role:user,admin,super_admin'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reviews', fn() => Inertia::render('User/Reviews/Index'))->name('reviews.index');
    });


// =============================================================
// ============== BUSINESS MANAGEMENT (user + owner) ===========
// ============== For setting up a business and getting it approved
// =============================================================
Route::middleware(['auth', 'role:user,owner'])->group(function () {

    // =========================================================
    // PHASE 11 / WAVE 1D-2 — THE LISTING LIFECYCLE
    // A Listing is created directly as the discoverable entity. Business
    // association and Location are optional.
    // =========================================================
    Route::prefix('owner/listings')->name('owner.listings.')->group(function () {
        Route::get('/', [OwnerListingController::class, 'index'])->name('index');
        Route::get('/create', [OwnerListingController::class, 'create'])->name('create');
        Route::post('/', [OwnerListingController::class, 'store'])->name('store');
        Route::get('/{listing}/edit', [OwnerListingController::class, 'edit'])->name('edit');
        Route::put('/{listing}', [OwnerListingController::class, 'update'])->name('update');
        Route::post('/{listing}/publish', [OwnerListingController::class, 'publish'])->name('publish');
        Route::post('/{listing}/unpublish', [OwnerListingController::class, 'unpublish'])->name('unpublish');
        Route::delete('/{listing}', [OwnerListingController::class, 'destroy'])->name('destroy');

        // PHASE 11 / WAVE 1D-3 — SERVICES ARE LISTING-OWNED.
        // The Listing is the authoritative route entity: no Business ->
        // primaryListing() bridge and no arbitrary Listing selection.
        Route::prefix('{listing}/services')->name('services.')->group(function () {
            Route::get('/', [ServiceController::class, 'index'])->name('index');
            Route::post('/', [ServiceController::class, 'store'])
                ->name('store')
                ->middleware('plan.limit:services');
            Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
            Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');
        });

        // PHASE 11 / WAVE 1D-3 — CONTACTS ARE LISTING-OWNED.
        // The Listing is the authoritative route entity: no Business ->
        // primaryListing() bridge and no arbitrary Listing selection.
        Route::prefix('{listing}/contacts')->name('contacts.')->group(function () {
            Route::get('/', [ContactController::class, 'index'])->name('index');
            Route::post('/', [ContactController::class, 'store'])->name('store');
            Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        });

        // PHASE 11 / WAVE 1D-3 — LISTING MEDIA (LISTING-OWNED).
        // Explicit Listing context; never resolved from a Business.
        Route::prefix('{listing}/images')->name('images.')->group(function () {
            Route::get('/', [ListingImageController::class, 'index'])->name('index');
            Route::post('/', [ListingImageController::class, 'store'])->name('store');
            Route::delete('/{image}', [ListingImageController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('owner/businesses')->name('owner.businesses.')->group(function () {

        // Business CRUD
        Route::get('/create', [OwnerBusinessController::class, 'create'])
            ->name('create')
            ->middleware('plan.limit:listings');
        Route::post('/', [OwnerBusinessController::class, 'store'])
            ->name('store')
            ->middleware('plan.limit:listings');
        Route::get('/{business}/edit', [OwnerBusinessController::class, 'edit'])->name('edit');
        Route::put('/{business}', [OwnerBusinessController::class, 'update'])->name('update');
        Route::post('/{business}/submit', [OwnerBusinessController::class, 'submit'])
            ->name('submit')
            ->middleware('verified');
        Route::post('/{business}/toggle-active', [OwnerBusinessController::class, 'toggleActive'])
            ->name('toggle-active');
        Route::delete('/{business}', [OwnerBusinessController::class, 'destroy'])->name('destroy');

        // ✅ Locations (physical places — needed for setup)
        Route::prefix('{business}/locations')->name('locations.')->group(function () {
            Route::get('/', [OwnerLocationController::class, 'index'])->name('index');
            Route::get('/create', [OwnerLocationController::class, 'create'])->name('create');
            Route::post('/', [OwnerLocationController::class, 'store'])
                ->name('store')
                ->middleware('plan.limit:locations');
            Route::get('/{location}/edit', [OwnerLocationController::class, 'edit'])->name('edit');
            Route::put('/{location}', [OwnerLocationController::class, 'update'])->name('update');
            Route::delete('/{location}', [OwnerLocationController::class, 'destroy'])->name('destroy');
        });

        // PHASE 11 / WAVE 1D-3 — the Business-scoped services routes were
        // REMOVED. A service is Listing-owned, so service management now lives at
        // /owner/listings/{listing}/services (route names owner.listings.services.*).

        // PHASE 11 / WAVE 1D-3 — the Business-scoped contacts routes were
        // REMOVED. A contact is Listing-owned, so contact management now lives at
        // /owner/listings/{listing}/contacts (route names owner.listings.contacts.*).

        // PHASE 11 / WAVE 1D-3 — ORGANIZATION BRANDING (BUSINESS-OWNED).
        // This route manages ONLY `businesses.logo` / `businesses.cover_image`.
        // It no longer pretends to manage Listing media, and a Business may
        // legitimately have zero Listings and still hold branding.
        // Listing presentation media lives at /owner/listings/{listing}/images.
        Route::prefix('{business}/images')->name('images.')->group(function () {
            Route::get('/', [ImageController::class, 'index'])->name('index');
            Route::post('/', [ImageController::class, 'store'])
                ->name('store')
                ->middleware('plan.limit:images');
            Route::delete('/{type}', [ImageController::class, 'destroy'])
                ->whereIn('type', ['logo', 'cover'])
                ->name('destroy');
        });

        // ✅ Hours (of a physical Location — needed for setup)
        Route::prefix('{business}/locations/{location}/hours')->name('hours.')->group(function () {
            Route::get('/', [HourController::class, 'index'])->name('index');
            Route::post('/batch', [HourController::class, 'storeBatch'])->name('batch');
            Route::get('/status', [HourController::class, 'status'])->name('status');

            // ✅ Date overrides — MUST come before the {hour} wildcard
            Route::post('/overrides', [HourController::class, 'storeOverride'])->name('overrides.store');
            Route::delete('/overrides/{override}', [HourController::class, 'destroyOverride'])->name('overrides.destroy');

            // Keep this LAST so it doesn't shadow 'overrides' and 'status'
            Route::delete('/{hour}', [HourController::class, 'destroy'])->name('destroy');
        });
    });

    // Subscription
    Route::prefix('owner/subscription')->name('owner.subscription.')->group(function () {
        Route::get('/', [OwnerSubscriptionController::class, 'index'])->name('index');
        Route::get('/renew', [OwnerSubscriptionController::class, 'renew'])->name('renew');
        Route::post('/select-plan', [OwnerSubscriptionController::class, 'selectPlan'])->name('select-plan');
        Route::get('/payment', [OwnerSubscriptionController::class, 'payment'])->name('payment');
        Route::post('/process-payment', [OwnerSubscriptionController::class, 'processPayment'])->name('process-payment');
        Route::get('/status', [OwnerSubscriptionController::class, 'status'])->name('status');
    });
});

// =============================================================
// ============== OWNER ROUTES (Full Access) ===================
// =============================================================
Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/usage', [OwnerDashboardController::class, 'usage'])->name('usage');

    // Businesses (list + delete + featured — creation handled above)
    Route::prefix('businesses')->name('businesses.')->group(function () {
        Route::get('/', [OwnerBusinessController::class, 'index'])->name('index');

        Route::post('/{business}/toggle-featured', [OwnerBusinessController::class, 'toggleFeatured'])
            ->name('toggle-featured')
            ->middleware('plan.feature:featured_listing');
        Route::post('/{business}/toggle-active', [OwnerBusinessController::class, 'toggleActive'])
            ->name('toggle-active');
    });

    // Coupons
    Route::prefix('coupons')->name('coupons.')->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/create', [CouponController::class, 'create'])
            ->name('create')
            ->middleware('plan.feature:coupons');
        Route::post('/', [CouponController::class, 'store'])
            ->name('store')
            ->middleware(['plan.feature:coupons', 'plan.limit:coupons']);
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');
        Route::get('/{coupon}/analytics', [CouponController::class, 'analytics'])->name('analytics');
    });

    // Reviews — PHASE 11 / WAVE 1D: EXPLICIT Business context.
    // A Review is Business-owned, so the Business is identified by the route,
    // never selected from the owner's Businesses.
    Route::prefix('businesses/{business}/reviews')->name('businesses.reviews.')->group(function () {
        Route::get('/', [OwnerReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [OwnerReviewController::class, 'show'])->name('show');
        Route::post('/{review}/reply', [OwnerReviewController::class, 'reply'])
            ->name('reply')
            ->middleware('plan.feature:respond_to_reviews');
    });

    // Analytics — PHASE 11 / WAVE 1D-3: the Business-keyed tracking writers
    // (/track-view/{business}, /track-click/{business}/{type}) were DELETED.
    // Analytics are Listing-owned and recorded through
    // /analytics/listing/{listing}/track-*. What remains here are the
    // legitimate Business AGGREGATE reads and exports.
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');

        // ✅ Export
        Route::get('/export/csv', [AnalyticsController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/pdf', [AnalyticsController::class, 'exportPdf'])->name('export.pdf');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [NotificationController::class, 'dropdown'])->name('dropdown');
        Route::get('/count', [NotificationController::class, 'count'])->name('count');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('update-preferences');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Leads — PHASE 11 / WAVE 1D: EXPLICIT Business context.
    // A Lead is Business-owned, so the Business is identified by the route,
    // never selected from the owner's Businesses.
    // Inquiries — PHASE 12B: LISTING-attributed owner access.
    //
    // The authoritative relationship is Inquiry -> Listing -> Listing owner.
    // Business is OPTIONAL context and is NOT part of authorization, so this is
    // the only route set that can reach an inquiry belonging to a Listing with
    // `business_id = NULL`. It reuses the existing Owner/Leads Inertia pages.
    //
    // No `plan.feature:lead_capture` middleware here: that gate resolves through
    // a Business, and a Business-less Listing has none. The public submission
    // gate in ListingLeadController remains the entitlement boundary.
    Route::prefix('listings/{listing}/leads')->name('listings.leads.')->group(function () {
        Route::get('/', [OwnerListingLeadController::class, 'index'])->name('index');
        Route::get('/{lead}', [OwnerListingLeadController::class, 'show'])->name('show');
        Route::put('/{lead}/status', [OwnerListingLeadController::class, 'updateStatus'])->name('update-status');
        Route::put('/{lead}/notes', [OwnerListingLeadController::class, 'updateNotes'])->name('update-notes');
        Route::delete('/{lead}', [OwnerListingLeadController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('businesses/{business}/leads')->name('businesses.leads.')->group(function () {
        Route::get('/', [OwnerLeadController::class, 'index'])
            ->name('index')
            ->middleware('plan.feature:lead_capture');
        Route::get('/{lead}', [OwnerLeadController::class, 'show'])
            ->name('show')
            ->middleware('plan.feature:lead_capture');
        Route::put('/{lead}/status', [OwnerLeadController::class, 'updateStatus'])
            ->name('update-status')
            ->middleware('plan.feature:lead_capture');
        Route::put('/{lead}/notes', [OwnerLeadController::class, 'updateNotes'])
            ->name('update-notes')
            ->middleware('plan.feature:lead_capture');
        Route::delete('/{lead}', [OwnerLeadController::class, 'destroy'])
            ->name('destroy')
            ->middleware('plan.feature:lead_capture');
    });


});






// =============================================================
// ============== ADMIN ROUTES ==================================
// =============================================================
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Super admin only
    Route::middleware(['role:super_admin'])->group(function () {
        Route::get('/super-dashboard', [SuperDashboardController::class, 'index'])->name('super.dashboard');

        // ✅ Impersonation
        Route::post('/users/{user}/impersonate', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])
            ->middleware('throttle:impersonate')
            ->name('users.impersonate');

        // ✅ Danger Zone actions
        Route::post('/actions/maintenance', [\App\Http\Controllers\Admin\SuperAdminActionsController::class, 'toggleMaintenance'])
            ->middleware('throttle:admin-actions')
            ->name('actions.maintenance');
        Route::post('/actions/cache-clear', [\App\Http\Controllers\Admin\SuperAdminActionsController::class, 'clearCache'])
            ->middleware('throttle:admin-actions')
            ->name('actions.cache-clear');
        Route::post('/actions/optimize-clear', [\App\Http\Controllers\Admin\SuperAdminActionsController::class, 'optimizeClear'])
            ->middleware('throttle:admin-actions')
            ->name('actions.optimize-clear');
        Route::post('/actions/queue-restart', [\App\Http\Controllers\Admin\SuperAdminActionsController::class, 'restartQueue'])
            ->middleware('throttle:admin-actions')
            ->name('actions.queue-restart');
    });

    // API
    Route::get('/api/subscriptions', [PaymentController::class, 'getSubscriptions'])->name('api.subscriptions');

    // Revenue Analytics
    Route::get('/revenue', [RevenueController::class, 'index'])->name('revenue');

    // Contacts
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [AdminContactController::class, 'index'])->name('index');
        Route::post('/bulk-delete', [AdminContactController::class, 'bulkDestroy'])->name('bulk-delete');
        Route::post('/bulk-mark-read', [AdminContactController::class, 'bulkMarkRead'])->name('bulk-mark-read');
        Route::get('/{id}', [AdminContactController::class, 'show'])->name('show');
        Route::delete('/{id}', [AdminContactController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/mark-replied', [AdminContactController::class, 'markReplied'])->name('mark-replied');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::get('/dropdown', [AdminNotificationController::class, 'dropdown'])->name('dropdown');
        Route::get('/count', [AdminNotificationController::class, 'count'])->name('count');
        Route::get('/unread-count', [AdminNotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::post('/mark-all-as-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::post('/{id}/mark-as-read', [AdminNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::delete('/{id}', [AdminNotificationController::class, 'destroy'])->name('destroy');
        Route::get('/create', [AdminNotificationController::class, 'create'])->name('create');
        Route::post('/send', [AdminNotificationController::class, 'send'])->name('send');

        // Scheduled
        Route::get('/scheduled', [AdminNotificationController::class, 'scheduled'])->name('scheduled');
        Route::post('/scheduled/{id}/cancel', [AdminNotificationController::class, 'cancelScheduled'])->name('scheduled.cancel');
        Route::delete('/scheduled/{id}', [AdminNotificationController::class, 'destroyScheduled'])->name('scheduled.destroy');
        Route::post('/scheduled/{id}/reschedule', [AdminNotificationController::class, 'reschedule'])->name('scheduled.reschedule');
        Route::post('/scheduled/{id}/send-now', [AdminNotificationController::class, 'sendNow'])->name('scheduled.send-now');
    });

    // Reviews
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/export/csv', [ReviewExportController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/json', [ReviewExportController::class, 'exportJson'])->name('export.json');
        Route::get('/export/pdf', [ReviewExportController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export', [ReviewExportController::class, 'export'])->name('export');
        Route::get('/', [AdminReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [AdminReviewController::class, 'show'])->name('show');
        Route::post('/{review}/approve', [AdminReviewController::class, 'approve'])->name('approve');
        Route::post('/{review}/reject', [AdminReviewController::class, 'reject'])->name('reject');
        Route::delete('/{review}', [AdminReviewController::class, 'destroy'])->name('destroy');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/tree', [CategoryController::class, 'tree'])->name('tree');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('reorder');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/toggle', [CategoryController::class, 'toggleActive'])->name('toggle');
    });

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Owners
    Route::prefix('owners')->name('owners.')->group(function () {
        Route::get('/', [OwnerController::class, 'index'])->name('index');
        Route::get('/statistics', [OwnerController::class, 'statistics'])->name('statistics');
        Route::get('/{owner}', [OwnerController::class, 'show'])->name('show');
        Route::get('/{owner}/edit', [OwnerController::class, 'edit'])->name('edit');
        Route::put('/{owner}', [OwnerController::class, 'update'])->name('update');
        Route::post('/{owner}/validate', [OwnerController::class, 'validateOwner'])->name('validate');
        Route::post('/{owner}/suspend', [OwnerController::class, 'suspend'])->name('suspend');
        Route::post('/{owner}/activate', [OwnerController::class, 'activate'])->name('activate');
        Route::delete('/{owner}', [OwnerController::class, 'destroy'])->name('destroy');
    });

    // Locations
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/countries', [LocationController::class, 'countries'])->name('countries');
        Route::post('/countries', [LocationController::class, 'storeCountry'])->name('countries.store');
        Route::put('/countries/{country}', [LocationController::class, 'updateCountry'])->name('countries.update');
        Route::post('/countries/{country}/toggle', [LocationController::class, 'toggleCountry'])->name('countries.toggle');
        Route::delete('/countries/{country}', [LocationController::class, 'destroyCountry'])->name('countries.destroy');

        Route::get('/regions', [LocationController::class, 'regions'])->name('regions');
        Route::post('/regions', [LocationController::class, 'storeRegion'])->name('regions.store');
        Route::put('/regions/{region}', [LocationController::class, 'updateRegion'])->name('regions.update');
        Route::post('/regions/{region}/toggle', [LocationController::class, 'toggleRegion'])->name('regions.toggle');
        Route::delete('/regions/{region}', [LocationController::class, 'destroyRegion'])->name('regions.destroy');

        Route::get('/cities', [LocationController::class, 'cities'])->name('cities');
        Route::post('/cities', [LocationController::class, 'storeCity'])->name('cities.store');
        Route::put('/cities/{city}', [LocationController::class, 'updateCity'])->name('cities.update');
        Route::post('/cities/{city}/toggle', [LocationController::class, 'toggleCity'])->name('cities.toggle');
        Route::delete('/cities/{city}', [LocationController::class, 'destroyCity'])->name('cities.destroy');

        Route::get('/areas', [LocationController::class, 'areas'])->name('areas');
        Route::post('/areas', [LocationController::class, 'storeArea'])->name('areas.store');
        Route::put('/areas/{area}', [LocationController::class, 'updateArea'])->name('areas.update');
        Route::post('/areas/{area}/toggle', [LocationController::class, 'toggleArea'])->name('areas.toggle');
        Route::delete('/areas/{area}', [LocationController::class, 'destroyArea'])->name('areas.destroy');
    });

    // Businesses
    Route::prefix('businesses')->name('businesses.')->group(function () {
        Route::get('/', [AdminBusinessController::class, 'index'])->name('index');
        Route::post('/{id}/restore', [AdminBusinessController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [AdminBusinessController::class, 'forceDelete'])->name('force-delete');
        Route::get('/{business}', [AdminBusinessController::class, 'show'])->name('show');
        Route::post('/{business}/activate', [AdminBusinessController::class, 'activate'])->name('activate');
        Route::post('/{business}/approve', [AdminBusinessController::class, 'approve'])->name('approve');
        Route::post('/{business}/publish', [AdminBusinessController::class, 'publish'])->name('publish');
        Route::post('/{business}/reject', [AdminBusinessController::class, 'reject'])->name('reject');
        Route::post('/{business}/suspend', [AdminBusinessController::class, 'suspend'])->name('suspend');
        Route::delete('/{business}', [AdminBusinessController::class, 'destroy'])->name('destroy');

    });

    // Invitations
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [InvitationController::class, 'index'])->name('index');
        Route::get('/create', [InvitationController::class, 'create'])->name('create');
        Route::post('/', [InvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend');
        Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
    });

    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('/create', [PlanController::class, 'create'])->name('create');
        Route::post('/', [PlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [PlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/toggle', [PlanController::class, 'toggleActive'])->name('toggle');
        Route::post('/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('duplicate');
    });

    // Subscriptions
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index'])->name('index');
        Route::get('/create', [AdminSubscriptionController::class, 'create'])->name('create');
        Route::post('/', [AdminSubscriptionController::class, 'store'])->name('store');
        Route::get('/export', [AdminSubscriptionController::class, 'export'])->name('export');
        Route::post('/bulk-action', [AdminSubscriptionController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{subscription}', [AdminSubscriptionController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [AdminSubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [AdminSubscriptionController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('destroy');
        Route::post('/{subscription}/mark-failed', [AdminSubscriptionController::class, 'markFailed'])->name('mark-failed');
        Route::post('/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/activate', [AdminSubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{subscription}/suspend', [AdminSubscriptionController::class, 'suspend'])->name('suspend');
    });

    // Payments (read-only view of Fapshi transactions)
    // The legacy manual-payment flow (create/store) has been removed —
    // the platform has fully migrated to automated payment via Fapshi.
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
        Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });
});


// =============================================================
// ============== IMPERSONATION (auth only) ====================
// =============================================================
// Placed outside the admin/super_admin gate so that an impersonated
// user can still hit it to return to the original admin account.
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/impersonate/stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])
        ->name('admin.impersonate.stop');
});



// =============================================================
// ============== AUTH ROUTES ===================================
// =============================================================
require __DIR__ . '/auth.php';