<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Exception;

use RuntimeException;

/**
 * Authentication Exception.
 *
 * Thrown when authentication fails.
 */
class AuthenticationException extends RuntimeException
{
    public function __construct(
        string $message = 'Authentication failed',
        private readonly ?string $errorCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get error code for API responses.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode ?? 'AUTHENTICATION_FAILED';
    }

    /**
     * Convert to array for API responses.
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'code'    => $this->getErrorCode(),
            'message' => $this->getMessage(),
        ];
    }
}
