<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Services;

use HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Helpers\ErrorHelper;
use HdmBoot\Modules\Core\User\Contracts\Services\UserServiceInterface;
use HdmBoot\Modules\Core\User\Exceptions\UserAlreadyExistsException;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Simplified User Service.
 *
 * Handles basic user operations without complex domain entities.
 * Implements UserServiceInterface for module isolation.
 */
final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get user by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getUserById(string $id): ?array
    {
        try {
            $user = $this->userRepository->findById($id);

            return $user; // Return null if not found, throw exception in strict mode
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user by ID', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get user by email.
     *
     * @return array<string, mixed>|null
     */
    public function getUserByEmail(string $email): ?array
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Authenticate user.
     */
    public function authenticate(string $email, string $password): ?array
    {
        error_log('🔍 USER DEBUG: Looking up user by email: ' . $email);
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            error_log('🔍 USER DEBUG: User not found in repository: ' . $email);
            $this->logger->warning('Authentication failed: user not found', ['email' => $email]);

            return null;
        }

        error_log('🔍 USER DEBUG: User found, checking password for: ' . $email);

        if (!isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            $this->logger->warning('Authentication failed: invalid password', ['email' => $email]);

            return null;
        }

        if ($user['status'] !== 'active') {
            $this->logger->warning('Authentication failed: user not active', ['email' => $email]);

            return null;
        }

        $this->logger->info('User authenticated successfully', ['user_id' => $user['id']]);

        return $user;
    }

    /**
     * Create a new user.
     */
    public function createUser(
        string $email,
        string $name,
        string $password,
        string $role = 'user'
    ): array {
        // Validate input
        $this->validateUserInput($email, $name, $password);

        // Check if email already exists
        if ($this->userRepository->emailExists($email)) {
            throw UserAlreadyExistsException::withEmail($email);
        }

        // Create user data
        $userData = [
            'email'      => $email,
            'name'       => $name,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'role'       => $role,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Save to repository
        $user = $this->userRepository->save($userData);

        $this->logger->info('User created successfully', ['user_id' => $user['id']]);

        return $user;
    }

    /**
     * Update user information.
     */
    public function updateUser(string $id, array $data): array
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        // Update allowed fields
        $allowedFields = ['name', 'email', 'role', 'status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return $user;
        }

        // Check email uniqueness if email is being updated
        if (isset($updateData['email']) && $updateData['email'] !== $user['email']) {
            if ($this->userRepository->emailExists($updateData['email'])) {
                throw new InvalidArgumentException('Email address is already in use');
            }
        }

        $updatedUser = $this->userRepository->update($id, $updateData);

        $this->logger->info('User updated successfully', ['user_id' => $id]);

        return $updatedUser;
    }

    /**
     * Change user password.
     */
    public function changePassword(string $id, string $currentPassword, string $newPassword): void
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new InvalidArgumentException('Current password is incorrect');
        }

        // Update password
        $this->userRepository->update($id, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $this->logger->info('User password changed successfully', ['user_id' => $id]);
    }

    /**
     * Delete user.
     */
    public function deleteUser(string $id): void
    {
        $this->userRepository->delete($id);
        $this->logger->info('User deleted successfully', ['user_id' => $id]);
    }

    /**
     * Get all users with optional filters.
     */
    public function getUsers(array $filters = []): array
    {
        return $this->userRepository->findAll($filters);
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(array $user, string $permission): bool
    {
        // Basic role-based permissions
        return match ($permission) {
            'user.view'    => true, // All authenticated users can view
            'user.edit'    => in_array($user['role'], ['admin', 'editor']),
            'user.delete'  => $user['role'] === 'admin',
            'user.manage'  => $user['role'] === 'admin',
            'admin.access' => $user['role'] === 'admin',
            default        => false,
        };
    }

    /**
     * Check if email exists in the system.
     */
    public function emailExists(string $email): bool
    {
        try {
            $user = $this->userRepository->findByEmail($email);

            return $user !== null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to check email existence', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserStatistics(): array
    {
        try {
            // This would typically query the repository for statistics
            // For now, return basic stats
            return [
                'total_users'    => 0, // Would be calculated from repository
                'active_users'   => 0,
                'inactive_users' => 0,
                'admin_users'    => 0,
                'last_updated'   => date('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error'        => 'Failed to retrieve statistics',
                'last_updated' => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Validate user input using Error Helper.
     */
    private function validateUserInput(string $email, string $name, string $password): void
    {
        // Validate required fields
        ErrorHelper::validateRequired($email, 'email');
        ErrorHelper::validateRequired($name, 'name');
        ErrorHelper::validateRequired($password, 'password');

        // Validate email format
        ErrorHelper::validateEmail($email);

        // Validate password length
        ErrorHelper::validateMinLength($password, 8, 'password');
    }
}
