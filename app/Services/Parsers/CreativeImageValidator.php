<?php

namespace App\Services\Parsers;

use App\Contracts\CreativeValidatorInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Валидатор изображений креативов
 * 
 * Выполняет проверку доступности изображений через HTTP запросы,
 * валидацию типов контента, размеров изображений и содержимого ответа.
 * Поддерживает асинхронную проверку и детальную диагностику.
 *
 * @package App\Services\Parsers
 * @author SeniorSoftwareEngineer
 * @version 1.1.0
 */
class CreativeImageValidator implements CreativeValidatorInterface
{
    /**
     * Поддерживаемые MIME типы изображений
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff'
    ];

    /**
     * Максимальный размер изображения в байтах (10MB)
     */
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;

    /**
     * Минимальный размер изображения в байтах (1KB)
     */
    private const MIN_IMAGE_SIZE = 1024;

    /**
     * Максимальный размер для загрузки содержимого (1MB)
     * Для проверки содержимого не загружаем файлы больше 1MB
     */
    private const MAX_CONTENT_SIZE_FOR_VALIDATION = 1024 * 1024;

    /**
     * Таймаут для HTTP запросов в секундах
     */
    private const HTTP_TIMEOUT = 15;

    /**
     * Паттерны для распознавания tracking URL (не изображений)
     */
    private const TRACKING_URL_PATTERNS = [
        '/track/',
        '/metrics/',
        '/pixel/',
        '/beacon/',
        '/impression/',
        '/click/',
        '/analytics/',
        '/event/',
        '/counter/',
        '/stat',
        '?event=',
        '&event=',
        'status.*expired',
        'status.*item',
        // Дополнительные паттерны для ad tracking
        '/dsp/',
        '/icm',
        '?aid=',
        '&aid=',
        '?mid=',
        '&mid=',
        '?sid=',
        '&sid=',
        '?subid=',
        '&subid=',
        '&t=',
        '?t=',
        '/ph/',
        '/redirect',
        '/redir',
        'click.php',
        'view.php'
    ];

    /**
     * Blacklist доменов с высоким процентом ошибок
     */
    private const PROBLEMATIC_DOMAINS = [
        'mcufwk.xyz',
        'yimufc.xyz',
        'imcdn.co',
        'eu.histi.co',
        'annshy.click',
        'inusse.click',
        'cleiln.click',
        'ukeine.click',
        // Дополнительные проблемные домены
        'adnxs.com',
        'adsystem.com',
        'doubleclick.net',
        'googleadservices.com',
        'googlesyndication.com',
        'adscdn.com',
        'adform.net',
        'adsense.com'
    ];

    /**
     * Максимальное количество редиректов
     */
    private const MAX_REDIRECTS = 3;

    /**
     * Сигнатуры изображений для проверки содержимого
     */
    private const IMAGE_SIGNATURES = [
        'jpeg' => ["\xFF\xD8\xFF"],
        'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'gif' => ["GIF87a", "GIF89a"],
        'webp' => ["RIFF"],
        'bmp' => ["BM"],
        'tiff' => ["\x49\x49\x2A\x00", "\x4D\x4D\x00\x2A"],
        'svg' => ["<svg", "<?xml"]
    ];

    /**
     * Валидирует массив URL изображений
     *
     * @param array $imageUrls Массив URL изображений
     * @return array Результат валидации для каждого URL
     */
    public function validateImages(array $imageUrls): array
    {
        $results = [];

        foreach ($imageUrls as $url) {
            if ($url === null || $url === '' || empty($url)) {
                $urlKey = $url === null ? 'null' : (string) $url;
                $results[$urlKey] = [
                    'valid' => false,
                    'error' => 'Empty URL provided',
                    'accessible' => false
                ];
                continue;
            }

            $results[$url] = $this->getImageDetails($url);
        }

        return $results;
    }

