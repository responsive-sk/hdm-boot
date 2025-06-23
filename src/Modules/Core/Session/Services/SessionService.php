<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Session\Services;

use HdmBoot\Modules\Core\User\Domain\Entities\User;
use HdmBoot\Modules\Core\User\Domain\ValueObjects\UserId;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Session Management Service.
 *
 * Handles user sessions with security features.
 */
final class SessionService
{
    private const USER_KEY = 'user_id';
    private const LOGIN_TIME_KEY = 'login_time';
    private const LAST_ACTIVITY_KEY = 'last_activity';
    private const SESSION_TIMEOUT = 3600; // 1 hour

    public function __construct(
        private readonly SessionInterface $session
    ) {
    }

    /**
     * Login user and create session.
     */
    public function loginUser(User $user): void
    {
        $currentTime = time();

        // Regenerate session ID for security
        $this->session->regenerateId();

        $this->session->set(self::USER_KEY, $user->getId()->toString());
        $this->session->set(self::LOGIN_TIME_KEY, $currentTime);
        $this->session->set(self::LAST_ACTIVITY_KEY, $currentTime);

        // Store user data for quick access
        $this->session->set('user_data', [
            'email'  => $user->getEmail(),
            'name'   => $user->getName(),
            'role'   => $user->getRole(),
            'status' => $user->getStatus(),
        ]);
    }

    /**
     * Logout user and destroy session.
     */
    public function logoutUser(): void
    {
        // Clear session data
        $this->session->clear();

        // Destroy session
        $this->session->destroy();

        // Start new session for flash messages
        $this->session->start();
    }

    /**
     * Check if user is logged in.
     */
    public function isLoggedIn(): bool
    {
        if (!$this->session->has(self::USER_KEY)) {
            return false;
        }

        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->logoutUser();
            return false;
        }

        // Update last activity
        $this->session->set(self::LAST_ACTIVITY_KEY, time());

        return true;
    }

    /**
     * Get logged in user ID.
     */
    public function getUserId(): ?UserId
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userIdString = $this->session->get(self::USER_KEY);

        return is_string($userIdString) ? UserId::fromString($userIdString) : null;
    }

    /**
     * Get user data from session.
     *
     * @return array<string, mixed>|null
     */
    public function getUserData(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userData = $this->session->get('user_data');

        if (!is_array($userData)) {
            return null;
        }

        /** @var array<string, mixed> $typedUserData */
        $typedUserData = $userData;
        return $typedUserData;
    }

    /**
     * Check if session is expired.
     */
    private function isSessionExpired(): bool
    {
        $lastActivity = $this->session->get(self::LAST_ACTIVITY_KEY, 0);
        $currentTime = time();
        $lastActivityInt = is_int($lastActivity) ? $lastActivity : (is_numeric($lastActivity) ? (int) $lastActivity : 0);
        $timeDiff = $currentTime - $lastActivityInt;

        return $timeDiff > self::SESSION_TIMEOUT;
    }

    /**
     * Set flash message.
     */
    public function setFlash(string $type, string $message): void
    {
        $this->session->flash($type, $message);
    }

    /**
     * Get and clear flash message.
     */
    public function getFlash(string $type): ?string
    {
        $message = $this->session->getFlashMessage($type);
        return is_string($message) ? $message : null;
    }

    /**
     * Check if user has role.
     */
    public function hasRole(string $role): bool
    {
        $userData = $this->getUserData();

        return ($userData['role'] ?? '') === $role;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get session info for debugging.
     *
     * @return array<string, mixed>
     */
    public function getSessionInfo(): array
    {
        $lastActivity = $this->session->get(self::LAST_ACTIVITY_KEY, 0);
        $lastActivityInt = is_int($lastActivity) ? $lastActivity : (is_numeric($lastActivity) ? (int) $lastActivity : 0);

        return [
            'session_id'     => $this->session->getId(),
            'is_logged_in'   => $this->isLoggedIn(),
            'user_id'        => $this->session->get(self::USER_KEY),
            'login_time'     => $this->session->get(self::LOGIN_TIME_KEY),
            'last_activity'  => $lastActivity,
            'time_remaining' => $this->isLoggedIn()
                ? self::SESSION_TIMEOUT - (time() - $lastActivityInt)
                : 0,
        ];
    }
}
