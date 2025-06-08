<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Services;

use InvalidArgumentException;
use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use RuntimeException;

/**
 * User Service.
 *
 * Handles user business logic and operations.
 */
final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Create a new user.
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

        // Create user
        $user = User::create($email, $name, $password, $role);

        // Save to repository
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Get user by ID.
     */
    public function getUserById(string $id): ?User
    {
        try {
            $userId = UserId::fromString($id);
            return $this->userRepository->findById($userId);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Get user by email.
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Update user information.
     */
    public function updateUser(string $id, array $data): User
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        // Update email if provided
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            if ($this->userRepository->emailExists($data['email'])) {
                throw new InvalidArgumentException('Email address is already in use');
            }
            $user->updateEmail($data['email']);
        }

        // Update name if provided
        if (isset($data['name'])) {
            $user->updateName($data['name']);
        }

        // Update role if provided (admin only operation)
        if (isset($data['role'])) {
            $user->changeRole($data['role']);
        }

        // Save changes
        $this->userRepository->save($user);

        return $user;
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
        if (!$user->verifyPassword($currentPassword)) {
            throw new InvalidArgumentException('Current password is incorrect');
        }

        // Change password
        $user->changePassword($newPassword);

        // Save changes
        $this->userRepository->save($user);
    }

    /**
     * Reset user password with token.
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $user = $this->userRepository->findByPasswordResetToken($token);
        if (!$user) {
            throw new InvalidArgumentException('Invalid or expired password reset token');
        }

        // Change password
        $user->changePassword($newPassword);

        // Save changes
        $this->userRepository->save($user);
    }

    /**
     * Generate password reset token.
     */
    public function generatePasswordResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            // Don't reveal if email exists or not
            return null;
        }

        $token = $user->generatePasswordResetToken();
        $this->userRepository->save($user);

        return $token;
    }

    /**
     * Verify email with token.
     */
    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findByEmailVerificationToken($token);
        if (!$user) {
            return false;
        }

        $user->verifyEmail();
        $this->userRepository->save($user);

        return true;
    }

    /**
     * Generate email verification token.
     */
    public function generateEmailVerificationToken(string $id): string
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $token = $user->generateEmailVerificationToken();
        $this->userRepository->save($user);

        return $token;
    }

    /**
     * Authenticate user.
     */
    public function authenticate(string $email, string $password): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw new InvalidArgumentException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new InvalidArgumentException('User account is not active');
        }

        if (!$user->verifyPassword($password)) {
            throw new InvalidArgumentException('Invalid credentials');
        }

        // Record login
        $user->recordLogin();
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Activate user.
     */
    public function activateUser(string $id): void
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $user->activate();
        $this->userRepository->save($user);
    }

    /**
     * Deactivate user.
     */
    public function deactivateUser(string $id): void
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $user->deactivate();
        $this->userRepository->save($user);
    }

    /**
     * Suspend user.
     */
    public function suspendUser(string $id): void
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new RuntimeException('User not found');
        }

        $user->suspend();
        $this->userRepository->save($user);
    }

    /**
     * Delete user.
     */
    public function deleteUser(string $id): void
    {
        $userId = UserId::fromString($id);
        $this->userRepository->delete($userId);
    }

    /**
     * Get all users with optional filters.
     */
    public function getUsers(array $filters = []): array
    {
        return $this->userRepository->findAll($filters);
    }

    /**
     * Get users with pagination.
     */
    public function getUsersWithPagination(
        int $page = 1,
        int $limit = 20,
        array $filters = []
    ): array {
        return $this->userRepository->findWithPagination($page, $limit, $filters);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics(): array
    {
        return $this->userRepository->getStatistics();
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // Basic role-based permissions
        return match ($permission) {
            'user.view' => true, // All authenticated users can view
            'user.edit' => $user->isAdmin() || $user->isEditor(),
            'user.delete' => $user->isAdmin(),
            'user.manage' => $user->isAdmin(),
            'admin.access' => $user->isAdmin(),
            default => false,
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

        if (strlen(trim($name)) < 2) {
            throw new InvalidArgumentException('Name must be at least 2 characters long');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters long');
        }

        // Additional password strength validation
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            throw new InvalidArgumentException(
                'Password must contain at least one lowercase letter, one uppercase letter, and one number'
            );
        }
    }
}
