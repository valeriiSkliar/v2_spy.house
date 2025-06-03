<?php

namespace App\Listeners\Notifications;

use App\Events\User\AccountConfirmationCodeRequested;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для обработки запроса кода подтверждения
 */
class AccountConfirmationCodeRequestedListener
{
    /**
     * Обработка запроса кода подтверждения
     */
    public function handle(AccountConfirmationCodeRequested $event): void
    {
        Log::info('Processing AccountConfirmationCodeRequested event', [
            'user_id' => $event->user->id,
            'code_length' => strlen($event->code),
        ]);

        // Код отправки обрабатывается в VerifyEmailNotification
        // Здесь можем добавить дополнительную логику, например:
        // - Логирование попыток
        // - Отправка SMS как дополнительный канал
        // - Уведомление администраторов о частых запросах
    }
}
