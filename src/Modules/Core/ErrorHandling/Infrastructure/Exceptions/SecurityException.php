<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions;

use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * Security Exception.
 *
 * Thrown when security checks fail (rate limiting, CSRF, etc.).
 */
final class SecurityException extends ProblemDetailsException
{
    /**
     * Create security exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(
        string $detail = 'Rate limit exceeded',
        ?int $retryAfter = null,
        ?string $instance = null
    ): self {
        $extensions = $retryAfter ? ['retry_after' => $retryAfter] : [];
        
        $problemDetails = ProblemDetails::custom(
            type: 'https://httpstatuses.com/429',
            title: 'Too Many Requests',
            status: 429,
            detail: $detail,
            instance: $instance,
            extensions: $extensions
        );

        return new self($problemDetails);
    }

    /**
     * Create security exception for invalid CSRF token.
     */
    public static function invalidCsrfToken(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::custom(
            type: 'https://httpstatuses.com/403',
            title: 'CSRF Token Invalid',
            status: 403,
            detail: 'Invalid CSRF token. Please refresh the page and try again.',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create security exception for suspicious activity.
     */
    public static function suspiciousActivity(
        string $detail = 'Suspicious activity detected',
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::custom(
            type: 'https://httpstatuses.com/403',
            title: 'Suspicious Activity',
            status: 403,
            detail: $detail,
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create security exception for blocked IP.
     */
    public static function blockedIp(
        string $detail = 'Your IP address has been blocked',
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::custom(
            type: 'https://httpstatuses.com/403',
            title: 'IP Blocked',
            status: 403,
            detail: $detail,
            instance: $instance
        );

        return new self($problemDetails);
    }
}
