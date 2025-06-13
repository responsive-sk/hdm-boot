<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Exceptions;

use MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\ProblemDetails\ProblemDetails;

/**
 * Authorization Exception.
 *
 * Thrown when authorization/permission checks fail.
 */
final class AuthorizationException extends ProblemDetailsException
{
    /**
     * Create authorization exception for insufficient permissions.
     */
    public static function insufficientPermissions(
        string $requiredPermission,
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::authorizationError(
            detail: "Insufficient permissions. Required: {$requiredPermission}",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authorization exception for access denied.
     */
    public static function accessDenied(?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authorizationError(
            detail: 'Access denied',
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authorization exception for resource access.
     */
    public static function resourceAccessDenied(
        string $resource,
        string $action = 'access',
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::authorizationError(
            detail: "You don't have permission to {$action} {$resource}",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authorization exception for role requirement.
     */
    public static function roleRequired(
        string $requiredRole,
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::authorizationError(
            detail: "Access requires {$requiredRole} role",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create authorization exception for ownership requirement.
     */
    public static function ownershipRequired(
        string $resource,
        ?string $instance = null
    ): self {
        $problemDetails = ProblemDetails::authorizationError(
            detail: "You can only access your own {$resource}",
            instance: $instance
        );

        return new self($problemDetails);
    }

    /**
     * Create custom authorization exception.
     */
    public static function custom(string $detail, ?string $instance = null): self
    {
        $problemDetails = ProblemDetails::authorizationError(
            detail: $detail,
            instance: $instance
        );

        return new self($problemDetails);
    }
}
