<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions;

use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Me Action (API)
 *
 * Returns current authenticated user information.
 */
final class MeAction
{
    public function __construct(
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // Get user from request attributes (set by authentication middleware)
            $user = $request->getAttribute('user');
            $token = $request->getAttribute('token');

            if (!$user) {
                $errorData = [
                    'success' => false,
                    'message' => 'User not authenticated',
                    'error_code' => 'NOT_AUTHENTICATED',
                ];

                $jsonResponse = json_encode($errorData);
                if ($jsonResponse === false) {
                    $jsonResponse = '{"success":false,"message":"Not authenticated"}';
                }

                $response->getBody()->write($jsonResponse);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }

            // Get fresh user data from database with proper type checking
            $userId = null;
            if (is_array($user) && isset($user['id'])) {
                $userIdValue = $user['id'];
                $userId = is_string($userIdValue) ? $userIdValue : (is_numeric($userIdValue) ? (string) $userIdValue : null);
                if ($userId) {
                    $freshUser = $this->userService->getUserById($userId);
                    if ($freshUser) {
                        $user = $freshUser;
                    }
                }
            }

            // Prepare response data with proper type checking
            $userData = is_array($user) ? $user : [];
            $tokenInfo = null;

            // Handle token info safely
            if ($token && is_object($token)) {
                try {
                    $expiresAt = method_exists($token, 'getExpiresAt') ? $token->getExpiresAt() : null;
                    $tokenInfo = [
                        'expires_at' => ($expiresAt instanceof \DateTimeInterface)
                            ? $expiresAt->format('Y-m-d H:i:s')
                            : null,
                        'expires_in' => method_exists($token, 'getTimeToExpiration')
                            ? $token->getTimeToExpiration()
                            : null,
                        'is_expired' => method_exists($token, 'isExpired')
                            ? $token->isExpired()
                            : null,
                    ];
                } catch (\Exception $e) {
                    // Token info extraction failed, continue without it
                    $tokenInfo = null;
                }
            }

            $responseData = [
                'success' => true,
                'message' => 'User information retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $userData['id'] ?? '',
                        'email' => $userData['email'] ?? '',
                        'name' => $userData['name'] ?? '',
                        'role' => $userData['role'] ?? '',
                        'status' => $userData['status'] ?? '',
                        'created_at' => $userData['created_at'] ?? null,
                        'updated_at' => $userData['updated_at'] ?? null,
                    ],
                    'token_info' => $tokenInfo,
                ],
            ];

            $jsonResponse = json_encode($responseData);
            if ($jsonResponse === false) {
                throw new \RuntimeException('Failed to encode JSON response');
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            // Log error
            $this->logger->error('Error in MeAction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Failed to retrieve user information',
                'error_code' => 'SYSTEM_ERROR',
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"System error"}';
            }

            $response->getBody()->write($jsonResponse);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
