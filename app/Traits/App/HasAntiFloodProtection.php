<?php

namespace App\Traits\App;

use App\Services\Common\AntiFloodService;
use Illuminate\Support\Facades\App;

trait HasAntiFloodProtection
{
    /**
     * Instance of AntiFloodService
     */
    protected ?AntiFloodService $antiFloodService = null;

    /**
     * Get the AntiFloodService instance
     */
    protected function getAntiFloodService(): AntiFloodService
    {
        if ($this->antiFloodService === null) {
            $this->antiFloodService = App::make(AntiFloodService::class);
        }

        return $this->antiFloodService;
    }

    /**
     * Check if the request limit has been exceeded for the user and action
     *
     * @param  mixed  $userId  Identifier of the user (or IP if authentication is not used)
     * @param  string  $action  Identifier of the action or part of the application (default 'default')
     * @param  int|null  $limit  Request limit for this action. If null, the default value is used.
     * @param  int|null  $window  Window duration in seconds for this action. If null, the default value is used.
     * @return bool true if the limit is not exceeded, false if it is exceeded
     */
    protected function checkAntiFlood($userId, string $action = 'default', ?int $limit = null, ?int $window = null): bool
    {
        return $this->getAntiFloodService()->check($userId, $action, $limit, $window);
    }

    /**
     * Get the anti-flood record for the user and specified action
     *
     * @param  mixed  $userId  Identifier of the user
     * @param  string  $action  Identifier of the action or part of the application (default 'default')
     * @return int|null Number of requests for this action in the current window, or null if record not found
     */
    protected function getAntiFloodRecord($userId, string $action = 'default'): ?int
    {
        return $this->getAntiFloodService()->getRecord($userId, $action);
    }

    /**
     * Delete the anti-flood record for the user and specified action (useful for testing or resetting the limit)
     *
     * @param  mixed  $userId  Identifier of the user
     * @param  string  $action  Identifier of the action or part of the application (default 'default')
     * @return bool True if the key was deleted, false otherwise
     */
    protected function deleteAntiFloodRecord($userId, string $action = 'default'): bool
    {
        return $this->getAntiFloodService()->deleteRecord($userId, $action);
    }

    /**
     * Get the remaining attempts for the user and action
     *
     * @param  mixed  $userId  Identifier of the user
     * @param  string  $action  Identifier of the action or part of the application (default 'default')
     * @param  int|null  $limit  Request limit for this action. If null, the default value is used.
     * @return int Number of remaining attempts (0 if limit exceeded)
     */
    protected function getRemainingAttempts($userId, string $action = 'default', ?int $limit = null): int
    {
        $service = $this->getAntiFloodService();
        $currentUsage = $service->getRecord($userId, $action) ?? 0;
        $limit = $limit ?? $service->defaultLimit;

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get the timestamp of the first request for the user and action
     *
     * @param  mixed  $userId  Identifier of the user
     * @param  string  $action  Identifier of the action or part of the application (default 'default')
     * @return int|null Timestamp or null if not found
     */
    protected function getAntiFloodTimestamp($userId, string $action = 'default'): ?int
    {
        return $this->getAntiFloodService()->getTimestamp($userId, $action);
    }
}
