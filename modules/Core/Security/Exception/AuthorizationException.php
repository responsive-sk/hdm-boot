<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Exception;

use RuntimeException;

/**
 * Authorization Exception.
 *
 * Thrown when user doesn't have permission to access a resource.
 */
class AuthorizationException extends RuntimeException
{
    public function __construct(
        string $message = 'Access denied',
        private readonly ?string $requiredPermission = null,
        private readonly ?string $userRole = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get required permission.
     */
    public function getRequiredPermission(): ?string
    {
        return $this->requiredPermission;
    }

    /**
     * Get user role.
     */
    public function getUserRole(): ?string
    {
        return $this->userRole;
    }

    /**
     * Convert to array for API responses.
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'code'                => 'AUTHORIZATION_FAILED',
            'message'             => $this->getMessage(),
            'required_permission' => $this->requiredPermission,
            'user_role'           => $this->userRole ?? 'unknown',
        ];
    }
}
