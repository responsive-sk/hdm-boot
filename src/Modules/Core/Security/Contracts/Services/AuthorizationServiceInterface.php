<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Contracts\Services;

/**
 * Authorization Service Interface - Public API for Security Module.
 *
 * This interface defines the public API that other modules can use
 * to interact with the Security module's authorization features.
 */
interface AuthorizationServiceInterface
{
    /**
     * Check if user has specific permission.
     *
     * @param array<string, mixed> $user
     */
    public function hasPermission(array $user, string $permission): bool;

    /**
     * Check if user has any of the specified permissions.
     *
     * @param array<string, mixed> $user
     * @param array<string> $permissions
     */
    public function hasAnyPermission(array $user, array $permissions): bool;

    /**
     * Check if user has all of the specified permissions.
     *
     * @param array<string, mixed> $user
     * @param array<string> $permissions
     */
    public function hasAllPermissions(array $user, array $permissions): bool;

    /**
     * Get all permissions for user based on role.
     *
     * @param array<string, mixed> $user
     *
     * @return array<string>
     */
    public function getUserPermissions(array $user): array;

    /**
     * Check if user can access resource.
     *
     * @param array<string, mixed> $user
     */
    public function canAccessResource(array $user, string $resource, string $action = 'view'): bool;
}
