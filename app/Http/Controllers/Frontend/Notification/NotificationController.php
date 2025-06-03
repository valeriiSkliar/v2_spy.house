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
     * AJAX endpoint for loading notifications.
     */
    public function ajaxList(Request $request)
    {
        if ($request->ajax()) {
            $user = $request->user();
            $perPage = request('per_page', 12);
            $notificationsData = $this->getNotifications($user, $perPage);
            $unreadCount = $user->unreadNotifications->count();

            $notificationsHtml = '';
            $paginationHtml = '';
            $hasPagination = false;

            if (count($notificationsData['items']) === 0) {
                $notificationsHtml = view('components.notifications.empty-notifications')->render();
            } else {
                $notificationsHtml = view('components.notifications.notifications-list', [
                    'notifications' => $notificationsData['items']
                ])->render();
            }

            if ($notificationsData['pagination']->hasPages()) {
                $paginationHtml = $notificationsData['pagination']->links()->render();
                $hasPagination = true;
            }

            return response()->json([
                'html' => $notificationsHtml,
                'pagination' => $paginationHtml,
                'hasPagination' => $hasPagination,
                'currentPage' => $notificationsData['pagination']->currentPage(),
                'totalPages' => $notificationsData['pagination']->lastPage(),
                'count' => count($notificationsData['items']),
                'unreadCount' => $unreadCount,
            ]);
        }

        return $this->index($request);
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
