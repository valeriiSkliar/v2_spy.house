<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    /**
     * Логировать событие восстановления пароля
     */
    public static function logPasswordResetEvent(string $email, string $event, string $ip, array $context = [])
    {
        $logData = [
            'event' => $event,
            'email' => $email,
            'ip' => $ip,
            'timestamp' => now()->toDateTimeString(),
            'context' => $context
        ];

        Log::channel('security')->info('Password Reset Event', $logData);
    }

    /**
     * Проверить, совпадают ли IP-адреса запроса и доступа
     */
    public static function checkIpMismatch(string $email): ?array
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->latest('created_at')
            ->first();

        if (!$record || !$record->request_ip || !$record->access_ip) {
            return null;
        }

        if ($record->request_ip !== $record->access_ip) {
            return [
                'request_ip' => $record->request_ip,
                'access_ip' => $record->access_ip,
                'mismatch' => true
            ];
        }

        return null;
    }

    /**
     * Получить статистику подозрительной активности
     */
    public static function getSuspiciousActivity(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        // Множественные запросы с одного IP
        $multipleRequestsFromIp = DB::table('password_reset_tokens')
            ->select('request_ip', DB::raw('COUNT(DISTINCT email) as email_count'))
            ->whereNotNull('request_ip')
            ->where('created_at', '>=', $startDate)
            ->groupBy('request_ip')
            ->having('email_count', '>', 3)
            ->get();

        // IP несовпадения
        $ipMismatches = DB::table('password_reset_tokens')
            ->whereNotNull('request_ip')
            ->whereNotNull('access_ip')
            ->whereRaw('request_ip != access_ip')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Неиспользованные токены
        $unusedTokens = DB::table('password_reset_tokens')
            ->whereNull('used_at')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', now()->subHours(2)) // Токены старше 2 часов
            ->count();

        return [
            'multiple_requests_from_ip' => $multipleRequestsFromIp,
            'ip_mismatches' => $ipMismatches,
            'unused_tokens' => $unusedTokens,
            'period_days' => $days
        ];
    }

    /**
     * Очистить старые записи
     */
    public static function cleanupOldRecords(int $daysToKeep = 30): int
    {
        return DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }
}
