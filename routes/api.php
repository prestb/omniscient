<?php

use App\Http\Controllers\Api\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PushSubscriptionController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API Routes
Route::prefix('locations')->group(function () {
    Route::get('/countries', [LocationController::class, 'countries']);
    Route::get('/regions', [LocationController::class, 'regions']);
    Route::get('/cities', [LocationController::class, 'cities']);
    Route::get('/areas', [LocationController::class, 'areas']);
    Route::get('/hierarchy', [LocationController::class, 'hierarchy']);
});


// PHASE 11 / WAVE 1D-3 — the Business-keyed analytics tracking routes
// (/api/analytics/track-view/{business} and /api/analytics/track-click/{business}/{type})
// were DELETED. Analytics are Listing-owned; the canonical public ingestion path
// is /analytics/listing/{listing}/track-view|track-click/{type} in routes/web.php.
// No compatibility wrapper is provided.



Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
