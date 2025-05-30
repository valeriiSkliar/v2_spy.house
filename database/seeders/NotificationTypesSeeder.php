<?php

namespace Database\Seeders;

use App\Enums\Frontend\NotificationType as NotificationTypeEnum;
use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Массив с категориями для каждого типа уведомления
        $categories = [
            // Системные уведомления
            NotificationTypeEnum::SYSTEM_SETTINGS_UPDATED->value => 'system',
            NotificationTypeEnum::SYSTEM_MAINTENANCE->value => 'system',
            NotificationTypeEnum::SYSTEM_UPDATE->value => 'system',

            // Уведомления аккаунта
            NotificationTypeEnum::WELCOME->value => 'account',
            NotificationTypeEnum::ACCOUNT_VERIFIED->value => 'account',
            NotificationTypeEnum::EMAIL_VERIFIED->value => 'account',
            NotificationTypeEnum::EMAIL_VERIFICATION_REQUEST->value => 'account',
            NotificationTypeEnum::EMAIL_UPDATED->value => 'account',
            NotificationTypeEnum::PASSWORD_CHANGED->value => 'account',
            NotificationTypeEnum::PROFILE_UPDATED->value => 'account',

            // Комментарии
            NotificationTypeEnum::COMMENT_POSTED->value => 'content',
            NotificationTypeEnum::COMMENT_APPROVED->value => 'content',
            NotificationTypeEnum::COMMENT_REPLY->value => 'content',

            // Уведомления о контенте
            NotificationTypeEnum::CONTENT_CREATED->value => 'content',
            NotificationTypeEnum::CONTENT_UPDATED->value => 'content',
            NotificationTypeEnum::CONTENT_SHARED->value => 'content',

            // Уведомления о загрузке сайтов
            NotificationTypeEnum::WEBSITE_DOWNLOAD_STARTED->value => 'landings',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_COMPLETED->value => 'landings',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_FAILED->value => 'landings',

            // Уведомления об оплате
            NotificationTypeEnum::PAYMENT_RECEIVED->value => 'billing',
            NotificationTypeEnum::PAYMENT_FAILED->value => 'billing',
            NotificationTypeEnum::SUBSCRIPTION_RENEWED->value => 'billing',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRED->value => 'billing',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRING_SOON->value => 'billing',

            // Безопасность
            NotificationTypeEnum::SECURITY_ALERT->value => 'security',
            NotificationTypeEnum::LOGIN_ATTEMPT->value => 'security',
            NotificationTypeEnum::PASSWORD_RESET->value => 'security',
        ];

        // Массив с названиями для каждого типа уведомления
        $names = [
            // Системные уведомления
            NotificationTypeEnum::SYSTEM_SETTINGS_UPDATED->value => 'System settings updated',
            NotificationTypeEnum::SYSTEM_MAINTENANCE->value => 'System maintenance scheduled',
            NotificationTypeEnum::SYSTEM_UPDATE->value => 'System updated with new features',

            // Уведомления аккаунта
            NotificationTypeEnum::WELCOME->value => 'Welcome to the platform',
            NotificationTypeEnum::ACCOUNT_VERIFIED->value => 'Your account has been verified',
            NotificationTypeEnum::EMAIL_VERIFIED->value => 'Your email has been verified',
            NotificationTypeEnum::EMAIL_VERIFICATION_REQUEST->value => 'Email verification request',
            NotificationTypeEnum::EMAIL_UPDATED->value => 'Your email has been updated',
            NotificationTypeEnum::PASSWORD_CHANGED->value => 'Your password has been changed',
            NotificationTypeEnum::PROFILE_UPDATED->value => 'Your profile has been updated',

            // Комментарии
            NotificationTypeEnum::COMMENT_POSTED->value => 'Your comment has been posted',
            NotificationTypeEnum::COMMENT_APPROVED->value => 'Your comment has been approved',
            NotificationTypeEnum::COMMENT_REPLY->value => 'Someone replied to your comment',

            // Уведомления о контенте
            NotificationTypeEnum::CONTENT_CREATED->value => 'New content created',
            NotificationTypeEnum::CONTENT_UPDATED->value => 'Content updated',
            NotificationTypeEnum::CONTENT_SHARED->value => 'Content shared',

            // Уведомления о загрузке сайтов
            NotificationTypeEnum::WEBSITE_DOWNLOAD_STARTED->value => 'Website download started',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_COMPLETED->value => 'Website download completed',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_FAILED->value => 'Website download failed',

            // Уведомления об оплате
            NotificationTypeEnum::PAYMENT_RECEIVED->value => 'Payment received',
            NotificationTypeEnum::PAYMENT_FAILED->value => 'Payment failed',
            NotificationTypeEnum::SUBSCRIPTION_RENEWED->value => 'Subscription renewed',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRED->value => 'Subscription expired',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRING_SOON->value => 'Subscription expiring soon',

            // Безопасность
            NotificationTypeEnum::SECURITY_ALERT->value => 'Security alert',
            NotificationTypeEnum::LOGIN_ATTEMPT->value => 'New login attempt detected',
            NotificationTypeEnum::PASSWORD_RESET->value => 'Password reset requested',
        ];

        // Массив с описаниями для каждого типа уведомления
        $descriptions = [
            // Системные уведомления
            NotificationTypeEnum::SYSTEM_SETTINGS_UPDATED->value => 'This notification is sent when system settings are updated by administrators.',
            NotificationTypeEnum::SYSTEM_MAINTENANCE->value => 'This notification is sent before scheduled system maintenance.',
            NotificationTypeEnum::SYSTEM_UPDATE->value => 'This notification is sent when the system is updated with new features or improvements.',

            // Уведомления аккаунта
            NotificationTypeEnum::WELCOME->value => 'Welcome message sent to new users after registration.',
            NotificationTypeEnum::ACCOUNT_VERIFIED->value => 'Notification sent when a user account is verified.',
            NotificationTypeEnum::EMAIL_VERIFIED->value => 'Notification sent when a user email is verified.',
            NotificationTypeEnum::EMAIL_VERIFICATION_REQUEST->value => 'Notification sent when a user requests email verification.',
            NotificationTypeEnum::EMAIL_UPDATED->value => 'Notification sent when a user email is updated.',
            NotificationTypeEnum::PASSWORD_CHANGED->value => 'Notification sent when a user password is changed.',
            NotificationTypeEnum::PROFILE_UPDATED->value => 'Notification sent when a user profile is updated.',

            // Комментарии
            NotificationTypeEnum::COMMENT_POSTED->value => 'Notification sent when a user posts a comment.',
            NotificationTypeEnum::COMMENT_APPROVED->value => 'Notification sent when a user comment is approved by moderators.',
            NotificationTypeEnum::COMMENT_REPLY->value => 'Notification sent when someone replies to a user comment.',

            // Уведомления о контенте
            NotificationTypeEnum::CONTENT_CREATED->value => 'Notification sent when new content is created.',
            NotificationTypeEnum::CONTENT_UPDATED->value => 'Notification sent when content is updated.',
            NotificationTypeEnum::CONTENT_SHARED->value => 'Notification sent when content is shared.',

            // Уведомления о загрузке сайтов
            NotificationTypeEnum::WEBSITE_DOWNLOAD_STARTED->value => 'Notification sent when a website download process starts.',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_COMPLETED->value => 'Notification sent when a website download process completes successfully.',
            NotificationTypeEnum::WEBSITE_DOWNLOAD_FAILED->value => 'Notification sent when a website download process fails.',

            // Уведомления об оплате
            NotificationTypeEnum::PAYMENT_RECEIVED->value => 'Notification sent when a payment is received.',
            NotificationTypeEnum::PAYMENT_FAILED->value => 'Notification sent when a payment fails.',
            NotificationTypeEnum::SUBSCRIPTION_RENEWED->value => 'Notification sent when a subscription is renewed.',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRED->value => 'Notification sent when a subscription expires.',
            NotificationTypeEnum::SUBSCRIPTION_EXPIRING_SOON->value => 'Notification sent when a subscription is about to expire.',

            // Безопасность
            NotificationTypeEnum::SECURITY_ALERT->value => 'Notification sent for security alerts.',
            NotificationTypeEnum::LOGIN_ATTEMPT->value => 'Notification sent when there is a login attempt from a new device.',
            NotificationTypeEnum::PASSWORD_RESET->value => 'Notification sent when a password reset is requested.',
        ];

        // Массив с каналами оповещений по умолчанию для каждого типа уведомления
        $defaultChannels = [
            // Системные уведомления - важные системные уведомления отправляются по всем каналам
            NotificationTypeEnum::SYSTEM_SETTINGS_UPDATED->value => ['mail', 'database'],
            NotificationTypeEnum::SYSTEM_MAINTENANCE->value => ['mail', 'database'],
            NotificationTypeEnum::SYSTEM_UPDATE->value => ['mail', 'database'],

            // Уведомления аккаунта - информация об аккаунте важна, поэтому отправляется по email
            NotificationTypeEnum::WELCOME->value => ['mail', 'database'],
            NotificationTypeEnum::ACCOUNT_VERIFIED->value => ['mail', 'database'],
            NotificationTypeEnum::EMAIL_VERIFIED->value => ['mail', 'database'],
            NotificationTypeEnum::EMAIL_VERIFICATION_REQUEST->value => ['mail', 'database'],
            NotificationTypeEnum::EMAIL_UPDATED->value => ['mail', 'database'],
            NotificationTypeEnum::PASSWORD_CHANGED->value => ['mail', 'database'],
            NotificationTypeEnum::PROFILE_UPDATED->value => ['mail', 'database'],

            // Комментарии - менее критичные уведомления могут быть только в интерфейсе
            NotificationTypeEnum::COMMENT_POSTED->value => ['database'],
            NotificationTypeEnum::COMMENT_APPROVED->value => ['database'],
            NotificationTypeEnum::COMMENT_REPLY->value => ['mail', 'database'],

            // Уведомления о контенте - только в интерфейсе
            NotificationTypeEnum::CONTENT_CREATED->value => ['database'],
            NotificationTypeEnum::CONTENT_UPDATED->value => ['database'],
            NotificationTypeEnum::CONTENT_SHARED->value => ['database'],

            // Уведомления о загрузке сайтов - важно уведомлять о завершении загрузок
            NotificationTypeEnum::WEBSITE_DOWNLOAD_STARTED->value => ['database'],
            NotificationTypeEnum::WEBSITE_DOWNLOAD_COMPLETED->value => ['mail', 'database'],
            NotificationTypeEnum::WEBSITE_DOWNLOAD_FAILED->value => ['mail', 'database'],

            // Уведомления об оплате - все уведомления о платежах важны
            NotificationTypeEnum::PAYMENT_RECEIVED->value => ['mail', 'database'],
            NotificationTypeEnum::PAYMENT_FAILED->value => ['mail', 'database'],
            NotificationTypeEnum::SUBSCRIPTION_RENEWED->value => ['mail', 'database'],
            NotificationTypeEnum::SUBSCRIPTION_EXPIRED->value => ['mail', 'database'],
            NotificationTypeEnum::SUBSCRIPTION_EXPIRING_SOON->value => ['mail', 'database'],

            // Безопасность - критически важные уведомления отправляются по всем каналам
            NotificationTypeEnum::SECURITY_ALERT->value => ['mail', 'database'],
            NotificationTypeEnum::LOGIN_ATTEMPT->value => ['mail', 'database'],
            NotificationTypeEnum::PASSWORD_RESET->value => ['mail', 'database'],
        ];

        // Массив с флагами настраиваемости для каждого типа уведомления
        $isUserConfigurable = [
            // Системные уведомления - некоторые системные уведомления нельзя отключить
            NotificationTypeEnum::SYSTEM_SETTINGS_UPDATED->value => true,
            NotificationTypeEnum::SYSTEM_MAINTENANCE->value => false,
            NotificationTypeEnum::SYSTEM_UPDATE->value => false,

            // Уведомления аккаунта - критичные уведомления о безопасности нельзя отключить
            NotificationTypeEnum::WELCOME->value => true,
            NotificationTypeEnum::ACCOUNT_VERIFIED->value => true,
            NotificationTypeEnum::EMAIL_VERIFIED->value => false,
            NotificationTypeEnum::EMAIL_VERIFICATION_REQUEST->value => false,
            NotificationTypeEnum::EMAIL_UPDATED->value => false,
            NotificationTypeEnum::PASSWORD_CHANGED->value => false,
            NotificationTypeEnum::PROFILE_UPDATED->value => true,

            // Комментарии - все настраиваемые
            NotificationTypeEnum::COMMENT_POSTED->value => true,
            NotificationTypeEnum::COMMENT_APPROVED->value => true,
            NotificationTypeEnum::COMMENT_REPLY->value => true,

            // Уведомления о контенте - все настраиваемые
            NotificationTypeEnum::CONTENT_CREATED->value => true,
            NotificationTypeEnum::CONTENT_UPDATED->value => true,
            NotificationTypeEnum::CONTENT_SHARED->value => true,

            // Уведомления о загрузке сайтов - все настраиваемые
            NotificationTypeEnum::WEBSITE_DOWNLOAD_STARTED->value => true,
            NotificationTypeEnum::WEBSITE_DOWNLOAD_COMPLETED->value => true,
            NotificationTypeEnum::WEBSITE_DOWNLOAD_FAILED->value => true,

            // Уведомления об оплате - информацию о платежах нельзя отключить
            NotificationTypeEnum::PAYMENT_RECEIVED->value => false,
            NotificationTypeEnum::PAYMENT_FAILED->value => false,
            NotificationTypeEnum::SUBSCRIPTION_RENEWED->value => false,
            NotificationTypeEnum::SUBSCRIPTION_EXPIRED->value => false,
            NotificationTypeEnum::SUBSCRIPTION_EXPIRING_SOON->value => true,

            // Безопасность - все критические уведомления безопасности нельзя отключить
            NotificationTypeEnum::SECURITY_ALERT->value => false,
            NotificationTypeEnum::LOGIN_ATTEMPT->value => false,
            NotificationTypeEnum::PASSWORD_RESET->value => false,
        ];

        // Создаем записи для всех типов уведомлений из enum
        foreach (NotificationTypeEnum::cases() as $case) {
            NotificationType::updateOrCreate(
                ['key' => $case->value],
                [
                    'name' => $names[$case->value],
                    'description' => $descriptions[$case->value],
                    'default_channels' => $defaultChannels[$case->value],
                    'is_user_configurable' => $isUserConfigurable[$case->value],
                    'category' => $categories[$case->value],
                ]
            );
        }
    }
}
