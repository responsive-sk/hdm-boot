<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Services;

use InvalidArgumentException;
use MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Log\LoggerInterface;

/**
 * Simplified Authentication Service.
 *
 * Handles user authentication without complex domain entities.
 * Implements AuthenticationServiceInterface for module isolation.
 */
final class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly UserService $userService,
        private readonly JwtService $jwtService,
        private readonly SecurityLoginChecker $securityChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Authenticate user with email and password (for web login).
     *
     * @return array<string, mixed>|null
     */
    public function authenticateForWeb(string $email, string $password, string $clientIp): ?array
    {
        try {
            // Security check before authentication attempt
            $this->securityChecker->checkLoginSecurity($email, $clientIp);

            // Authenticate user
            $user = $this->userService->authenticate($email, $password);

            if (!$user) {
                // Record failed attempt
                $this->securityChecker->recordFailedAttempt($email, $clientIp);

                $this->logger->warning('Authentication failed - invalid credentials', [
                    'email' => $email,
                    'ip'    => $clientIp,
                ]);

                return null;
            }

            // Record successful attempt
            $this->securityChecker->recordSuccessfulAttempt($email, $clientIp);

            // Log successful authentication
            $this->logger->info('User authenticated successfully', [
                'user_id' => $user['id'],
                'email'   => $user['email'],
                'ip'      => $clientIp,
            ]);

            return $user;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'ip'    => $clientIp,
            ]);

            return null;
        }
    }

    /**
     * Authenticate user with email and password (for API/JWT).
     *
     * @return array<string, mixed>|null
     */
    public function authenticate(string $email, string $password): ?array
    {
        try {
            // Authenticate user
            $user = $this->userService->authenticate($email, $password);

            if (!$user) {
                $this->logger->warning('API authentication failed - invalid credentials', [
                    'email' => $email,
                ]);

                return null;
            }

            // Log successful authentication
            $this->logger->info('API user authenticated successfully', [
                'user_id' => $user['id'],
                'email'   => $user['email'],
            ]);

            return $user;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logger->error('API authentication error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate JWT token for user.
     *
     * @param array<string, mixed> $user
     */
    public function generateToken(array $user): string
    {
        try {
            return $this->jwtService->generateToken([
                'user_id' => $user['id'],
                'email'   => $user['email'],
                'role'    => $user['role'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('JWT token generation failed', [
                'user_id' => $user['id'],
                'error'   => $e->getMessage(),
            ]);

            throw new InvalidArgumentException('Failed to generate authentication token');
        }
    }

    /**
     * Validate JWT token and return user data.
     */
    public function validateToken(string $tokenString): ?array
    {
        try {
            $payload = $this->jwtService->validateToken($tokenString);

            if (!$payload || !isset($payload['user_id'])) {
                return null;
            }

            // Get fresh user data
            $user = $this->userService->getUserById($payload['user_id']);

            if (!$user || $user['status'] !== 'active') {
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            $this->logger->warning('JWT token validation failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract and validate token from Authorization header.
     */
    public function validateAuthorizationHeader(string $authorizationHeader): ?array
    {
        try {
            // Extract Bearer token
            if (!str_starts_with($authorizationHeader, 'Bearer ')) {
                return null;
            }

            $tokenString = substr($authorizationHeader, 7);

            return $this->validateToken($tokenString);
        } catch (\Exception $e) {
            $this->logger->warning('Authorization header validation failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Logout user (log the action).
     */
    public function logout(array $user, string $clientIp): void
    {
        $this->logger->info('User logged out', [
            'user_id' => $user['id'],
            'email'   => $user['email'],
            'ip'      => $clientIp,
        ]);
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(array $user, string $permission): bool
    {
        return $this->userService->hasPermission($user, $permission);
    }
}
