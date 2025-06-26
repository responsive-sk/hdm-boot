<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Mark\Repository;

/**
 * Mark Repository Interface.
 * 
 * Defines contract for mark user data access.
 * Uses mark.db database exclusively.
 */
interface MarkRepositoryInterface
{
    /**
     * Find mark user by email.
     * 
     * @param string $email Mark user email
     * @return array|null Mark user data or null if not found
     */
    public function findByEmail(string $email): ?array;

    /**
     * Find mark user by ID.
     * 
     * @param string $id Mark user ID
     * @return array|null Mark user data or null if not found
     */
    public function findById(string $id): ?array;

    /**
     * Update last login time for mark user.
     * 
     * @param string $id Mark user ID
     * @return bool True if updated successfully
     */
    public function updateLastLogin(string $id): bool;

    /**
     * Get all mark users.
     * 
     * @return array List of mark users
     */
    public function findAll(): array;

    /**
     * Create new mark user.
     * 
     * @param array $userData Mark user data
     * @return string Created user ID
     */
    public function create(array $userData): string;

    /**
     * Update mark user.
     * 
     * @param string $id Mark user ID
     * @param array $userData Updated user data
     * @return bool True if updated successfully
     */
    public function update(string $id, array $userData): bool;

    /**
     * Delete mark user.
     * 
     * @param string $id Mark user ID
     * @return bool True if deleted successfully
     */
    public function delete(string $id): bool;
}
