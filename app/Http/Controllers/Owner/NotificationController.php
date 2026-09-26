<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(20);
        $unreadCount = $user->unreadNotifications()->count();

        return Inertia::render('Owner/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    // API endpoint for dropdown
    public function dropdown(Request $request)
    {
        $user = auth()->user();

        // ✅ Lightweight polling mode — only return the count
        if ($request->boolean('count_only')) {
            return response()->json([
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        $notifications = $user->notifications()->take(10)->get();
        $unreadCount = $user->unreadNotifications()->count();
        $total = $user->notifications()->count();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
            'total' => $total,
        ]);
    }

    // Get unread count only (for polling)
    public function count()
    {
        $user = auth()->user();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->back()
            ->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->markAllNotificationsAsRead();

        return redirect()->back()
            ->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back()
            ->with('success', 'Notification deleted.');
    }

    public function preferences()
    {
        $user = auth()->user();

        // ✅ Source of truth — all types defined in NotificationHelper
        $allTypes = NotificationHelper::getNotificationTypes();

        // Filter out admin-only types
        $adminOnly = ['admin_notification', 'system'];
        $ownerTypes = array_filter(
            $allTypes,
            fn($key) => !in_array($key, $adminOnly, true),
            ARRAY_FILTER_USE_KEY
        );

        // Group them by category for display
        $grouped = [
            'account' => [
                'label' => 'Account',
                'types' => [],
            ],
            'business' => [
                'label' => 'Business',
                'types' => [],
            ],
            'subscription' => [
                'label' => 'Subscription & Payments',
                'types' => [],
            ],
            'engagement' => [
                'label' => 'Customer engagement',
                'types' => [],
            ],
        ];

        $typeToGroup = [
            'invitation' => 'account',
            'account_approved' => 'account',
            'account_promoted_to_owner' => 'account',

            'business_approved' => 'business',
            'business_published' => 'business',
            'business_rejected' => 'business',
            'business_submitted' => 'business',

            'subscription_active' => 'subscription',
            'subscription_expiring' => 'subscription',
            'subscription_expired' => 'subscription',
            'subscription_grace_period' => 'subscription',
            'subscription_suspended' => 'subscription',
            'subscription_renewed' => 'subscription',
            'payment_received' => 'subscription',
            'payment_failed' => 'subscription',
            'payment_failed_alert' => 'subscription',
            'payment_expired' => 'subscription',

            'new_review' => 'engagement',
            'new_lead' => 'engagement',
        ];

        foreach ($ownerTypes as $key => $meta) {
            $pref = $user->getNotificationPreference($key);
            $groupKey = $typeToGroup[$key] ?? 'account';

            $grouped[$groupKey]['types'][] = [
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'description' => $meta['description'] ?? '',
                'icon' => $meta['icon'] ?? '📢',
                'preferences' => $pref,
            ];
        }

        // Drop empty groups
        $grouped = array_filter($grouped, fn($g) => !empty($g['types']));

        return Inertia::render('Owner/Notifications/Preferences', [
            'groups' => array_values($grouped),
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.type' => 'required|string',
            'preferences.*.email' => 'boolean',
            'preferences.*.in_app' => 'boolean',
            'preferences.*.sms' => 'boolean',
        ]);

        // ✅ Only accept types the app actually knows about
        $validTypes = array_keys(NotificationHelper::getNotificationTypes());

        foreach ($validated['preferences'] as $pref) {
            if (!in_array($pref['type'], $validTypes, true)) {
                continue;
            }

            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $pref['type'],
                ],
                [
                    'email' => $pref['email'] ?? true,
                    'in_app' => $pref['in_app'] ?? true,
                    'sms' => $pref['sms'] ?? false,
                ]
            );
        }

        return redirect()->route('owner.notifications.index')
            ->with('success', 'Notification preferences updated.');
    }

    public function getUnreadCount()
    {
        $user = auth()->user();
        $count = $user->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }
}