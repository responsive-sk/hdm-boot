<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions;

use MvaBootstrap\Modules\Core\Security\Domain\ValueObjects\JwtToken;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Logout Action.
 *
 * Handles POST /api/auth/logout - user logout (JWT token invalidation).
 */
final class LogoutAction
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Get authenticated user and token from middleware
        $token = $request->getAttribute('token');
        $user = $request->getAttribute('user');
        $clientIp = $this->getClientIp($request);

        if (!$token instanceof JwtToken) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INVALID_TOKEN',
                    'message' => 'Invalid or missing token',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LogoutAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        try {
            // Perform logout (mainly for logging purposes with JWT)
            $this->authenticationService->logout($token, $clientIp);

            $responseData = [
                'success' => true,
                'message' => 'Logout successful',
                'data'    => [
                    'logged_out_at' => date('Y-m-d H:i:s'),
                ],
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT) ?: "modules/Core/Security/Actions/LogoutAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'LOGOUT_ERROR',
                    'message' => 'Logout failed',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LogoutAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get client IP address.
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $serverParams = $request->getServerParams();
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
