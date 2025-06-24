<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Actions;

use HdmBoot\Modules\Core\Security\Exceptions\AuthenticationException;
use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Refresh Token Action (API).
 *
 * Handles JWT token refresh requests.
 */
final class RefreshTokenAction
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LoggerInterface $logger,
        private readonly LoggerInterface $securityLogger
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // Get refresh token from request
            $data = (array) $request->getParsedBody();
            $refreshTokenString = is_string($data['refresh_token'] ?? null) ? $data['refresh_token'] : '';

            if (empty($refreshTokenString)) {
                throw new AuthenticationException('Refresh token is required');
            }

            // Validate refresh token string first
            $user = $this->authenticationService->validateToken($refreshTokenString);
            if (!$user) {
                throw new AuthenticationException('Invalid refresh token');
            }

            // Create new token for the user
            $newTokenString = $this->authenticationService->generateToken($user);
            $newToken = \HdmBoot\Modules\Core\Security\Domain\ValueObjects\JwtToken::fromString($newTokenString);

            // Get user data from new token
            $userData = $newToken->getPayload();

            // Log successful token refresh
            $this->securityLogger->info('🔐 Token refresh successful', [
                'event'   => 'token_refresh_success',
                'user_id' => $newToken->getUserId() ?? 'unknown',
                'email'   => $newToken->getUserEmail() ?? 'unknown',
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // Return new token
            $responseData = [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data'    => [
                    'token'      => $newToken->getToken(),
                    'expires_at' => $newToken->getExpiresAt()->format('Y-m-d H:i:s'),
                    'expires_in' => $newToken->getTimeToExpiration(),
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
        } catch (AuthenticationException $e) {
            // Invalid or expired refresh token
            $this->securityLogger->warning('🚨 Token refresh failed', [
                'event'   => 'token_refresh_failed',
                'message' => $e->getMessage(),
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $errorData = [
                'success'    => false,
                'message'    => 'Invalid or expired refresh token',
                'error_code' => 'INVALID_REFRESH_TOKEN',
            ];

            $jsonResponse = json_encode($errorData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"success":false,"message":"Invalid refresh token"}';
            }

            $response->getBody()->write($jsonResponse);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Exception $e) {
            // General errors - log to both loggers
            $this->logger->error('Token refresh system error', [
                'message' => $e->getMessage(),
            ]);

            $this->securityLogger->error('🚨 Token refresh system error', [
                'event'   => 'token_refresh_system_error',
                'message' => $e->getMessage(),
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $errorData = [
                'success'    => false,
                'message'    => 'Token refresh failed',
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
