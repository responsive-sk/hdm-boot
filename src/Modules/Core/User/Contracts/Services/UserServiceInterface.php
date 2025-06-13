<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Contracts\Services;

/**
 * User Service Interface - Public API for User Module.
 *
 * This interface defines the public API that other modules can use
 * to interact with the User module without direct dependencies.
 */
interface UserServiceInterface
{
    /**
     * Authenticate user with email and password.
     *
     * @return array<string, mixed>|null User data if authentication successful, null otherwise
     */
    public function authenticate(string $email, string $password): ?array;

    /**
     * Get user by ID.
     *
     * @return array<string, mixed>|null User data if found, null otherwise
     */
    public function getUserById(string $id): ?array;

    /**
     * Get user by email.
     *
     * @return array<string, mixed>|null User data if found, null otherwise
     */
    public function getUserByEmail(string $email): ?array;

    /**
     * Check if user has specific permission.
     *
     * @param array<string, mixed> $user
     */
    public function hasPermission(array $user, string $permission): bool;

    /**
     * Check if email exists in the system.
     */
    public function emailExists(string $email): bool;

    /**
     * Get user statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserStatistics(): array;
}
