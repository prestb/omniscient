<?php
// app/Http/Controllers/PushNotificationController.php

namespace App\Http\Controllers;

use App\Models\PushSubscription; // ✅ Use PushSubscription, NOT Notification
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        try {
            $validated = $request->validate([
                'endpoint' => 'required|string',
                'keys' => 'required|array',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
                'user_agent' => 'nullable|string',
            ]);

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // ✅ Use PushSubscription model
            $subscription = PushSubscription::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'endpoint' => $validated['endpoint'],
                ],
                [
                    'keys' => $validated['keys'],
                    'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                ]
            );

            Log::info('Push subscription saved', [
                'subscription_id' => $subscription->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscribed to push notifications',
                'data' => $subscription,
            ]);

        } catch (\Exception $e) {
            Log::error('Push subscription error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function unsubscribe(Request $request)
    {
        try {
            $validated = $request->validate([
                'endpoint' => 'required|string',
            ]);

            // ✅ Use PushSubscription model
            $deleted = PushSubscription::where('user_id', auth()->id())
                ->where('endpoint', $validated['endpoint'])
                ->delete();

            Log::info('Push unsubscription', [
                'user_id' => auth()->id(),
                'deleted' => $deleted,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unsubscribed from push notifications',
                'deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Push unsubscription error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe: ' . $e->getMessage(),
            ], 500);
        }
    }
}