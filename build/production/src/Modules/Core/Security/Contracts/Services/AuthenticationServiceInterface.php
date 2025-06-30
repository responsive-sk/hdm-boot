<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Contracts\Services;

/**
 * Authentication Service Interface - Public API for Security Module.
 *
 * This interface defines the public API that other modules can use
 * to interact with the Security module's authentication features.
 */
interface AuthenticationServiceInterface
{
    /**
     * Authenticate user for web login.
     *
     * @return array<string, mixed>|null User data if authentication successful, null otherwise
     */
    public function authenticateForWeb(string $email, string $password, string $clientIp): ?array;

    /**
     * Authenticate user for API access.
     *
     * @return array<string, mixed>|null User data if authentication successful, null otherwise
     */
    public function authenticate(string $email, string $password): ?array;

    /**
     * Generate JWT token for user.
     *
     * @param array<string, mixed> $user
     */
    public function generateToken(array $user): string;

    /**
     * Validate JWT token and return user data.
     *
     * @return array<string, mixed>|null User data if token valid, null otherwise
     */
    public function validateToken(string $tokenString): ?array;

    /**
     * Validate authorization header and return user data.
     *
     * @return array<string, mixed>|null User data if header valid, null otherwise
     */
    public function validateAuthorizationHeader(string $authorizationHeader): ?array;

    /**
     * Logout user (log the action).
     *
     * @param array<string, mixed> $user
     */
    public function logout(array $user, string $clientIp): void;
}
