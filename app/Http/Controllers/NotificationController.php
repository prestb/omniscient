<?php
// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $this->notificationService->getNotifications($user, 20);
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Get unread count (for dropdown)
     */
    public function count(Request $request)
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $success = $this->notificationService->markAsRead($id, $user);

        if ($success) {
            return response()->json(['message' => 'Notification marked as read']);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $this->notificationService->markAllAsRead($user);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get notification dropdown items
     */
    public function dropdown(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->limit(10)->get();
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted']);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }
}