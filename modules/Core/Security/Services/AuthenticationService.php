<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Services;

use MvaBootstrap\Modules\Core\Security\Domain\ValueObjects\JwtToken;
use MvaBootstrap\Modules\Core\Security\Exception\AuthenticationException;
use MvaBootstrap\Modules\Core\Security\Exception\SecurityException;
use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Log\LoggerInterface;

/**
 * Authentication Service.
 *
 * Handles user authentication with JWT tokens and security checks.
 * Integrates with the existing throttling system.
 */
final class AuthenticationService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly JwtService $jwtService,
        private readonly SecurityLoginChecker $securityChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Authenticate user with email and password (for web sessions).
     * Returns User entity instead of JWT token.
     */
    public function authenticateUser(string $email, string $password, string $clientIp = '127.0.0.1'): User
    {
        try {
            // Security check before authentication attempt
            $this->securityChecker->checkLoginSecurity($email, $clientIp);

            // Find user by email
            $user = $this->userService->getUserByEmail($email);

            if ($user === null) {
                // Record failed attempt
                $this->securityChecker->recordFailedAttempt($email, $clientIp);

                throw new AuthenticationException(
                    'Invalid credentials',
                    'INVALID_CREDENTIALS'
                );
            }

            // Verify password
            if (!$user->verifyPassword($password)) {
                // Record failed attempt
                $this->securityChecker->recordFailedAttempt($email, $clientIp);

                throw new AuthenticationException(
                    'Invalid credentials',
                    'INVALID_CREDENTIALS'
                );
            }

            // Check if user is active
            if (!$user->isActive()) {
                throw new AuthenticationException(
                    'Account is not active',
                    'ACCOUNT_INACTIVE'
                );
            }

            // Record successful attempt
            $this->securityChecker->recordSuccessfulAttempt($email, $clientIp);

            // Update user login info
            $user->recordLogin();
            // Note: For now, we skip updating the user in database
            // This can be implemented later if needed

            // Log successful authentication
            $this->logger->info('User authenticated successfully', [
                'user_id' => $user->getId()->toString(),
                'email'   => $user->getEmail(),
                'ip'      => $clientIp,
            ]);

            return $user;
        } catch (AuthenticationException $e) {
            // Re-throw authentication exceptions
            throw $e;
        } catch (SecurityException $e) {
            // Re-throw security exceptions (throttling, etc.)
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'ip'    => $clientIp,
            ]);

            throw new AuthenticationException(
                'Authentication failed',
                'AUTHENTICATION_ERROR',
                $e
            );
        }
    }

    /**
     * Authenticate user with email and password (for API/JWT).
     */
    public function authenticate(string $email, string $password, string $clientIp): JwtToken
    {
        try {
            // Security check before authentication attempt
            $this->securityChecker->checkLoginSecurity($email, $clientIp);

            // Authenticate user
            $user = $this->userService->authenticate($email, $password);

            // Generate JWT token
            $token = $this->jwtService->generateToken($user);

            // Log successful authentication
            $this->logger->info('User authenticated successfully', [
                'user_id' => $user->getId()->toString(),
                'email'   => $user->getEmail(),
                'ip'      => $clientIp,
            ]);

            return $token;
        } catch (SecurityException $e) {
            // Security throttling triggered
            $this->logger->warning('Authentication blocked by security throttling', [
                'email'           => $email,
                'ip'              => $clientIp,
                'security_type'   => $e->getSecurityType()->value,
                'remaining_delay' => $e->getRemainingDelay(),
            ]);

            throw $e;
        } catch (\InvalidArgumentException $e) {
            // Invalid credentials - record failed attempt
            $this->recordFailedLoginAttempt($email, $clientIp);

            $this->logger->warning('Authentication failed - invalid credentials', [
                'email' => $email,
                'ip'    => $clientIp,
                'error' => $e->getMessage(),
            ]);

            throw new AuthenticationException('Invalid credentials', 'INVALID_CREDENTIALS', $e);
        } catch (\Exception $e) {
            // Unexpected error
            $this->logger->error('Authentication failed - unexpected error', [
                'email' => $email,
                'ip'    => $clientIp,
                'error' => $e->getMessage(),
            ]);

            throw new AuthenticationException('Authentication failed', 'AUTHENTICATION_ERROR', $e);
        }
    }

    /**
     * Validate JWT token and return user data.
     */
    public function validateToken(string $tokenString): JwtToken
    {
        try {
            return $this->jwtService->validateToken($tokenString);
        } catch (\InvalidArgumentException $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage(), 'INVALID_TOKEN', $e);
        }
    }

    /**
     * Extract and validate token from Authorization header.
     */
    public function validateAuthorizationHeader(string $authorizationHeader): JwtToken
    {
        try {
            $tokenString = $this->jwtService->extractTokenFromHeader($authorizationHeader);

            return $this->validateToken($tokenString);
        } catch (\InvalidArgumentException $e) {
            throw new AuthenticationException('Invalid authorization header: ' . $e->getMessage(), 'INVALID_AUTHORIZATION', $e);
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refreshToken(string $tokenString): JwtToken
    {
        try {
            $token = $this->jwtService->validateToken($tokenString);

            return $this->jwtService->refreshToken($token);
        } catch (\InvalidArgumentException $e) {
            throw new AuthenticationException('Cannot refresh token: ' . $e->getMessage(), 'REFRESH_FAILED', $e);
        }
    }

    /**
     * Get user from JWT token.
     */
    public function getUserFromToken(JwtToken $token): ?User
    {
        $userId = $token->getUserId();
        if (!$userId) {
            return null;
        }

        return $this->userService->getUserById($userId);
    }

    /**
     * Logout user (invalidate token on client side).
     * Note: JWT tokens are stateless, so we can't invalidate them server-side
     * without maintaining a blacklist. For now, we just log the logout.
     */
    public function logout(JwtToken $token, string $clientIp): void
    {
        $this->logger->info('User logged out', [
            'user_id' => $token->getUserId(),
            'email'   => $token->getUserEmail(),
            'ip'      => $clientIp,
        ]);

        // In a more advanced implementation, you could:
        // 1. Add token to blacklist
        // 2. Store logout time in database
        // 3. Invalidate refresh tokens
    }

    /**
     * Record failed login attempt for security tracking.
     */
    private function recordFailedLoginAttempt(string $email, string $clientIp): void
    {
        try {
            // This would integrate with your existing SecurityLoginChecker
            // to record the failed attempt for throttling purposes
            $this->securityChecker->recordFailedAttempt($email, $clientIp);
        } catch (\Exception $e) {
            // Don't let security recording failures break authentication
            $this->logger->error('Failed to record login attempt', [
                'email' => $email,
                'ip'    => $clientIp,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
