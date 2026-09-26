<?php

use App\Http\Controllers\Api\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnalyticsController;
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


// Analytics Tracking Routes
Route::post('/analytics/track-view/{business}', [AnalyticsController::class, 'trackView']);
Route::post('/analytics/track-click/{business}/{type}', [AnalyticsController::class, 'trackClick']);



Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe']);
Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
