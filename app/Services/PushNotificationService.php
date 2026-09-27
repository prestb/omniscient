<?php
// app/Services/PushNotificationService.php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject', 'mailto:donyohel@gmail.com'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);
    }

    /**
     * Send push notification to a user
     */
    public function sendToUser(User $user, string $title, string $body, ?string $icon = null, ?string $url = null): int
    {
        // ✅ Fix: Ensure we get a collection
        $subscriptions = $user->pushSubscriptions()->get();
        $sent = 0;

        // ✅ Fix: Use count() instead of isEmpty()
        if ($subscriptions->count() === 0) {
            Log::info('No push subscriptions for user', ['user_id' => $user->id]);
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?? asset('images/icon-192.png'),
            'badge' => asset('images/icon-72.png'),
            'url' => $url ?? '/',
        ]);

        foreach ($subscriptions as $subscription) {
            try {
                // ✅ Check if keys exist before using them
                $keys = $subscription->keys ?? [];

                $pushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'authToken' => $keys['auth'] ?? '',
                    'publicKey' => $keys['p256dh'] ?? '',
                ]);

                $this->webPush->queueNotification(
                    $pushSubscription,
                    $payload
                );
                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to queue push notification', [
                    'error' => $e->getMessage(),
                    'endpoint' => $subscription->endpoint,
                ]);
            }
        }

        // Send all notifications
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                Log::warning('Push notification failed', [
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);

                // ✅ FIX: Use fully qualified class name
                if ($report->isSubscriptionExpired()) {
                    \App\Models\PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }

        return $sent;
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToUsers($users, string $title, string $body, ?string $icon = null, ?string $url = null): int
    {
        $sent = 0;
        foreach ($users as $user) {
            $sent += $this->sendToUser($user, $title, $body, $icon, $url);
        }
        return $sent;
    }

    /**
     * Send push notification to all admins
     */
    public function sendToAdmins(string $title, string $body, ?string $icon = null, ?string $url = null): int
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        return $this->sendToUsers($admins, $title, $body, $icon, $url);
    }

    /**
     * Send push notification to all owners
     */
    public function sendToOwners(string $title, string $body, ?string $icon = null, ?string $url = null): int
    {
        $owners = User::where('role', 'owner')->get();
        return $this->sendToUsers($owners, $title, $body, $icon, $url);
    }
}