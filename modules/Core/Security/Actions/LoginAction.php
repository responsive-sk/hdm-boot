<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Actions;

use MvaBootstrap\Modules\Core\Security\Exception\AuthenticationException;
use MvaBootstrap\Modules\Core\Security\Exception\SecurityException;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Login Action.
 *
 * Handles POST /api/auth/login - user authentication with JWT token generation.
 */
final class LoginAction
{
    public function __construct(
        private readonly AuthenticationService $authenticationService
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody();
        $clientIp = $this->getClientIp($request);

        // Validate input
        if (!is_array($data)) {
            $data = [];
        }
        $validationErrors = $this->validateInput($data);
        if (!empty($validationErrors)) {
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $validationErrors,
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LoginAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(422);
        }

        try {
            // Authenticate user and generate JWT token
            $token = $this->authenticationService->authenticate(
                email: $data['email'],
                password: $data['password'],
                clientIp: $clientIp
            );

            // Get user data from token
            $user = $this->authenticationService->getUserFromToken($token);

            if ($user === null) {
                throw new \RuntimeException("User not found after authentication");
            }

            $responseData = [
                'success' => true,
                'data'    => [
                    'token'      => $token->getToken(),
                    'token_type' => 'Bearer',
                    'expires_in' => $token->getTimeToExpiration(),
                    'expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s'),
                    'user'       => [
                        'id'             => $user->getId()->toString(),
                        'email'          => $user->getEmail(),
                        'name'           => $user->getName(),
                        'role'           => $user->getRole(),
                        'status'         => $user->getStatus(),
                        'email_verified' => $user->isEmailVerified(),
                    ],
                ],
                'message' => 'Login successful',
            ];

            $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT) ?: "modules/Core/Security/Actions/LoginAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (SecurityException $e) {
            // Security throttling
            $errorData = [
                'success' => false,
                'error'   => $e->toArray(),
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LoginAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(429); // Too Many Requests
        } catch (AuthenticationException $e) {
            // Authentication failed
            $errorData = [
                'success' => false,
                'error'   => $e->toArray(),
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LoginAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Exception $e) {
            // Unexpected error
            $errorData = [
                'success' => false,
                'error'   => [
                    'code'    => 'INTERNAL_ERROR',
                    'message' => 'Login failed due to server error',
                ],
            ];

            $response->getBody()->write(json_encode($errorData) ?: "modules/Core/Security/Actions/LoginAction.php");

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Validate login input.
     * @param array<string, mixed>|null $data
     * @return array<string, array<string>>
     */
    private function validateInput(?array $data): array
    {
        $errors = [];

        if (empty($data)) {
            return ['general' => ['Request body is required']];
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = ['Email is required'];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ['Invalid email format'];
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = ['Password is required'];
        }

        return $errors;
    }

    /**
     * Get client IP address.
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        // Check for IP from various headers (proxy, load balancer, etc.)
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
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to REMOTE_ADDR
        return $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
