<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Domain\Services;

use InvalidArgumentException;
use HdmBoot\Modules\Core\User\Domain\Models\User;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * User Domain Service.
 *
 * Pure business logic for user operations without framework dependencies.
 * Contains core user management logic separated from HTTP layer.
 */
final class UserDomainService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get user by ID with domain logic.
     */
    public function getUserById(string $id): ?User
    {
        try {
            $userData = $this->userRepository->findById($id);

            return $userData ? User::fromArray($userData) : null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user by ID', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get user by email with domain logic.
     */
    public function getUserByEmail(string $email): ?User
    {
        try {
            $userData = $this->userRepository->findByEmail($email);

            return $userData ? User::fromArray($userData) : null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Authenticate user with domain logic.
     */
    public function authenticateUser(string $email, string $password): ?User
    {
        try {
            $userData = $this->userRepository->findByEmail($email);
            if (!$userData) {
                $this->logger->warning('Authentication failed: user not found', ['email' => $email]);

                return null;
            }

            if (!isset($userData['password']) || !password_verify($password, $userData['password'])) {
                $this->logger->warning('Authentication failed: invalid password', ['email' => $email]);

                return null;
            }

            $user = User::fromArray($userData);

            if (!$user->isActive()) {
                $this->logger->warning('Authentication failed: user not active', [
                    'email'  => $email,
                    'status' => $user->status,
                ]);

                return null;
            }

            $this->logger->info('User authenticated successfully', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return $user;
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create new user with domain logic.
     */
    public function createUser(
        string $email,
        string $name,
        string $password,
        string $role = 'user'
    ): User {
        // Validate input
        $this->validateUserInput($email, $name, $password);

        // Check if email already exists
        if ($this->userRepository->emailExists($email)) {
            throw new InvalidArgumentException('Email address is already in use');
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
        $savedUserData = $this->userRepository->save($userData);
        $user = User::fromArray($savedUserData);

        $this->logger->info('User created successfully', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'role'    => $user->role,
        ]);

        return $user;
    }

    /**
     * Update user with domain logic.
     */
    public function updateUser(string $id, array $data): User
    {
        $existingUserData = $this->userRepository->findById($id);
        if (!$existingUserData) {
            throw new RuntimeException('User not found');
        }

        // Update allowed fields
        $allowedFields = ['name', 'email', 'role', 'status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return User::fromArray($existingUserData);
        }

        // Check email uniqueness if email is being updated
        if (isset($updateData['email']) && $updateData['email'] !== $existingUserData['email']) {
            if ($this->userRepository->emailExists($updateData['email'])) {
                throw new InvalidArgumentException('Email address is already in use');
            }
        }

        $updatedUserData = $this->userRepository->update($id, $updateData);
        $user = User::fromArray($updatedUserData);

        $this->logger->info('User updated successfully', [
            'user_id'        => $user->id,
            'updated_fields' => array_keys($updateData),
        ]);

        return $user;
    }

    /**
     * Change user password with domain logic.
     */
    public function changePassword(string $id, string $currentPassword, string $newPassword): void
    {
        $userData = $this->userRepository->findById($id);
        if (!$userData) {
            throw new RuntimeException('User not found');
        }

        // Verify current password
        if (!isset($userData['password']) || !password_verify($currentPassword, $userData['password'])) {
            throw new InvalidArgumentException('Current password is incorrect');
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters long');
        }

        // Update password
        $this->userRepository->update($id, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $this->logger->info('User password changed successfully', ['user_id' => $id]);
    }

    /**
     * Delete user with domain logic.
     */
    public function deleteUser(string $id): void
    {
        $userData = $this->userRepository->findById($id);
        if (!$userData) {
            throw new RuntimeException('User not found');
        }

        $this->userRepository->delete($id);

        $this->logger->info('User deleted successfully', [
            'user_id' => $id,
            'email'   => $userData['email'] ?? 'unknown',
        ]);
    }

    /**
     * Check if user has permission with domain logic.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Basic role-based permissions
        return match ($permission) {
            'user.view'    => true, // All authenticated users can view
            'user.edit'    => $user->hasAnyRole(['admin', 'editor']),
            'user.delete'  => $user->isAdmin(),
            'user.manage'  => $user->isAdmin(),
            'admin.access' => $user->isAdmin(),
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
