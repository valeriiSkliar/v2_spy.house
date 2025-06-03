<?php

namespace App\Http\Controllers\Frontend\Notification;

use App\Http\Controllers\FrontendController;
use App\Models\NotificationType;
use Illuminate\Http\Request;

class BaseNotificationController extends FrontendController
{
    public $emptyView = 'pages.notifications.empty';

    public $indexView = 'pages.notifications.index';

    protected function renderIndexView(Request $request)
    {
        $user = $request->user();
        $perPage = request('per_page', 12);
        $notificationsData = $this->getNotifications($user, $perPage);
        $unreadCount = $user->unreadNotifications->count();

        if (count($notificationsData['items']) === 0) {
            return view($this->emptyView);
        }

        // dd($notificationsData);
        return view($this->indexView, [
            'notifications' => $notificationsData,
            'unreadCount' => $unreadCount,
            'selectedPerPage' => $notificationsData['selectedPerPage'],
            'perPageOptions' => $notificationsData['perPageOptions'],
            'perPageOptionsPlaceholder' => __('notifications.per_page', ['count' => $notificationsData['perPage']]),
        ]);
    }

    /**
     * Get notifications from database.
     */
    protected function getNotifications($user, $perPage): array
    {
        $notifications = [];
        $dbNotifications = $user->notifications()->paginate($perPage);

        foreach ($dbNotifications as $notification) {
            $data = $notification->data;
            $notificationType = NotificationType::where('key', $data['type'] ?? '')->first();

            $notifications[] = [
                'id' => $notification->id,
                'read' => ! is_null($notification->read_at),
                'date' => $notification->created_at->format('d.m.y (H:i)'),
                'title' => $data['title'] ?? ($notificationType ? $notificationType->name : 'Notification'),
                'content' => $data['message'] ?? ($notificationType ? $notificationType->description : 'You have a new notification'),
                'icon' => $data['icon'] ?? 'bell',
                'data' => $data['data'] ?? [],
            ];
        }

        $perPageOptions = [
            ['label' => '12', 'value' => '12', 'order' => 1],
            ['label' => '24', 'value' => '24', 'order' => 2],
            ['label' => '48', 'value' => '48', 'order' => 3],
            ['label' => '96', 'value' => '96', 'order' => 4],
        ];

        $selectedPerPage = null;
        foreach ($perPageOptions as $option) {
            if ($option['value'] == $perPage) {
                $selectedPerPage = $option;
                break;
            }
        }
        if (! $selectedPerPage && count($perPageOptions) > 0) {
            $selectedPerPage = $perPageOptions[0];
        }

        return [
            'items' => $notifications,
            'perPage' => $perPage,
            'selectedPerPage' => $selectedPerPage,
            'perPageOptions' => $perPageOptions,
            'pagination' => $dbNotifications->appends(['per_page' => $perPage]),
        ];
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'id' => $notification->id,
            'read' => $notification->isRead(),
        ]);
    }
}
