<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class DebugRegistrationListener
{
    private static int $callCount = 0;
    private static array $processedEvents = [];

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        static::$callCount++;

        $user = $event->user;

        // Создаем уникальный хэш события для дедупликации
        $eventHash = md5(serialize([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'timestamp_second' => now()->format('Y-m-d H:i:s'), // округляем до секунды
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024) // округляем до МБ
        ]));

        $isDuplicate = isset(static::$processedEvents[$eventHash]);
        static::$processedEvents[$eventHash] = [
            'call_count' => static::$callCount,
            'timestamp' => now()->toDateTimeString()
        ];

        // Получаем stack trace для анализа места вызова
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        $callStack = [];
        $uniqueFiles = [];
        foreach ($stackTrace as $index => $trace) {
            if (isset($trace['file']) && isset($trace['line'])) {
                $file = basename($trace['file']);
                $uniqueFiles[] = $file;
                $callStack[] = sprintf(
                    "#%d %s:%d %s%s%s()",
                    $index,
                    $file,
                    $trace['line'],
                    $trace['class'] ?? '',
                    isset($trace['class']) ? '::' : '',
                    $trace['function'] ?? ''
                );
            }
        }

        // Анализируем источник вызова
        $sourceAnalysis = $this->analyzeEventSource($stackTrace);

        $debugData = [
            'event_call_count' => static::$callCount,
            'is_duplicate' => $isDuplicate,
            'event_hash' => $eventHash,
            'user_id' => $user->id ?? 'null',
            'user_email' => $user->email ?? 'null',
            'user_name' => $user->name ?? 'null',
            'user_created_at' => $user->created_at ?? 'null',
            'timestamp' => now()->toDateTimeString(),
            'memory_usage' => memory_get_usage(true) . ' bytes',
            'source_analysis' => $sourceAnalysis,
            'unique_files_in_stack' => array_unique($uniqueFiles),
            'call_stack' => $callStack,
        ];

        // Выводим в лог
        Log::channel('single')->info('🐛 DEBUG REGISTRATION EVENT', $debugData);

        // Дополнительно выводим в консоль если запущено через artisan
        if (app()->runningInConsole()) {
            echo "\n=== DEBUG REGISTRATION EVENT ===\n";
            echo "Call Count: " . static::$callCount . "\n";
            echo "Is Duplicate: " . ($isDuplicate ? 'YES' : 'NO') . "\n";
            echo "Event Hash: " . substr($eventHash, 0, 8) . "...\n";
            echo "User ID: " . ($user->id ?? 'null') . "\n";
            echo "User Email: " . ($user->email ?? 'null') . "\n";
            echo "User Name: " . ($user->name ?? 'null') . "\n";
            echo "Created At: " . ($user->created_at ?? 'null') . "\n";
            echo "Memory Usage: " . memory_get_usage(true) . " bytes\n";
            echo "Source: " . $sourceAnalysis . "\n";
            echo "--- CALL STACK (first 5) ---\n";
            foreach (array_slice($callStack, 0, 5) as $call) {
                echo $call . "\n";
            }
            echo "===============================\n\n";
        }
    }

    /**
     * Анализирует источник события по stack trace
     */
    private function analyzeEventSource(array $stackTrace): string
    {
        foreach ($stackTrace as $trace) {
            $file = $trace['file'] ?? '';
            $function = $trace['function'] ?? '';

            if (str_contains($file, 'TestRegistrationDebug.php')) {
                return 'test_command';
            }
            if (str_contains($file, 'RegisteredUserController.php')) {
                return 'registration_controller';
            }
            if (str_contains($file, 'tinker') || str_contains($file, 'ExecutionClosure.php')) {
                return 'tinker_console';
            }
            if (str_contains($file, 'ProcessUserRegistrationJob.php')) {
                return 'registration_job';
            }
            if (str_contains($function, 'sendEmailVerificationNotification')) {
                return 'email_verification';
            }
        }

        return 'unknown_source';
    }

    /**
     * Получить текущий счетчик вызовов
     */
    public static function getCallCount(): int
    {
        return static::$callCount;
    }

    /**
     * Сбросить счетчик вызовов
     */
    public static function resetCallCount(): void
    {
        static::$callCount = 0;
    }
}
