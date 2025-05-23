<?php

namespace App\Http\Controllers\Frontend\Notification;

use Illuminate\Http\Request;

class NotificationController extends BaseNotificationController
{
    public $indexView = 'pages.notifications.index';

    public $emptyView = 'pages.notifications.empty';

    /**
     * Display a listing of the notifications.
     */
    public function index(Request $request)
    {
        return $this->renderIndexView($request);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json([
                'success' => false,
                'message' => __('notifications.not_found_or_already_read'),
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'id' => $id,
            'read' => true,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $unreadNotifications = $request->user()->unreadNotifications;

        if ($unreadNotifications->isNotEmpty()) {
            $unreadNotifications->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return redirect()->back()->with('status', __('notifications.all_marked_as_read'));
    }

    /**
     * Get the count of unread notifications.
     */
    public function unreadCount(Request $request)
    {
        $unreadCount = $request->user()->unreadNotifications->count();

        return response()->json([
            'success' => true,
            'count' => $unreadCount,
        ]);
    }
}
