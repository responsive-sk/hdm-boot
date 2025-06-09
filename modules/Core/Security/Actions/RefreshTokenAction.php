<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions;

use MvaBootstrap\Modules\Core\Security\Domain\ValueObjects\JwtToken;
use MvaBootstrap\Modules\Core\Security\Exception\AuthenticationException;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Refresh Token Action.
 *
 * Handles POST /api/auth/refresh - JWT token refresh.
 */
final class RefreshTokenAction
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Get current token from middleware
        $currentToken = $request->getAttribute('token');

        if (!$currentToken instanceof JwtToken) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INVALID_TOKEN',
                    'message' => 'Invalid or missing token',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/RefreshTokenAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        try {
            // Refresh the token
            $newToken = $this->authenticationService->refreshToken($currentToken->getToken());

            // Get user data
            $user = $this->authenticationService->getUserFromToken($newToken);

            if ($user === null) {
                throw new \RuntimeException("User not found for token refresh");
            }

            $responseData = [
                'success' => true,
                'data'    => [
                    'token'      => $newToken->getToken(),
                    'token_type' => 'Bearer',
                    'expires_in' => $newToken->getTimeToExpiration(),
                    'expires_at' => $newToken->getExpiresAt()->format('Y-m-d H:i:s'),
                    'user'       => [
                        'id'             => $user->getId()->toString(),
                        'email'          => $user->getEmail(),
                        'name'           => $user->getName(),
                        'role'           => $user->getRole(),
                        'status'         => $user->getStatus(),
                        'email_verified' => $user->isEmailVerified(),
                    ],
                ],
                'message' => 'Token refreshed successfully',
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT) ?: "modules/Core/Security/Actions/RefreshTokenAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (AuthenticationException $e) {
            $errorData = [
                'success' => false,
                'error'   => $e->toArray(),
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/RefreshTokenAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'REFRESH_ERROR',
                    'message' => 'Token refresh failed',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/RefreshTokenAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
