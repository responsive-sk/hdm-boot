<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Services;

use InvalidArgumentException;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Simplified User Service.
 *
 * Handles basic user operations without complex domain entities.
 */
final class UserService
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
            return $this->userRepository->findById($id);
        } catch (InvalidArgumentException) {
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
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            $this->logger->warning('Authentication failed: user not found', ['email' => $email]);
            return null;
        }

        if (!isset($user['password']) || !password_verify($password, $user['password'])) {
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
            throw new InvalidArgumentException('Email address is already in use');
        }

        // Create user data
        $userData = [
            'email' => $email,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
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
        if (!password_verify($currentPassword, $user['password'])) {
            throw new InvalidArgumentException('Current password is incorrect');
        }

        // Update password
        $this->userRepository->update($id, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
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
     * Validate user input.
     */
    private function validateUserInput(string $email, string $name, string $password): void
    {
        if (empty(trim($email))) {
            throw new InvalidArgumentException('Email is required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (empty(trim($name))) {
            throw new InvalidArgumentException('Name is required');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }
    }
}