    /**
     * Проверяет доступность одного изображения
     *
     * @param string $imageUrl URL изображения
     * @return bool true если изображение доступно
     */
    public function isImageAccessible(string $imageUrl): bool
    {
        if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Предварительные проверки
        $urlParts = parse_url($imageUrl);
        $domain = $urlParts['host'] ?? '';

        // Проверка blacklist доменов
        if ($this->isProblematicDomain($domain)) {
            return false;
        }

        // Проверка tracking URL
        if ($this->isTrackingUrl($imageUrl)) {
            return false;
        }

        // DNS проверка
        if (!$this->isDomainResolvable($domain)) {
            return false;
        }

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => self::MAX_REDIRECTS,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https']
                    ],
                    'verify' => false, // Для работы с самоподписанными сертификатами
                ])
                ->head($imageUrl);

            if (!$response->successful()) {
                Log::debug("Image not accessible: HTTP {$response->status()}", [
                    'url' => $imageUrl,
                    'status' => $response->status()
                ]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::warning("Failed to check image accessibility", [
                'url' => $imageUrl,
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Возвращает детальную информацию о изображении
     *
     * @param string $imageUrl URL изображения
     * @return array Подробная информация о валидации
     */
    public function getImageDetails(string $imageUrl): array
    {
        $result = [
            'url' => $imageUrl,
            'valid' => false,
            'accessible' => false,
            'content_type' => null,
            'content_length' => null,
            'status_code' => null,
            'error' => null,
            'size_valid' => false,
            'type_valid' => false,
            'content_valid' => false,
            'response_time_ms' => null,
            'validation_method' => 'head_only'
        ];

        // Базовая валидация URL
        if (empty($imageUrl)) {
            $result['error'] = 'Empty URL provided';
            return $result;
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $result['error'] = 'Invalid URL format';
            return $result;
        }

        // Проверка схемы URL
        $urlParts = parse_url($imageUrl);
        if (!in_array($urlParts['scheme'] ?? '', ['http', 'https'])) {
            $result['error'] = 'Only HTTP/HTTPS protocols supported';
            return $result;
        }

        // Проверка на tracking URL (не изображения)
        if ($this->isTrackingUrl($imageUrl)) {
            $result['error'] = 'URL appears to be a tracking pixel, not an image';
            Log::debug("Tracking URL detected and rejected", [
                'url' => $imageUrl,
                'domain' => $urlParts['host'] ?? ''
            ]);
            return $result;
        }

        // Проверка проблемных доменов
        $domain = $urlParts['host'] ?? '';
        if ($this->isProblematicDomain($domain)) {
            $result['error'] = "Domain '{$domain}' is blacklisted due to high failure rate";
            return $result;
        }

        // КРИТИЧЕСКАЯ ПРОВЕРКА: DNS резолюция домена
        if (!$this->isDomainResolvable($domain)) {
            $result['error'] = "Domain '{$domain}' cannot be resolved (DNS_PROBE_FINISHED_NXDOMAIN)";
            return $result;
        }

        try {
            $startTime = microtime(true);

            // Сначала выполняем HEAD запрос для получения заголовков
            $headResponse = Http::timeout(self::HTTP_TIMEOUT)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => self::MAX_REDIRECTS,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https']
                    ],
                    'verify' => false,
                ])
                ->head($imageUrl);

            if (!$headResponse->successful()) {
                $result['status_code'] = $headResponse->status();
                $result['error'] = "HTTP error: {$headResponse->status()}";
                return $result;
            }

            $result['accessible'] = true;
            $result['status_code'] = $headResponse->status();

            // Анализ заголовков
            $contentType = $headResponse->header('Content-Type');
            $contentLength = $headResponse->header('Content-Length');

            if ($contentType) {
                $result['content_type'] = strtok($contentType, ';');
                $result['type_valid'] = in_array($result['content_type'], self::ALLOWED_IMAGE_TYPES);
            } else {
                $result['type_valid'] = true;
            }

            if ($contentLength) {
                $result['content_length'] = (int) $contentLength;
                $result['size_valid'] = $this->isValidImageSize($result['content_length']);
            } else {
                $result['size_valid'] = true;
            }

            // Проверка содержимого для файлов подходящего размера
            $shouldValidateContent = !$contentLength ||
                ($contentLength && (int) $contentLength <= self::MAX_CONTENT_SIZE_FOR_VALIDATION);

            if ($shouldValidateContent) {
                $contentValidation = $this->validateImageContent($imageUrl);
                $result['content_valid'] = $contentValidation['valid'];
                $result['validation_method'] = 'head_and_content';

                if (!$contentValidation['valid']) {
                    $result['error'] = $contentValidation['error'];
                }
            } else {
                // Если файл слишком большой для проверки содержимого, считаем его валидным
                $result['content_valid'] = true;
                $result['validation_method'] = 'head_only_large_file';
            }

            $endTime = microtime(true);
            $result['response_time_ms'] = round(($endTime - $startTime) * 1000, 2);

            // Изображение валидно если доступно и проходит все проверки
            $result['valid'] = $result['accessible'] &&
                $result['type_valid'] &&
                $result['size_valid'] &&
                $result['content_valid'];

            if (!$result['valid'] && !$result['error']) {
                $errors = [];
                if (!$result['type_valid'] && $contentType !== null) {
                    $errors[] = "unsupported content type: {$result['content_type']}";
                }
                if (!$result['size_valid'] && $contentLength !== null) {
                    $errors[] = "invalid size: {$result['content_length']} bytes";
                }
                if (!$result['content_valid']) {
                    $errors[] = "invalid image content";
                }
                $result['error'] = implode(', ', $errors);
            }
        } catch (Exception $e) {
            // Специальная обработка различных типов ошибок
            $errorMessage = $e->getMessage();
            $errorLower = strtolower($errorMessage);

            if (
                str_contains($errorLower, 'dns') ||
                str_contains($errorLower, 'nxdomain') ||
                str_contains($errorLower, 'name resolution') ||
                str_contains($errorLower, 'could not resolve host')
            ) {
                $result['error'] = "DNS resolution failed: domain does not exist";
            } elseif (
                str_contains($errorLower, 'connection refused') ||
                str_contains($errorLower, 'connection timeout') ||
                str_contains($errorLower, 'connection timed out')
            ) {
                $result['error'] = "Connection failed: server unreachable";
            } elseif (
                str_contains($errorLower, 'ssl') ||
                str_contains($errorLower, 'certificate')
            ) {
                $result['error'] = "SSL/Certificate error: " . $errorMessage;
            } else {
                $result['error'] = "Request failed: " . $errorMessage;
            }

            Log::warning("Image validation failed", [
                'url' => $imageUrl,
                'error' => $errorMessage,
                'error_type' => $result['error'],
                'domain' => $domain
            ]);
        }

        return $result;
    }

    /**
     * Проверяет содержимое изображения с помощью GET запроса
     *
     * @param string $imageUrl URL изображения
     * @return array Результат проверки содержимого
     */
    private function validateImageContent(string $imageUrl): array
    {
        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => self::MAX_REDIRECTS,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https']
                    ],
                    'verify' => false,
                    'max_size' => self::MAX_CONTENT_SIZE_FOR_VALIDATION, // Ограничиваем размер
                ])
                ->get($imageUrl);

            if (!$response->successful()) {
                return [
                    'valid' => false,
                    'error' => "HTTP error during content validation: {$response->status()}"
                ];
            }

            $content = $response->body();

            // Проверяем на JSON ответы (tracking пиксели часто возвращают JSON)
            if ($this->isJsonResponse($content)) {
                return [
                    'valid' => false,
                    'error' => 'Response contains JSON data instead of image'
                ];
            }

            // Проверяем сигнатуры изображений
            if (!$this->hasValidImageSignature($content)) {
                return [
                    'valid' => false,
                    'error' => 'Content does not match image file signatures'
                ];
            }

            return [
                'valid' => true,
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => "Content validation failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Проверяет, содержит ли контент валидную сигнатуру изображения
     *
     * @param string $content Содержимое файла
     * @return bool true если найдена валидная сигнатура
     */
    private function hasValidImageSignature(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // Проверяем сигнатуры различных форматов изображений
        foreach (self::IMAGE_SIGNATURES as $format => $signatures) {
            foreach ($signatures as $signature) {
                if (str_starts_with($content, $signature)) {
                    return true;
                }

                // Для SVG дополнительно проверяем регистронезависимо
                if ($format === 'svg') {
                    if (stripos($content, $signature) === 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Проверяет, является ли содержимое JSON ответом
     *
     * @param string $content Содержимое для проверки
     * @return bool true если содержимое является JSON
     */
    private function isJsonResponse(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $trimmed = trim($content);

        // Быстрая проверка по первому и последнему символу
        $startsWithJson = str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
        $endsWithJson = str_ends_with($trimmed, '}') || str_ends_with($trimmed, ']');

        if (!$startsWithJson || !$endsWithJson) {
            return false;
        }

        // Проверяем, можно ли декодировать как JSON
        json_decode($trimmed);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Проверяет валидность размера изображения
     *
     * @param int $size Размер в байтах
     * @return bool true если размер валиден
     */
    private function isValidImageSize(int $size): bool
    {
        return $size >= self::MIN_IMAGE_SIZE && $size <= self::MAX_IMAGE_SIZE;
    }

    /**
     * Проверяет валидность креатива в целом
     *
     * @param array $creativeData Данные креатива
     * @return bool true если креатив валиден
     */
    public function isCreativeValid(array $creativeData): bool
    {
        // Проверяем обязательные поля
        $requiredFields = ['title', 'description', 'external_id'];
        foreach ($requiredFields as $field) {
            if (empty($creativeData[$field])) {
                return false;
            }
        }

        // Собираем все URL изображений для проверки
        $imageUrls = array_filter([
            $creativeData['icon_url'] ?? null,
            $creativeData['main_image_url'] ?? null
        ]);

        // Креатив должен иметь хотя бы одно изображение
        if (empty($imageUrls)) {
            return false;
        }

        // Проверяем доступность изображений
        $validationResults = $this->validateImages($imageUrls);

        // Хотя бы одно изображение должно быть валидным
        foreach ($validationResults as $result) {
            if ($result['valid']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, является ли URL трекинговым пикселем
     *
     * @param string $url URL для проверки
     * @return bool true если URL является tracking pixel
     */
    private function isTrackingUrl(string $url): bool
    {
        $url = strtolower($url);

        foreach (self::TRACKING_URL_PATTERNS as $pattern) {
            if (str_contains($url, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет, является ли домен проблемным
     *
     * @param string $domain Домен для проверки
     * @return bool true если домен в blacklist
     */
    private function isProblematicDomain(string $domain): bool
    {
        return in_array(strtolower($domain), self::PROBLEMATIC_DOMAINS);
    }

    /**
     * Проверяет, может ли домен быть разрешен через DNS
     *
     * @param string $domain Домен для проверки
     * @return bool true если домен можно разрешить
     */
    private function isDomainResolvable(string $domain): bool
    {
        if (empty($domain)) {
            return false;
        }

        try {
            // Проверяем A-записи (IPv4)
            $aRecords = dns_get_record($domain, DNS_A);
            if (!empty($aRecords)) {
                return true;
            }

            // Проверяем AAAA-записи (IPv6)
            $aaaaRecords = dns_get_record($domain, DNS_AAAA);
            if (!empty($aaaaRecords)) {
                return true;
            }

            // Проверяем CNAME-записи (алиасы)
            $cnameRecords = dns_get_record($domain, DNS_CNAME);
            if (!empty($cnameRecords)) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::debug("DNS resolution failed for domain", [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Конфигурационные методы для тестирования и настройки
     */

    /**
     * Возвращает поддерживаемые типы изображений
     *
     * @return array Массив MIME типов
     */
    public static function getAllowedImageTypes(): array
    {
        return self::ALLOWED_IMAGE_TYPES;
    }

    /**
     * Возвращает ограничения размера
     *
     * @return array Массив с min/max размерами
     */
    public static function getSizeLimits(): array
    {
        return [
            'min' => self::MIN_IMAGE_SIZE,
            'max' => self::MAX_IMAGE_SIZE,
            'max_content_validation' => self::MAX_CONTENT_SIZE_FOR_VALIDATION
        ];
    }

    /**
     * Возвращает настройки HTTP клиента
     *
     * @return array Массив с настройками
     */
    public static function getHttpSettings(): array
    {
        return [
            'timeout' => self::HTTP_TIMEOUT,
            'max_redirects' => self::MAX_REDIRECTS
        ];
    }

    /**
     * Возвращает поддерживаемые сигнатуры изображений
     *
     * @return array Массив сигнатур по форматам
     */
    public static function getImageSignatures(): array
    {
        return self::IMAGE_SIGNATURES;
    }
}
