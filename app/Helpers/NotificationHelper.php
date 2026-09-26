<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Notification;
use App\Services\PushNotificationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationMail;

class NotificationHelper
{
    protected static ?PushNotificationService $pushService = null;

    /**
     * Notification types that should also trigger an email.
     * Everything else stays in-app + push only.
     */
    protected const EMAIL_WHITELIST = [
        'payment_received',
        'payment_failed',
        'payment_failed_alert',
        'payment_expired',
        'subscription_active',
        'subscription_expiring',
        'subscription_expired',
        'subscription_renewed',
        'subscription_suspended',
        'subscription_grace_period',
        'account_approved',
        'invitation',
    ];

    /**
     * Initialize push service if needed
     */
    protected static function getPushService(): PushNotificationService
    {
        if (self::$pushService === null) {
            self::$pushService = app(PushNotificationService::class);
        }
        return self::$pushService;
    }

    /**
     * Send a notification to a user.
     *
     * @param  User|null  $user
     * @param  string     $title
     * @param  string     $message
     * @param  string|null $actionUrl
     * @param  array      $data
     * @param  string     $type           Notification type key
     * @param  bool       $force          true = bypass user preferences (critical messages)
     * @return Notification|false
     */
    public static function send(
        $user,
        $title,
        $message,
        $actionUrl = null,
        $data = [],
        $type = 'general',
        $force = false
    ) {
        if (!$user || !$user instanceof User) {
            Log::error('Invalid user provided to NotificationHelper::send', [
                'user_type' => is_object($user) ? get_class($user) : gettype($user),
            ]);
            return false;
        }

        try {
            $preferences = $force
                ? ['in_app' => true, 'push' => true, 'email' => true, 'sms' => false]
                : $user->getNotificationPreference($type);

            $notification = null;

            // ================================================
            // In-app notification (DB)
            // ================================================
            if ($preferences['in_app']) {
                $notification = Notification::create([
                    'id' => (string) Str::uuid(),
                    'type' => $type,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => [
                        'title' => $title,
                        'message' => $message,
                        'action_url' => $actionUrl,
                        'data' => $data,
                    ],
                    'read_at' => null,
                ]);
            }

            // ================================================
            // Email — only for whitelisted types
            // ================================================
            if ($preferences['email'] && in_array($type, self::EMAIL_WHITELIST, true)) {
                try {
                    Mail::to($user->email)->send(new NotificationMail(
                        $title,
                        $message,
                        $actionUrl
                    ));
                } catch (\Exception $e) {
                    Log::error('NotificationHelper: failed to send email', [
                        'user_id' => $user->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the whole operation if email fails
                }
            }

            // ================================================
            // Push notification
            // ================================================
            if ($preferences['push']) {
                try {
                    $pushService = self::getPushService();
                    $pushService->sendToUser($user, $title, $message, null, $actionUrl);
                } catch (\Exception $e) {
                    Log::error('NotificationHelper: failed to send push', [
                        'user_id' => $user->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the whole operation if push fails
                }
            }

            return $notification ?? false;

        } catch (\Exception $e) {
            Log::error('NotificationHelper::send failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'type' => $type,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send a notification to multiple users
     */
    public static function sendToMany(
        $users,
        $title,
        $message,
        $actionUrl = null,
        $data = [],
        $type = 'general',
        $force = false
    ) {
        $notifications = [];
        foreach ($users as $user) {
            $notification = self::send($user, $title, $message, $actionUrl, $data, $type, $force);
            if ($notification) {
                $notifications[] = $notification;
            }
        }
        return $notifications;
    }

    /**
     * Send a notification to all admins
     */
    public static function sendToAdmins(
        $title,
        $message,
        $actionUrl = null,
        $data = [],
        $type = 'general',
        $force = true
    ) {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        if ($admins->isEmpty()) {
            Log::warning('NotificationHelper: no admins found for notification', ['type' => $type]);
            return [];
        }

        return self::sendToMany($admins, $title, $message, $actionUrl, $data, $type, $force);
    }

    /**
     * Get all notification types (rich version with icons)
     */
    public static function getNotificationTypes()
    {
        return [
            'invitation' => [
                'label' => 'Invitations',
                'description' => 'Notifications about business invitations',
                'icon' => '📧',
            ],
            'account_approved' => [
                'label' => 'Account Approval',
                'description' => 'Notifications about account approval',
                'icon' => '✅',
            ],
            'business_approved' => [
                'label' => 'Business Approval',
                'description' => 'Notifications about business approval',
                'icon' => '🎉',
            ],
            'business_published' => [
                'label' => 'Business Published',
                'description' => 'Notifications when business is published',
                'icon' => '🚀',
            ],
            'business_rejected' => [
                'label' => 'Business Rejected',
                'description' => 'Notifications when business is rejected',
                'icon' => '❌',
            ],
            'business_submitted' => [
                'label' => 'Business Submitted',
                'description' => 'Notifications when a business is submitted for review',
                'icon' => '📝',
            ],
            'new_review' => [
                'label' => 'New Review',
                'description' => 'Notifications when a business receives a new review',
                'icon' => '⭐',
            ],
            'subscription_active' => [
                'label' => 'Subscription Active',
                'description' => 'Notifications when subscription is activated',
                'icon' => '💳',
            ],
            'subscription_expiring' => [
                'label' => 'Subscription Expiring',
                'description' => 'Notifications when subscription is about to expire',
                'icon' => '⚠️',
            ],
            'subscription_expired' => [
                'label' => 'Subscription Expired',
                'description' => 'Notifications when subscription expires',
                'icon' => '⛔',
            ],
            'subscription_grace_period' => [
                'label' => 'Grace Period',
                'description' => 'Notifications when subscription enters grace period',
                'icon' => '⏳',
            ],
            'subscription_suspended' => [
                'label' => 'Subscription Suspended',
                'description' => 'Notifications when subscription is suspended',
                'icon' => '🚫',
            ],
            'subscription_renewed' => [
                'label' => 'Subscription Renewed',
                'description' => 'Notifications when subscription is renewed',
                'icon' => '🔄',
            ],
            'admin_notification' => [
                'label' => 'Admin Alerts',
                'description' => 'Notifications for admin actions',
                'icon' => '📢',
            ],
            'payment_received' => [
                'label' => 'Payment Received',
                'description' => 'Notifications when a payment is received',
                'icon' => '💰',
            ],
            'payment_failed' => [
                'label' => 'Payment Failed',
                'description' => 'Notifications when a payment fails',
                'icon' => '❌',
            ],
            'payment_expired' => [
                'label' => 'Payment Expired',
                'description' => 'Notifications when a payment link expires',
                'icon' => '⏰',
            ],
            'system' => [
                'label' => 'System Updates',
                'description' => 'System-wide notifications',
                'icon' => '🔔',
            ],
            'new_lead' => [
                'label' => 'New Leads',
                'description' => 'Notifications when a customer sends you an inquiry',
                'icon' => '📬',
            ],
            'new_review' => [
                'label' => 'New Reviews',
                'description' => 'Notifications when a business receives a new review',
                'icon' => '⭐',
            ],
            'payment_failed_alert' => [
                'label' => 'Payment Alerts',
                'description' => 'Escalated alerts for repeated or severe payment failures',
                'icon' => '🚨',
            ],
            'account_promoted_to_owner' => [
                'label' => 'Account Promotions',
                'description' => 'Notifications when your account is upgraded',
                'icon' => '👑',
            ],
        ];


    }
}