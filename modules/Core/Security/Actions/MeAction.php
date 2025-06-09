<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions;

use MvaBootstrap\Modules\Core\Security\Services\AuthorizationService;
use MvaBootstrap\Modules\Core\User\Domain\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Me Action.
 *
 * Handles GET /api/auth/me - get current authenticated user information.
 */
final class MeAction
{
    public function __construct(
        private readonly AuthorizationService $authorizationService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Get authenticated user from middleware
        $user = $request->getAttribute('user');
        $token = $request->getAttribute('token');

        if (!$user instanceof User) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'USER_NOT_FOUND',
                    'message' => 'Authenticated user not found',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/MeAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        try {
            // Get user permissions
            $permissions = $this->authorizationService->getUserPermissions($user);

            $responseData = [
                'success' => true,
                'data'    => [
                    'user' => [
                        'id'             => $user->getId()->toString(),
                        'email'          => $user->getEmail(),
                        'name'           => $user->getName(),
                        'role'           => $user->getRole(),
                        'status'         => $user->getStatus(),
                        'email_verified' => $user->isEmailVerified(),
                        'last_login_at'  => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                        'login_count'    => $user->getLoginCount(),
                        'created_at'     => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                        'updated_at'     => $user->getUpdatedAt()->format('Y-m-d H:i:s'),
                    ],
                    'permissions' => $permissions,
                    'token_info'  => [
                        'expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s'),
                        'expires_in' => $token->getTimeToExpiration(),
                        'is_expired' => $token->isExpired(),
                    ],
                    'capabilities' => [
                        'can_access_admin'    => $this->authorizationService->canAccessAdmin($user),
                        'can_manage_users'    => $this->authorizationService->canManageUsers($user),
                        'can_view_statistics' => $this->authorizationService->canViewUserStatistics($user),
                    ],
                ],
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT) ?: "modules/Core/Security/Actions/MeAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INTERNAL_ERROR',
                    'message' => 'Failed to retrieve user information',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/MeAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
