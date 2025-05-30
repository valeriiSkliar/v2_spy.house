<?php

namespace App\Listeners\Notifications;

use Illuminate\Support\Facades\Log;

/**
 * Слушатель для аналитики и метрик
 */
class NotificationMetricsListener
{
    /**
     * Обработка всех событий пользователя для сбора метрик
     */
    public function handle($event): void
    {
        $eventName = class_basename($event);
        $userId = $event->user->id ?? null;

        Log::info('User event occurred', [
            'event' => $eventName,
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
            'metadata' => $event->metadata ?? []
        ]);

        // Здесь можно добавить отправку метрик в внешние системы:
        // - Google Analytics
        // - Mixpanel
        // - Custom metrics service
    }
}
