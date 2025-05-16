<?php

namespace App\Services\App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class AntiFloodService
{
    // Значения по умолчанию для лимита запросов и окна в секундах (например, 3600 секунд = 1 час)
    public int $defaultLimit;
    public int $defaultWindow;

    public function __construct(int $defaultLimit = 10, int $defaultWindow = 3600)
    {
        $this->defaultLimit = $defaultLimit;
        $this->defaultWindow = $defaultWindow;
    }

    /**
     * Проверяет, не превышен ли лимит для пользователя для конкретного действия.
     * При каждом вызове увеличивает счётчик.
     *
     * @param mixed  $userId Идентификатор пользователя (или IP, если аутентификация не используется)
     * @param string $action Идентификатор действия или части приложения (по умолчанию 'default')
     * @param int|null $limit Лимит запросов для данного действия. Если null, используется значение по умолчанию.
     * @param int|null $window Длительность окна в секундах для данного действия. Если null, используется значение по умолчанию.
     *
     * @return bool true, если лимит не превышен, false – если превышен
     */
    public function check($userId, string $action = 'default', ?int $limit = null, ?int $window = null): bool
    {
        $limit = $limit ?? $this->defaultLimit;
        $window = $window ?? $this->defaultWindow;

        $key = $this->getKey($userId, $action);
        $current = Redis::incr($key);

        // Если это первый запрос, устанавливаем время жизни ключа
        if ($current === 1) {
            Redis::expire($key, $window);
        }

        return $current <= $limit;
    }

    /**
     * Формирует ключ для Redis на основе идентификатора пользователя, действия и текущего окна (например, текущего часа).
     *
     * @param mixed  $userId Идентификатор пользователя
     * @param string $action Идентификатор действия или части приложения (по умолчанию 'default')
     *
     * @return string
     */
    protected function getKey($userId, string $action = 'default'): string
    {
        $windowKey = date('YmdHi'); // формирование ключа с учётом года, месяца, дня, часа и минуты
        return "antiflood:{$userId}:{$action}:{$windowKey}";
    }

    /**
     * Получает запись антифлуда для пользователя и указанного действия.
     *
     * @param mixed  $userId Идентификатор пользователя
     * @param string $action Идентификатор действия или части приложения (по умолчанию 'default')
     *
     * @return int|null Количество запросов для данного действия в текущем окне, либо null если запись не найдена.
     */
    public function getRecord($userId, string $action = 'default'): ?int
    {
        $key = $this->getKey($userId, $action);
        $current = Redis::get($key);
        return $current !== null ? (int)$current : null;
    }

    /**
     * Удаляет запись антифлуда для пользователя и указанного действия (полезно для тестирования или сброса лимита).
     *
     * @param mixed  $userId Идентификатор пользователя
     * @param string $action Идентификатор действия или части приложения (по умолчанию 'default')
     *
     * @return bool True если ключ был удалён, false в противном случае.
     */
    public function deleteRecord($userId, string $action = 'default'): bool
    {
        $key = $this->getKey($userId, $action);
        return (bool) Redis::del($key);
    }

    /**
     * Проверяет разрешение на выполнение действия с учетом антифлуд защиты
     *
     * @param string $action Идентификатор действия
     * @param int|null $limit Лимит запросов
     * @param int|null $window Временное окно в секундах
     * @return bool
     */
    public function isAllowed(string $action, ?int $limit = null, ?int $window = null): bool
    {
        $userId = Auth::id() ?? request()->ip();
        return $this->check($userId, $action, $limit, $window);
    }
}
