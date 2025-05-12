<?php

namespace App\Enums\Frontend;

enum NotificationType: string
{
    // Системные уведомления
    case SYSTEM_SETTINGS_UPDATED = 'system_settings_updated'; // Обновление системных настроек
    case SYSTEM_MAINTENANCE = 'system_maintenance'; // Уведомление о техническом обслуживании
    case SYSTEM_UPDATE = 'system_update'; // Обновление системы

        // Уведомления аккаунта
    case WELCOME = 'welcome'; // Приветственное сообщение после регистрации
    case ACCOUNT_VERIFIED = 'account_verified'; // Аккаунт подтвержден
    case EMAIL_VERIFIED = 'email_verified'; // Email подтвержден
    case PASSWORD_CHANGED = 'password_changed'; // Пароль изменен
    case PROFILE_UPDATED = 'profile_updated'; // Профиль обновлен

        // Комментарии
    case COMMENT_POSTED = 'comment_posted'; // Комментарий опубликован
    case COMMENT_APPROVED = 'comment_approved'; // Комментарий одобрен
    case COMMENT_REPLY = 'comment_reply'; // Ответ на комментарий

        // Уведомления о контенте
    case CONTENT_CREATED = 'content_created'; // Создан новый контент
    case CONTENT_UPDATED = 'content_updated'; // Контент обновлен
    case CONTENT_SHARED = 'content_shared'; // Контент опубликован

        // Уведомления о загрузке сайтов
    case WEBSITE_DOWNLOAD_STARTED = 'website_download_started'; // Начата загрузка сайта
    case WEBSITE_DOWNLOAD_COMPLETED = 'website_download_completed'; // Загрузка сайта завершена
    case WEBSITE_DOWNLOAD_FAILED = 'website_download_failed'; // Ошибка загрузки сайта

        // Уведомления об оплате
    case PAYMENT_RECEIVED = 'payment_received'; // Оплата получена
    case PAYMENT_FAILED = 'payment_failed'; // Ошибка оплаты
    case SUBSCRIPTION_RENEWED = 'subscription_renewed'; // Подписка продлена
    case SUBSCRIPTION_EXPIRED = 'subscription_expired'; // Подписка истекла
    case SUBSCRIPTION_EXPIRING_SOON = 'subscription_expiring_soon'; // Подписка скоро истечет

        // Безопасность
    case SECURITY_ALERT = 'security_alert'; // Предупреждение безопасности
    case LOGIN_ATTEMPT = 'login_attempt'; // Попытка входа с нового устройства
    case PASSWORD_RESET = 'password_reset'; // Сброс пароля
}
