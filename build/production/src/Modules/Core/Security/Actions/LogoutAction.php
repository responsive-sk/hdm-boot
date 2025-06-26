<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Actions;

use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Logout Action (API).
 *
 * Handles API logout requests with JSON responses.
 */
final class LogoutAction
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
            // Get user from request attributes (set by authentication middleware)
            $user = $request->getAttribute('user');
            $token = $request->getAttribute('token');

            if ($user && $token) {
                // Invalidate token
                $this->authenticationService->invalidateToken($token);

                // Log successful logout with proper type checking
                $userId = 'unknown';
                $email = 'unknown';

                if (is_array($user)) {
                    if (isset($user['id'])) {
                        $userIdValue = $user['id'];
                        $userId = is_string($userIdValue) ? $userIdValue : (is_numeric($userIdValue) ? (string) $userIdValue : 'unknown');
                    }
                    if (isset($user['email'])) {
                        $emailValue = $user['email'];
                        $email = is_string($emailValue) ? $emailValue : 'unknown';
                    }
                }

                $this->securityLogger->info('🔐 API logout successful', [
                    'event'   => 'api_logout_success',
                    'user_id' => $userId,
                    'email'   => $email,
                    'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }

            // Return success response
            $responseData = [
                'success' => true,
                'message' => 'Logout successful',
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
            // Log error but still return success for security - log to both loggers
            $this->logger->error('API logout error', [
                'message' => $e->getMessage(),
            ]);

            $this->securityLogger->error('🚨 API logout error', [
                'event'   => 'api_logout_error',
                'message' => $e->getMessage(),
                'ip'      => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // Still return success to prevent information leakage
            $responseData = [
                'success' => true,
                'message' => 'Logout completed',
            ];

            $jsonResponse = json_encode($responseData);
            if ($jsonResponse === false) {
                // Fallback response if JSON encoding fails
                $response->getBody()->write('{"success":true,"message":"Logout completed"}');
            } else {
                $response->getBody()->write($jsonResponse);
            }

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        }
    }
}
