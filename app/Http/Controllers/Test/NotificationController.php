<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public $indexView = 'pages.notifications.index';
    /**
     * Display a listing of the notifications.
     */
    public function index()
    {
        // Здесь будет логика получения уведомлений из базы данных
        $notifications = $this->getNotifications();

        // if (count($notifications) === 0) {
        //     return view('notifications.empty');
        // }

        return view($this->indexView, compact('notifications'));
    }

    /**
     * Get notifications from database.
     */
    private function getNotifications()
    {
        // Мок-данные для примера
        return [
            [
                'id' => 1,
                'read' => false,
                'date' => '08.05.23 (16:45)',
                'title' => 'Приветствуем на Pay2House!',
                'content' => 'Для уведомлений будет использоваться e-mail, указанный в Вашем профиле. (<strong>vadfart@gmail.com</strong>)',
                'hasButton' => true
            ],
            [
                'id' => 2,
                'read' => true,
                'date' => '08.05.23 (16:45)',
                'title' => 'Приветствуем на Pay2House!',
                'content' => 'Для уведомлений будет использоваться e-mail, указанный в Вашем профиле. (<strong>vadfart@gmail.com</strong>)'
            ]
        ];
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        // Логика для отметки всех уведомлений как прочитанные

        return redirect()->route('notifications.index');
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        // Логика для отметки конкретного уведомления как прочитанное

        return redirect()->route('notifications.index');
    }
}
