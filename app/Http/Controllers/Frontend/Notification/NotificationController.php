<?php

namespace App\Http\Controllers\Frontend\Notification;

use App\Http\Controllers\Controller;
use App\Models\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public $indexView = 'pages.notifications.index';
    public $emptyView = 'pages.notifications.empty';

    /**
     * Display a listing of the notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $this->getNotifications($user);
        $unreadCount = $user->unreadNotifications->count();

        if (count($notifications) === 0) {
            return view($this->emptyView);
        }

        return view($this->indexView, compact('notifications', 'unreadCount'));
    }

    /**
     * Get notifications from database.
     */
    private function getNotifications($user)
    {
        $perPage = request('per_page', 12);
        $notifications = [];
        $dbNotifications = $user->notifications()->paginate($perPage);

        foreach ($dbNotifications as $notification) {
            $data = $notification->data;
            $notificationType = NotificationType::where('key', $data['type'] ?? '')->first();

            $notifications[] = [
                'id' => $notification->id,
                'read' => !is_null($notification->read_at),
                'date' => $notification->created_at->format('d.m.y (H:i)'),
                'title' => $data['title'] ?? ($notificationType ? $notificationType->name : 'Notification'),
                'content' => $data['message'] ?? ($notificationType ? $notificationType->description : 'You have a new notification'),
                'icon' => $data['icon'] ?? 'bell',
                'hasButton' => !is_null($notification->read_at) ? false : true,
                'data' => $data['data'] ?? [],
            ];
        }

        return [
            'items' => $notifications,
            'pagination' => $dbNotifications,
        ];
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }
}
