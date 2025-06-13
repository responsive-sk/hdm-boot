<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions;

use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * Authentication Exception.
 *
 * Thrown when authentication fails.
 */
final class AuthenticationException extends ProblemDetailsException
{
    /**
     * Create authentication exception for invalid credentials.
     */
    public static function invalidCredentials(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Invalid email or password',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authentication exception for missing credentials.
     */
    public static function missingCredentials(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Authentication credentials are required',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authentication exception for expired token.
     */
    public static function expiredToken(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Authentication token has expired',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authentication exception for invalid token.
     */
    public static function invalidToken(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Invalid authentication token',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authentication exception for account locked.
     */
    public static function accountLocked(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Account is locked due to too many failed attempts',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authentication exception for inactive account.
     */
    public static function accountInactive(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: 'Account is inactive',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create custom authentication exception.
     */
    public static function custom(string $detail, ?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authenticationError(
            detail: $detail,
            instance: $instance
        );

        return new self($problemDetails);
    }
}
