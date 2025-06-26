<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Mark\Services;

use HdmBoot\Modules\Core\Mark\Repository\MarkRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mark Authentication Service.
 * 
 * Handles authentication for mark users (super users).
 * Uses mark.db database exclusively.
 */
final class MarkAuthenticationService
{
    public function __construct(
        private readonly MarkRepositoryInterface $markRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Authenticate mark user by email and password.
     * 
     * @param string $email Mark user email
     * @param string $password Plain text password
     * @return array|null Mark user data or null if authentication fails
     */
    public function authenticate(string $email, string $password): ?array
    {
        $this->logger->debug('🔴 MARK AUTH: Starting authentication', ['email' => $email]);

        // Find mark user by email
        $markUser = $this->markRepository->findByEmail($email);

        if ($markUser === null) {
            $this->logger->warning('🔴 MARK AUTH: User not found', ['email' => $email]);
            return null;
        }

        $this->logger->debug('🔴 MARK AUTH: User found', [
            'email' => $email,
            'user_id' => $markUser['id'],
            'role' => $markUser['role']
        ]);

        // Verify password
        if (!isset($markUser['password_hash']) || !password_verify($password, $markUser['password_hash'])) {
            $this->logger->warning('🔴 MARK AUTH: Invalid password', ['email' => $email]);
            return null;
        }

        // Check if user is active
        if ($markUser['status'] !== 'active') {
            $this->logger->warning('🔴 MARK AUTH: User not active', [
                'email' => $email,
                'status' => $markUser['status']
            ]);
            return null;
        }

        // Update last login
        $this->markRepository->updateLastLogin($markUser['id']);

        $this->logger->info('🔴 MARK AUTH: Authentication successful', [
            'email' => $email,
            'user_id' => $markUser['id'],
            'role' => $markUser['role']
        ]);

        return $markUser;
    }

    /**
     * Check if email belongs to mark system.
     * 
     * @param string $email Email to check
     * @return bool True if email exists in mark.db
     */
    public function isMarkUser(string $email): bool
    {
        $markUser = $this->markRepository->findByEmail($email);
        return $markUser !== null;
    }

    /**
     * Get mark user by ID.
     * 
     * @param string $userId Mark user ID
     * @return array|null Mark user data or null if not found
     */
    public function getMarkUser(string $userId): ?array
    {
        return $this->markRepository->findById($userId);
    }

    /**
     * Validate mark session.
     * 
     * @param array $session Session data
     * @return bool True if session is valid
     */
    public function validateMarkSession(array $session): bool
    {
        if (!isset($session['mark_user_id'], $session['mark_login_time'])) {
            return false;
        }

        // Check if session is not too old (24 hours)
        $maxAge = 24 * 60 * 60; // 24 hours
        if (time() - $session['mark_login_time'] > $maxAge) {
            return false;
        }

        // Verify user still exists and is active
        $markUser = $this->getMarkUser($session['mark_user_id']);
        if ($markUser === null || $markUser['status'] !== 'active') {
            return false;
        }

        return true;
    }
}
