<?php

namespace Database\Seeders;

use App\Enums\Frontend\NotificationType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // Создаем системные уведомления
        foreach ($users as $user) {
            $user->notifications()->create([
                'id' => Str::uuid(),
                'type' => NotificationType::SYSTEM_UPDATE->value,
                'data' => [
                    'title' => 'System Update',
                    'message' => 'New features have been added to the platform',
                    'version' => '2.0.0'
                ],
            ]);

            $user->notifications()->create([
                'id' => Str::uuid(),
                'type' => NotificationType::WELCOME->value,
                'data' => [
                    'title' => 'Welcome to Our Platform',
                    'message' => 'Thank you for joining our platform!'
                ],
                'read_at' => now(),
            ]);
        }

        // Создаем уведомления о безопасности для первого пользователя
        $firstUser = $users->first();
        $firstUser->notifications()->create([
            'id' => Str::uuid(),
            'type' => NotificationType::SECURITY_ALERT->value,
            'data' => [
                'title' => 'Security Alert',
                'message' => 'New login detected from unknown device',
                'ip' => '192.168.1.1',
                'device' => 'Chrome on Windows'
            ],
        ]);

        // Создаем уведомления о платежах для первого пользователя
        $firstUser->notifications()->create([
            'id' => Str::uuid(),
            'type' => NotificationType::PAYMENT_RECEIVED->value,
            'data' => [
                'title' => 'Payment Received',
                'message' => 'Your payment of $99.99 has been received',
                'amount' => 99.99,
                'currency' => 'USD'
            ],
            'read_at' => now(),
        ]);
    }
}
