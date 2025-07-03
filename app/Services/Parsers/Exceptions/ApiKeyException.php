<?php

declare(strict_types=1);

namespace App\Services\Parsers\Exceptions;

/**
 * Exception for API key authentication errors
 * 
 * @package App\Services\Parsers\Exceptions
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ApiKeyException extends ParserException
{
    /**
     * API key that caused the error (masked for security)
     */
    protected string $maskedApiKey;

    /**
     * Create a new API key exception
     *
     * @param string $message Error message
     * @param string $apiKey API key that caused the error
     * @param int $code Error code
     * @param \Exception|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = 'Invalid API key',
        string $apiKey = '',
        int $code = 401,
        \Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->maskedApiKey = $this->maskApiKey($apiKey);
    }

    /**
     * Get masked API key
     *
     * @return string
     */
    public function getMaskedApiKey(): string
    {
        return $this->maskedApiKey;
    }

    /**
     * Mask API key for security purposes
     *
     * @param string $apiKey
     * @return string
     */
    private function maskApiKey(string $apiKey): string
    {
        if (empty($apiKey)) {
            return '[EMPTY]';
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($apiKey, 0, 4) . str_repeat('*', $length - 8) . substr($apiKey, -4);
    }

    /**
     * Get exception data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'masked_api_key' => $this->maskedApiKey
        ]);
    }
}
