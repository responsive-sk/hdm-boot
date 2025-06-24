<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Session\Exceptions;

use HdmBoot\Modules\Core\Session\Enum\SecurityType;
use RuntimeException;

/**
 * Session Security Exception.
 *
 * Thrown when session or CSRF security checks fail.
 */
class SecurityException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly SecurityType $securityType,
        private readonly int|string $remainingDelay = 0,
    ) {
        parent::__construct($message);
    }

    /**
     * Get remaining delay (int for seconds or 'captcha' string).
     */
    public function getRemainingDelay(): int|string
    {
        return $this->remainingDelay;
    }

    /**
     * Get security type that triggered the exception.
     */
    public function getSecurityType(): SecurityType
    {
        return $this->securityType;
    }

    /**
     * Get user-friendly public message.
     */
    public function getPublicMessage(): string
    {
        return match ($this->getSecurityType()) {
            SecurityType::CSRF_TOKEN_INVALID => 'CSRF token validation failed. Please refresh the page and try again.',
            SecurityType::SESSION_EXPIRED    => 'Your session has expired. Please log in again.',
            SecurityType::SESSION_INVALID    => 'Invalid session. Please log in again.',
            default                          => 'Security check failed. Please try again.',
        };
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code'             => 'SESSION_SECURITY_ERROR',
            'message'          => $this->getPublicMessage(),
            'security_type'    => $this->securityType->value,
            'remaining_delay'  => $this->remainingDelay,
            'requires_refresh' => in_array($this->securityType, [
                SecurityType::CSRF_TOKEN_INVALID,
                SecurityType::SESSION_EXPIRED,
                SecurityType::SESSION_INVALID,
            ], true),
        ];
    }
}
