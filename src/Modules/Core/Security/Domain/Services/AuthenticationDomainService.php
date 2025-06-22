<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Domain\Services;

use HdmBoot\Modules\Core\Security\Domain\DTOs\LoginRequest;
use HdmBoot\Modules\Core\Security\Domain\DTOs\LoginResult;
use HdmBoot\Modules\Core\Security\Services\SecurityLoginChecker;
use HdmBoot\Modules\Core\User\Services\UserService;
use Psr\Log\LoggerInterface;

/**
 * Authentication Domain Service.
 *
 * Pure business logic for authentication without HTTP dependencies.
 * This service contains the core authentication logic separated from the framework.
 */
final class AuthenticationDomainService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly SecurityLoginChecker $securityChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handle user login with pure business logic.
     */
    public function handleLogin(LoginRequest $loginRequest): LoginResult
    {
        try {
            // Validate input
            $validationErrors = $loginRequest->validate();
            if (!empty($validationErrors)) {
                $this->logger->warning('Login validation failed', [
                    'errors'  => $validationErrors,
                    'request' => $loginRequest->toLogArray(),
                ]);

                return LoginResult::failure(
                    'Invalid input data',
                    'VALIDATION_ERROR',
                    ['validation_errors' => $validationErrors]
                );
            }

            // Security check before authentication attempt
            $this->securityChecker->checkLoginSecurity($loginRequest->email, $loginRequest->clientIp);

            // Authenticate user
            $user = $this->userService->authenticate($loginRequest->email, $loginRequest->password);

            if (!$user) {
                // Record failed attempt
                $this->securityChecker->recordFailedAttempt($loginRequest->email, $loginRequest->clientIp);

                $this->logger->warning('Authentication failed - invalid credentials', [
                    'request' => $loginRequest->toLogArray(),
                ]);

                return LoginResult::failure(
                    'Invalid email or password',
                    'INVALID_CREDENTIALS'
                );
            }

            // Record successful attempt
            $this->securityChecker->recordSuccessfulAttempt($loginRequest->email, $loginRequest->clientIp);

            // Log successful authentication
            $this->logger->info('User authenticated successfully', [
                'user_id' => $user['id'],
                'email'   => $user['email'],
                'request' => $loginRequest->toLogArray(),
            ]);

            return LoginResult::success(
                user: $user,
                metadata: [
                    'login_time' => time(),
                    'client_ip'  => $loginRequest->clientIp,
                    'user_agent' => $loginRequest->userAgent,
                ]
            );
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logger->error('Authentication domain service error', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $loginRequest->toLogArray(),
            ]);

            return LoginResult::failure(
                'An unexpected error occurred during authentication',
                'INTERNAL_ERROR'
            );
        }
    }

    /**
     * Handle user logout with pure business logic.
     *
     * @param array<string, mixed> $user
     */
    public function handleLogout(array $user, string $clientIp): void
    {
        try {
            $this->logger->info('User logged out', [
                'user_id'   => $user['id'],
                'email'     => $user['email'],
                'client_ip' => $clientIp,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Logout domain service error', [
                'error'   => $e->getMessage(),
                'user_id' => $user['id'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Validate user session with pure business logic.
     *
     * @return array<string, mixed>|null
     */
    public function validateUserSession(string $userId): ?array
    {
        try {
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                $this->logger->warning('Session validation failed: user not found', [
                    'user_id' => $userId,
                ]);

                return null;
            }

            if (!isset($user['status']) || $user['status'] !== 'active') {
                $this->logger->warning('Session validation failed: user not active', [
                    'user_id' => $userId,
                    'status'  => $user['status'] ?? 'unknown',
                ]);

                return null;
            }

            return $user;
        } catch (\Exception $e) {
            $this->logger->error('Session validation error', [
                'error'   => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return null;
        }
    }

    /**
     * Check if user can perform action with pure business logic.
     *
     * @param array<string, mixed> $user
     */
    public function canUserPerformAction(array $user, string $action): bool
    {
        try {
            // Basic permission check based on user role
            return $this->userService->hasPermission($user, $action);
        } catch (\Exception $e) {
            $this->logger->error('Permission check error', [
                'error'   => $e->getMessage(),
                'user_id' => $user['id'] ?? 'unknown',
                'action'  => $action,
            ]);

            return false;
        }
    }
}
