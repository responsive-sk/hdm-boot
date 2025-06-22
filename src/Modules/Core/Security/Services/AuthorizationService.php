<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Services;

use HdmBoot\Modules\Core\User\Domain\Entities\User;
use Psr\Log\LoggerInterface;

/**
 * Authorization Service with Array-Based User Data.
 *
 * Handles role-based access control and permissions using array-based user data.
 */
final class AuthorizationService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /** @var array<string, array<string>> */
    private array $permissions = [
        // User permissions
        'user.view'       => ['user', 'editor', 'admin'],
        'user.create'     => ['admin'],
        'user.edit'       => ['admin'],
        'user.delete'     => ['admin'],
        'user.manage'     => ['admin'],
        'user.statistics' => ['admin'],

        // Admin permissions
        'admin.access'     => ['admin'],
        'admin.users'      => ['admin'],
        'admin.security'   => ['admin'],
        'admin.statistics' => ['admin'],

        // Article permissions (for future Article module)
        'article.view'    => ['user', 'editor', 'admin'],
        'article.create'  => ['editor', 'admin'],
        'article.edit'    => ['editor', 'admin'],
        'article.delete'  => ['admin'],
        'article.publish' => ['editor', 'admin'],

        // Security permissions
        'security.login'   => ['user', 'editor', 'admin'],
        'security.logout'  => ['user', 'editor', 'admin'],
        'security.refresh' => ['user', 'editor', 'admin'],
    ];

    /**
     * Check if user has specific permission.
     *
     * @param array<string, mixed> $user
     */
    public function hasPermission(array $user, string $permission): bool
    {
        try {
            $role = $user['role'] ?? 'user';

            // Admin has all permissions
            if ($role === 'admin') {
                return true;
            }

            // Check if permission exists
            if (!isset($this->permissions[$permission])) {
                $this->logger->warning('Unknown permission requested', ['permission' => $permission]);

                return false;
            }

            // Check if user's role has this permission
            return in_array($role, $this->permissions[$permission], true);
        } catch (\Exception $e) {
            $this->logger->error('Permission check failed', [
                'user_id'    => $user['id'] ?? 'unknown',
                'permission' => $permission,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if user has any of the specified permissions.
     *
     * @param array<string> $permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user->toArray(), $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the specified permissions.
     *
     * @param array<string> $permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user->toArray(), $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for a user's role.
     *
     * @return array<string>
     */
    public function getUserPermissions(User $user): array
    {
        $userRole = $user->getRole();
        $userPermissions = [];

        foreach ($this->permissions as $permission => $allowedRoles) {
            if (in_array($userRole, $allowedRoles, true)) {
                $userPermissions[] = $permission;
            }
        }

        return $userPermissions;
    }

    /**
     * Get all available permissions.
     *
     * @return array<string>
     */
    public function getAllPermissions(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * Get permissions by role.
     *
     * @return array<string>
     */
    public function getPermissionsByRole(string $role): array
    {
        $rolePermissions = [];

        foreach ($this->permissions as $permission => $allowedRoles) {
            if (in_array($role, $allowedRoles, true)) {
                $rolePermissions[] = $permission;
            }
        }

        return $rolePermissions;
    }

    /**
     * Check if user can access admin area.
     */
    public function canAccessAdmin(User $user): bool
    {
        return $this->hasPermission($user->toArray(), 'admin.access');
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(User $user): bool
    {
        return $this->hasPermission($user->toArray(), 'user.manage');
    }

    /**
     * Check if user can view user statistics.
     */
    public function canViewUserStatistics(User $user): bool
    {
        return $this->hasPermission($user->toArray(), 'user.statistics');
    }

    /**
     * Add custom permission (for dynamic permissions).
     *
     * @param array<string> $allowedRoles
     */
    public function addPermission(string $permission, array $allowedRoles): void
    {
        $this->permissions[$permission] = $allowedRoles;
    }

    /**
     * Remove permission.
     */
    public function removePermission(string $permission): void
    {
        unset($this->permissions[$permission]);
    }
}
