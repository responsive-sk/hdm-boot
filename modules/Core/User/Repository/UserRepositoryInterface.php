<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Repository;

use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use MvaBootstrap\Modules\Core\User\Domain\ValueObjects\UserId;

/**
 * User Repository Interface.
 *
 * Defines the contract for user data persistence operations.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(UserId $id): ?User;

    /**
     * Find user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by email verification token.
     */
    public function findByEmailVerificationToken(string $token): ?User;

    /**
     * Find user by password reset token.
     */
    public function findByPasswordResetToken(string $token): ?User;

    /**
     * Find all users with optional filters.
     *
     * @param array<string, mixed> $filters
     *
     * @return User[]
     */
    public function findAll(array $filters = []): array;

    /**
     * Find users by role.
     *
     * @return User[]
     */
    public function findByRole(string $role): array;

    /**
     * Find users by status.
     *
     * @return User[]
     */
    public function findByStatus(string $status): array;

    /**
     * Save user (create or update).
     */
    public function save(User $user): void;

    /**
     * Delete user by ID.
     */
    public function delete(UserId $id): void;

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool;

    /**
     * Count total users.
     */
    public function count(): int;

    /**
     * Count users by status.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Count users by role.
     *
     * @return array<string, int>
     */
    public function countByRole(): array;

    /**
     * Get user statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * Find users with pagination.
     *
     * @param array<string, mixed> $filters
     *
     * @return array{users: User[], total: int, page: int, limit: int}
     */
    public function findWithPagination(
        int $page = 1,
        int $limit = 20,
        array $filters = []
    ): array;
}
