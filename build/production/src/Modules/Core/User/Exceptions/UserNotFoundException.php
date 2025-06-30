<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Exceptions;

use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Exceptions\ProblemDetailsException;
use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * User Not Found Exception.
 *
 * Thrown when a requested user cannot be found.
 */
final class UserNotFoundException extends ProblemDetailsException
{
    /**
     * Create exception for user not found by ID.
     */
    public static function byId(string $userId, ?string $instance = null): self
    {
        $problemDetails = ProblemDetails::notFoundError(
            detail: "User with ID '{$userId}' not found",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create exception for user not found by email.
     */
    public static function byEmail(string $email, ?string $instance = null): self
    {
        $problemDetails = ProblemDetails::notFoundError(
            detail: "User with email '{$email}' not found",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create generic user not found exception.
     */
    public static function generic(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::notFoundError(
            detail: 'User not found',
            instance: $instance
        );

        return new self($problemDetails);
    }
}
