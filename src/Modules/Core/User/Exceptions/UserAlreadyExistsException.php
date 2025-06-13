<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Exceptions;

use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions\ProblemDetailsException;
use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * User Already Exists Exception.
 *
 * Thrown when attempting to create a user that already exists.
 */
final class UserAlreadyExistsException extends ProblemDetailsException
{
    /**
     * Create exception for email already exists.
     */
    public static function withEmail(string $email, ?string $instance = null): self
    {
        $problemDetails = ProblemDetails::conflictError(
            detail: "User with email '{$email}' already exists",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create generic user already exists exception.
     */
    public static function generic(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::conflictError(
            detail: 'User already exists',
            instance: $instance
        );

        return new self($problemDetails);
    }
}
