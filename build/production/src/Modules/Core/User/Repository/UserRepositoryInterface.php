<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Repository;

/**
 * Simplified User Repository Interface.
 *
 * Defines the contract for user data persistence operations using arrays.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(string $id): ?array;

    /**
     * Find user by email address.
     */
    public function findByEmail(string $email): ?array;

    /**
     * Find all users with optional filters.
     */
    public function findAll(array $filters = []): array;

    /**
     * Save user data (create or update).
     */
    public function save(array $userData): array;

    /**
     * Update user data.
     */
    public function update(string $id, array $data): array;

    /**
     * Delete user by ID.
     */
    public function delete(string $id): void;

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool;

    /**
     * Get user statistics.
     */
    public function getStatistics(): array;
}
