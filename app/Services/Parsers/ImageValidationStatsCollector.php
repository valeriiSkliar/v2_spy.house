<?php

namespace App\Services\Parsers;

use Illuminate\Support\Facades\Log;

/**
 * Сервис для сбора статистики валидации изображений
 * 
 * Собирает метрики по успешности валидации изображений,
 * типам ошибок и доменам для мониторинга качества данных
 *
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ImageValidationStatsCollector
{
    private array $stats = [
        'total_validations' => 0,
        'successful_validations' => 0,
        'failed_validations' => 0,
        'error_types' => [],
        'domains' => [],
        'response_times' => [],
        'content_types' => [],
        'status_codes' => []
    ];

    /**
     * Добавляет результат валидации в статистику
     *
     * @param array $validationResults Результаты валидации от CreativeImageValidator
     * @return void
     */
    public function addValidationResults(array $validationResults): void
    {
        foreach ($validationResults as $url => $result) {
            $this->stats['total_validations']++;

            if ($result['valid']) {
                $this->stats['successful_validations']++;
            } else {
                $this->stats['failed_validations']++;

                // Собираем типы ошибок
                if (!empty($result['error'])) {
                    $errorType = $this->categorizeError($result['error']);
                    $this->stats['error_types'][$errorType] =
                        ($this->stats['error_types'][$errorType] ?? 0) + 1;
                }
            }

            // Анализируем домены
            $domain = $this->extractDomain($url);
            if ($domain) {
                if (!isset($this->stats['domains'][$domain])) {
                    $this->stats['domains'][$domain] = [
                        'total' => 0,
                        'successful' => 0,
                        'failed' => 0
                    ];
                }

                $this->stats['domains'][$domain]['total']++;
                if ($result['valid']) {
                    $this->stats['domains'][$domain]['successful']++;
                } else {
                    $this->stats['domains'][$domain]['failed']++;
                }
            }

            // Собираем время отклика
            if (isset($result['response_time_ms'])) {
                $this->stats['response_times'][] = $result['response_time_ms'];
            }

            // Собираем Content-Type
            if (!empty($result['content_type'])) {
                $this->stats['content_types'][$result['content_type']] =
                    ($this->stats['content_types'][$result['content_type']] ?? 0) + 1;
            }

            // Собираем HTTP статус коды
            if (!empty($result['status_code'])) {
                $this->stats['status_codes'][$result['status_code']] =
                    ($this->stats['status_codes'][$result['status_code']] ?? 0) + 1;
            }
        }
    }

    /**
     * Возвращает собранную статистику
     *
     * @return array Полная статистика валидации
     */
    public function getStats(): array
    {
        $stats = $this->stats;

        // Вычисляем процентное соотношение
        if ($stats['total_validations'] > 0) {
            $stats['success_rate'] = round(
                ($stats['successful_validations'] / $stats['total_validations']) * 100,
                2
            );
            $stats['failure_rate'] = round(
                ($stats['failed_validations'] / $stats['total_validations']) * 100,
                2
            );
        } else {
            $stats['success_rate'] = 0;
            $stats['failure_rate'] = 0;
        }

        // Статистика времени отклика
        if (!empty($stats['response_times'])) {
            $stats['response_time_stats'] = [
                'avg' => round(array_sum($stats['response_times']) / count($stats['response_times']), 2),
                'min' => min($stats['response_times']),
                'max' => max($stats['response_times']),
                'count' => count($stats['response_times'])
            ];
        }

        // Сортируем домены по количеству запросов
        if (!empty($stats['domains'])) {
            uasort($stats['domains'], function ($a, $b) {
                return $b['total'] - $a['total'];
            });
        }

        // Сортируем ошибки по частоте
        if (!empty($stats['error_types'])) {
            arsort($stats['error_types']);
        }

        return $stats;
    }

    /**
     * Логирует статистику валидации
     *
     * @param string $level Уровень логирования (info, warning, error)
     * @return void
     */
    public function logStats(string $level = 'info'): void
    {
        $stats = $this->getStats();

        Log::log($level, 'Image validation statistics', [
            'total_validations' => $stats['total_validations'],
            'success_rate' => $stats['success_rate'] . '%',
            'failure_rate' => $stats['failure_rate'] . '%',
            'top_error_types' => array_slice($stats['error_types'], 0, 5, true),
            'top_domains' => array_slice($stats['domains'], 0, 10, true),
            'response_time_stats' => $stats['response_time_stats'] ?? null,
            'top_content_types' => array_slice($stats['content_types'], 0, 5, true),
            'status_code_distribution' => $stats['status_codes']
        ]);
    }

    /**
     * Сбрасывает собранную статистику
     *
     * @return void
     */
    public function reset(): void
    {
        $this->stats = [
            'total_validations' => 0,
            'successful_validations' => 0,
            'failed_validations' => 0,
            'error_types' => [],
            'domains' => [],
            'response_times' => [],
            'content_types' => [],
            'status_codes' => []
        ];
    }

    /**
     * Категоризирует ошибку по типу
     *
     * @param string $error Текст ошибки
     * @return string Категория ошибки
     */
    private function categorizeError(string $error): string
    {
        $error = strtolower($error);

        if (str_contains($error, 'dns') || str_contains($error, 'resolve host')) {
            return 'DNS_RESOLUTION_ERROR';
        }

        if (str_contains($error, 'timeout') || str_contains($error, 'timed out')) {
            return 'TIMEOUT_ERROR';
        }

        if (str_contains($error, 'connection') || str_contains($error, 'connect')) {
            return 'CONNECTION_ERROR';
        }

        if (str_contains($error, 'http error: 4')) {
            return 'HTTP_4XX_ERROR';
        }

        if (str_contains($error, 'http error: 5')) {
            return 'HTTP_5XX_ERROR';
        }

        if (str_contains($error, 'unsupported content type')) {
            return 'UNSUPPORTED_CONTENT_TYPE';
        }

        if (str_contains($error, 'invalid size')) {
            return 'INVALID_FILE_SIZE';
        }

        if (str_contains($error, 'invalid url')) {
            return 'INVALID_URL_FORMAT';
        }

        if (str_contains($error, 'empty url')) {
            return 'EMPTY_URL';
        }

        return 'OTHER_ERROR';
    }

    /**
     * Извлекает домен из URL
     *
     * @param string $url URL адрес
     * @return string|null Домен или null если не удалось извлечь
     */
    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Возвращает рекомендации на основе статистики
     *
     * @return array Массив рекомендаций
     */
    public function getRecommendations(): array
    {
        $stats = $this->getStats();
        $recommendations = [];

        // Анализ success rate
        if ($stats['success_rate'] < 50) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Низкий процент успешной валидации изображений (' . $stats['success_rate'] . '%). Рекомендуется проверить качество источника данных.'
            ];
        }

        // Анализ времени отклика
        if (isset($stats['response_time_stats']) && $stats['response_time_stats']['avg'] > 5000) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'Высокое среднее время отклика (' . $stats['response_time_stats']['avg'] . 'ms). Рекомендуется увеличить таймауты или оптимизировать запросы.'
            ];
        }

        // Анализ доменов с высоким процентом ошибок
        foreach ($stats['domains'] as $domain => $domainStats) {
            if ($domainStats['total'] >= 5) { // Минимум 5 запросов для анализа
                $domainFailureRate = ($domainStats['failed'] / $domainStats['total']) * 100;
                if ($domainFailureRate > 80) {
                    $recommendations[] = [
                        'type' => 'domain_issue',
                        'message' => "Домен {$domain} имеет высокий процент ошибок ({$domainFailureRate}%). Рекомендуется добавить в blacklist или исследовать проблему."
                    ];
                }
            }
        }

        return $recommendations;
    }
}
