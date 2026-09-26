<?php
// app/Http/Controllers/Api/PushSubscriptionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Store a push subscription
     */
    public function subscribe(Request $request)
    {
        try {
            Log::info('Push subscription request received', [
                'user_id' => auth()->id(),
                'data' => $request->all()
            ]);

            $validated = $request->validate([
                'endpoint' => 'required|string',
                'keys' => 'required|array',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string',
                'user_agent' => 'nullable|string',
            ]);

            // ✅ Check if user is authenticated (session-based)
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $user = auth()->user();

            // ✅ Check if subscription already exists
            $existingSubscription = PushSubscription::where('endpoint', $validated['endpoint'])
                ->where('user_id', $user->id)
                ->first();

            if ($existingSubscription) {
                $existingSubscription->update([
                    'keys' => $validated['keys'],
                    'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                ]);
                $subscription = $existingSubscription;
            } else {
                $subscription = PushSubscription::create([
                    'user_id' => $user->id,
                    'endpoint' => $validated['endpoint'],
                    'keys' => $validated['keys'],
                    'user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                ]);
            }

            Log::info('Push subscription saved', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
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

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request)
    {
        try {
            $validated = $request->validate([
                'endpoint' => 'required|string',
            ]);

            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

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