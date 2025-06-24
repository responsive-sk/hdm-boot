<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Security\Actions\LoginAction;
use HdmBoot\Modules\Core\Security\Actions\LogoutAction;
use HdmBoot\Modules\Core\Security\Actions\MeAction;
use HdmBoot\Modules\Core\Security\Actions\RefreshTokenAction;
use HdmBoot\Modules\Core\Security\Middleware\AuthenticationMiddleware;
use HdmBoot\Modules\Core\Security\Services\SecurityLoginChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

/*
 * Security Module Routes.
 *
 * Defines all HTTP routes for authentication and authorization functionality.
 */
return function (App $app): void {
    // Public authentication routes (no authentication required)
    $app->group('/api/auth', function (RouteCollectorProxyInterface $group): void {
        // Login endpoint
        // POST /api/auth/login
        $group->post('/login', LoginAction::class)
            ->setName('auth.login');

        // Token refresh endpoint (requires valid token)
        // POST /api/auth/refresh
        $group->post('/refresh', RefreshTokenAction::class)
            ->setName('auth.refresh')
            ->add(AuthenticationMiddleware::class);

        // Get current user info (requires authentication)
        // GET /api/auth/me
        $group->get('/me', MeAction::class)
            ->setName('auth.me')
            ->add(AuthenticationMiddleware::class);

        // Logout endpoint (requires authentication)
        // POST /api/auth/logout
        $group->post('/logout', LogoutAction::class)
            ->setName('auth.logout')
            ->add(AuthenticationMiddleware::class);
    });

    // Security administration routes (admin only)
    $app->group('/api/admin/security', function (RouteCollectorProxyInterface $group) use ($app): void {
        // Get security statistics
        // GET /api/admin/security/statistics
        $group->get('/statistics', function (ServerRequestInterface $request, ResponseInterface $response) use ($app): ResponseInterface {
            /** @var Container $container */
            $container = $app->getContainer();
            /** @var SecurityLoginChecker $securityChecker */
            $securityChecker = $container->get(SecurityLoginChecker::class);

            try {
                /** @var array<string, mixed> $statistics */
                $statistics = $securityChecker->getLoginStatistics();

                $data = [
                    'success' => true,
                    'data'    => $statistics,
                ];

                $jsonData = json_encode($data, JSON_PRETTY_PRINT);
                if ($jsonData === false) {
                    throw new \RuntimeException('Failed to encode JSON response');
                }
                $response->getBody()->write($jsonData);

                return $response->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Failed to retrieve security statistics',
                    ],
                ];

                $jsonErrorData = json_encode($errorData);
                if ($jsonErrorData === false) {
                    $jsonErrorData = '{"success":false,"error":{"code":"JSON_ERROR","message":"Failed to encode error response"}}';
                }
                $response->getBody()->write($jsonErrorData);

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        })->setName('admin.security.statistics');

        // Clean old security logs
        // POST /api/admin/security/cleanup
        $group->post('/cleanup', function (ServerRequestInterface $request, ResponseInterface $response) use ($app): ResponseInterface {
            /** @var Container $container */
            $container = $app->getContainer();
            /** @var SecurityLoginChecker $securityChecker */
            $securityChecker = $container->get(SecurityLoginChecker::class);

            try {
                /** @var int $deletedCount */
                $deletedCount = $securityChecker->cleanOldAttempts();

                $data = [
                    'success' => true,
                    'data'    => [
                        'deleted_records' => $deletedCount,
                        'cleaned_at'      => date('Y-m-d H:i:s'),
                    ],
                    'message' => "Cleaned {$deletedCount} old security records",
                ];

                $jsonData = json_encode($data, JSON_PRETTY_PRINT);
                if ($jsonData === false) {
                    throw new \RuntimeException('Failed to encode JSON response');
                }
                $response->getBody()->write($jsonData);

                return $response->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Failed to clean security logs',
                    ],
                ];

                $jsonErrorData = json_encode($errorData);
                if ($jsonErrorData === false) {
                    $jsonErrorData = '{"success":false,"error":{"code":"JSON_ERROR","message":"Failed to encode error response"}}';
                }
                $response->getBody()->write($jsonErrorData);

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        })->setName('admin.security.cleanup');
    })
    ->add(AuthenticationMiddleware::class);

    // Test endpoint for JWT validation (development only)
    if (($_ENV['APP_ENV'] ?? 'dev') !== 'prod') {
        $app->get('/api/test/auth', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            /** @var array<string, mixed>|null $user */
            $user = $request->getAttribute('user');
            /** @var array<string, mixed>|null $token */
            $token = $request->getAttribute('token');

            if (!is_array($user) || !is_array($token)) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'MISSING_AUTH_DATA',
                        'message' => 'User or token data missing from request',
                    ],
                ];

                $jsonErrorData = json_encode($errorData);
                if ($jsonErrorData === false) {
                    $jsonErrorData = '{"success":false,"error":{"code":"JSON_ERROR","message":"Failed to encode error response"}}';
                }
                $response->getBody()->write($jsonErrorData);

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            $data = [
                'success' => true,
                'message' => 'Authentication test successful',
                'data'    => [
                    'authenticated'    => true,
                    'user_id'          => $user['id'] ?? 'unknown',
                    'user_email'       => $user['email'] ?? 'unknown',
                    'user_role'        => $user['role'] ?? 'unknown',
                    'token_expires_in' => $token['expires_in'] ?? 'unknown',
                    'token_expires_at' => $token['expires_at'] ?? 'unknown',
                ],
            ];

            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
            if ($jsonData === false) {
                throw new \RuntimeException('Failed to encode JSON response');
            }
            $response->getBody()->write($jsonData);

            return $response->withHeader('Content-Type', 'application/json');
        })
        ->setName('test.auth')
        ->add(AuthenticationMiddleware::class);
    }
};
