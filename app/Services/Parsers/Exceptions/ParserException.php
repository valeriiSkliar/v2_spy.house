<?php

declare(strict_types=1);

namespace App\Services\Parsers\Exceptions;

use Exception;

/**
 * Base exception for parser-related errors
 * 
 * @package App\Services\Parsers\Exceptions
 * @author SeniorSoftwareEngineer
 * @version 1.0.0
 */
class ParserException extends Exception
{
    /**
     * Additional context data
     */
    protected array $context = [];

    /**
     * Create a new parser exception
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     * @param array $context Additional context data
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context data
     *
     * @param array $context
     * @return self
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get exception data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
}
