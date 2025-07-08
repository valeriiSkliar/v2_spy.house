<?php

declare(strict_types=1);

namespace App\Services\Parsers\Exceptions;

/**
 * Exception for rate limit exceeded errors
 * 
 * @package App\Services\Parsers\Exceptions
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class RateLimitException extends ParserException
{
    /**
     * Retry after seconds
     */
    protected int $retryAfter;

    /**
     * Create a new rate limit exception
     *
     * @param string $message Error message
     * @param int $retryAfter Seconds to wait before retry
     * @param int $code Error code
     * @param \Exception|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $retryAfter = 60,
        int $code = 429,
        \Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get retry after seconds
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Set retry after seconds
     *
     * @param int $retryAfter
     * @return self
     */
    public function setRetryAfter(int $retryAfter): self
    {
        $this->retryAfter = $retryAfter;
        return $this;
    }

    /**
     * Get exception data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter
        ]);
    }
}
