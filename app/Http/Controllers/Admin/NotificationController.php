<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

use App\Models\ScheduledNotification;
use Carbon\Carbon;

class NotificationController extends Controller
{

    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(20);
        $unreadCount = $user->unreadNotifications()->count();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    // API endpoint for dropdown
    public function dropdown()
    {
        $user = auth()->user();
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

        // ✅ Redirect so the Inertia router gets a proper response.
        //    The dropdown's axios call ignores the response body and
        //    updates local state directly, so this works for both.
        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        $user->markAllNotificationsAsRead();

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted.');
    }

    public function getUnreadCount()
    {
        $user = auth()->user();
        $count = $user->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Show admin notification send page
     */
    public function create()
    {
        return Inertia::render('Admin/Notifications/Send', [
            'userCount' => User::count(),
            'adminCount' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'ownerCount' => User::where('role', 'owner')->count(),
        ]);
    }

    // /**
    //  * Send notification to users
    //  */
    // public function send(Request $request)
    // {
    //     $validated = $request->validate([
    //         'title' => 'required|string|max:255',
    //         'message' => 'required|string|max:5000',
    //         'recipients' => 'required|array|min:1',
    //         'recipients.*' => 'string|in:all,admins,owners,all_users',
    //         'action_url' => 'nullable|url|max:255',
    //         'send_push' => 'boolean',
    //         'save_database' => 'boolean',
    //     ]);

    //     try {
    //         $sentCount = 0;
    //         $pushSentCount = 0;

    //         // Determine recipients
    //         $users = $this->getRecipients($validated['recipients']);

    //         if (empty($users)) {
    //             return back()->withErrors(['recipients' => 'No users found for selected recipients']);
    //         }

    //         // Save to database if requested
    //         if ($validated['save_database'] ?? true) {
    //             foreach ($users as $user) {
    //                 $notification = Notification::create([
    //                     'id' => (string) \Illuminate\Support\Str::uuid(),
    //                     'type' => 'admin_notification',
    //                     'notifiable_type' => get_class($user),
    //                     'notifiable_id' => $user->id,
    //                     'data' => [
    //                         'title' => $validated['title'],
    //                         'message' => $validated['message'],
    //                         'action_url' => $validated['action_url'] ?? null,
    //                         'sender' => 'Admin',
    //                     ],
    //                     'read_at' => null,
    //                 ]);
    //                 $sentCount++;
    //             }
    //         }

    //         // Send push notifications if requested
    //         if ($validated['send_push'] ?? true) {
    //             $pushSentCount = $this->pushService->sendToUsers(
    //                 $users,
    //                 $validated['title'],
    //                 $validated['message'],
    //                 null,
    //                 $validated['action_url'] ?? '/'
    //             );
    //         }

    //         Log::info('Admin notification sent', [
    //             'title' => $validated['title'],
    //             'recipients' => $validated['recipients'],
    //             'user_count' => count($users),
    //             'database_saved' => $sentCount,
    //             'push_sent' => $pushSentCount,
    //         ]);

    //         return redirect()->route('admin.notifications.create')
    //             ->with('success', "Notification sent to " . count($users) . " users! ($pushSentCount push notifications sent)");

    //     } catch (\Exception $e) {
    //         Log::error('Admin notification error', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return back()->withErrors(['error' => 'Failed to send notification: ' . $e->getMessage()]);
    //     }
    // }

    /**
     * Get recipients based on selection
     */
    protected function getRecipients(array $recipients, array $customIds = []): array
    {
        $users = collect();

        foreach ($recipients as $recipient) {
            switch ($recipient) {
                case 'all':
                case 'all_users':
                    $users = $users->merge(User::all());
                    break;
                case 'admins':
                    $users = $users->merge(User::whereIn('role', ['admin', 'super_admin'])->get());
                    break;
                case 'owners':
                    $users = $users->merge(User::where('role', 'owner')->get());
                    break;
                case 'custom':
                    if (!empty($customIds)) {
                        $users = $users->merge(User::whereIn('id', $customIds)->get());
                    }
                    break;
            }
        }

        return $users->unique('id')->values()->all();
    }

    /**
     * Show the notification scheduling page (with list of scheduled)
     */
    public function scheduled()
    {
        $scheduled = ScheduledNotification::with('creator:id,name')
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Notifications/Scheduled', [
            'scheduled' => $scheduled,
            'stats' => [
                'pending' => ScheduledNotification::pending()->count(),
                'sent' => ScheduledNotification::where('status', ScheduledNotification::STATUS_SENT)->count(),
                'failed' => ScheduledNotification::where('status', ScheduledNotification::STATUS_FAILED)->count(),
                'cancelled' => ScheduledNotification::where('status', ScheduledNotification::STATUS_CANCELLED)->count(),
            ],
        ]);
    }

    /**
     * Update send() to support scheduling
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'string|in:all,admins,owners,all_users,custom',
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'integer|exists:users,id',
            'action_url' => 'nullable|url|max:255',
            'send_push' => 'boolean',
            'save_database' => 'boolean',
            'scheduled_at' => 'nullable|date',
        ]);

        // ============== SCHEDULED MODE ==============
        if (!empty($validated['scheduled_at'])) {
            // ✅ Normalize to UTC
            // Handles both "2026-09-10 20:06:00" (already UTC) and ISO strings
            $scheduledAt = Carbon::parse($validated['scheduled_at'], 'UTC');

            Log::info('Scheduling notification', [
                'input' => $validated['scheduled_at'],
                'parsed_utc' => $scheduledAt->toDateTimeString(),
                'app_timezone' => config('app.timezone'),
                'user_timezone_guess' => 'Browser local',
            ]);

            ScheduledNotification::create([
                'created_by' => auth()->id(),
                'title' => $validated['title'],
                'message' => $validated['message'],
                'action_url' => $validated['action_url'] ?? null,
                'type' => 'admin_notification',
                'recipient_type' => implode(',', $validated['recipients']),
                'recipient_ids' => $validated['recipient_ids'] ?? null,
                'recipient_count' => $this->estimateRecipientCount($validated),
                'send_push' => $validated['send_push'] ?? true,
                'save_database' => $validated['save_database'] ?? true,
                'scheduled_at' => $scheduledAt,
                'status' => ScheduledNotification::STATUS_PENDING,
            ]);

            Log::info('Notification scheduled', [
                'title' => $validated['title'],
                'scheduled_at' => $scheduledAt->toDateTimeString(),
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.notifications.scheduled')
                ->with('success', "Notification scheduled for {$scheduledAt->format('M d, Y \a\t H:i')}!");
        }

        // ============== IMMEDIATE MODE (existing logic) ==============
        try {
            $sentCount = 0;
            $pushSentCount = 0;

            $users = $this->getRecipients($validated['recipients'], $validated['recipient_ids'] ?? []);

            if (empty($users)) {
                return back()->withErrors(['recipients' => 'No users found for selected recipients']);
            }

            // Save to database
            if ($validated['save_database'] ?? true) {
                foreach ($users as $user) {
                    \App\Models\Notification::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'type' => 'admin_notification',
                        'notifiable_type' => get_class($user),
                        'notifiable_id' => $user->id,
                        'data' => [
                            'title' => $validated['title'],
                            'message' => $validated['message'],
                            'action_url' => $validated['action_url'] ?? null,
                            'sender' => 'Admin',
                        ],
                        'read_at' => null,
                    ]);
                    $sentCount++;
                }
            }

            // Send push
            if ($validated['send_push'] ?? true) {
                $pushSentCount = $this->pushService->sendToUsers(
                    $users,
                    $validated['title'],
                    $validated['message'],
                    null,
                    $validated['action_url'] ?? '/'
                );
            }

            return redirect()->route('admin.notifications.create')
                ->with('success', "Notification sent to " . count($users) . " users! ($pushSentCount push notifications sent)");

        } catch (\Exception $e) {
            Log::error('Admin notification error', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancelScheduled($id)
    {
        $scheduled = ScheduledNotification::findOrFail($id);

        if (!$scheduled->canBeCancelled()) {
            return back()->with('error', 'This notification cannot be cancelled.');
        }

        $scheduled->update([
            'status' => ScheduledNotification::STATUS_CANCELLED,
        ]);

        return back()->with('success', 'Scheduled notification cancelled.');
    }

    /**
     * Delete a scheduled notification
     */
    public function destroyScheduled($id)
    {
        $scheduled = ScheduledNotification::findOrFail($id);
        $scheduled->delete();

        return back()->with('success', 'Scheduled notification deleted.');
    }

    /**
     * Reschedule a pending notification
     */
    public function reschedule(Request $request, $id)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $scheduled = ScheduledNotification::findOrFail($id);

        if (!$scheduled->canBeEdited()) {
            return back()->with('error', 'Only pending notifications can be rescheduled.');
        }

        $scheduled->update([
            'scheduled_at' => Carbon::parse($validated['scheduled_at']),
        ]);

        return back()->with('success', 'Notification rescheduled.');
    }

    /**
     * Send a scheduled notification now
     */
    public function sendNow($id)
    {
        $scheduled = ScheduledNotification::findOrFail($id);

        if ($scheduled->status === ScheduledNotification::STATUS_SENT) {
            return back()->with('error', 'This notification has already been sent.');
        }

        $scheduled->update([
            'status' => ScheduledNotification::STATUS_PENDING,
            'scheduled_at' => now(),
        ]);

        // Process immediately
        $this->processScheduledNotification($scheduled);

        return back()->with('success', 'Notification sent!');
    }

    /**
     * Internal: estimate recipient count (for display)
     */
    protected function estimateRecipientCount(array $validated): int
    {
        $recipients = $validated['recipients'];
        $customIds = $validated['recipient_ids'] ?? [];

        if (in_array('custom', $recipients) && !empty($customIds)) {
            return count($customIds);
        }

        $count = 0;
        foreach ($recipients as $r) {
            switch ($r) {
                case 'all':
                case 'all_users':
                    $count = \App\Models\User::count();
                    break;
                case 'admins':
                    $count += \App\Models\User::whereIn('role', ['admin', 'super_admin'])->count();
                    break;
                case 'owners':
                    $count += \App\Models\User::where('role', 'owner')->count();
                    break;
            }
        }

        return $count;
    }

    /**
     * Process a due scheduled notification (called by scheduler)
     */
    public function processScheduledNotification(ScheduledNotification $scheduled): bool
    {
        try {
            $scheduled->update(['status' => ScheduledNotification::STATUS_PROCESSING]);

            $recipients = explode(',', $scheduled->recipient_type);
            $customIds = $scheduled->recipient_ids ?? [];

            $users = $this->getRecipients($recipients, $customIds);

            if (empty($users)) {
                $scheduled->update([
                    'status' => ScheduledNotification::STATUS_FAILED,
                    'failure_reason' => 'No users found for selected recipients',
                ]);
                return false;
            }

            $sentCount = 0;
            $pushSentCount = 0;

            // Save to database
            if ($scheduled->save_database) {
                foreach ($users as $user) {
                    \App\Models\Notification::create([
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'type' => $scheduled->type,
                        'notifiable_type' => get_class($user),
                        'notifiable_id' => $user->id,
                        'data' => [
                            'title' => $scheduled->title,
                            'message' => $scheduled->message,
                            'action_url' => $scheduled->action_url,
                            'sender' => 'Admin (Scheduled)',
                        ],
                        'read_at' => null,
                    ]);
                    $sentCount++;
                }
            }

            // Send push
            if ($scheduled->send_push) {
                $pushSentCount = $this->pushService->sendToUsers(
                    $users,
                    $scheduled->title,
                    $scheduled->message,
                    null,
                    $scheduled->action_url ?? '/'
                );
            }

            $scheduled->update([
                'status' => ScheduledNotification::STATUS_SENT,
                'sent_at' => now(),
                'sent_count' => $sentCount,
                'push_sent_count' => $pushSentCount,
                'recipient_count' => count($users),
                'failure_reason' => null,
            ]);

            Log::info('Scheduled notification sent', [
                'scheduled_id' => $scheduled->id,
                'users' => count($users),
                'push_sent' => $pushSentCount,
            ]);

            return true;

        } catch (\Exception $e) {
            $scheduled->update([
                'status' => ScheduledNotification::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Scheduled notification failed', [
                'scheduled_id' => $scheduled->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}