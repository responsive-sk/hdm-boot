<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Core\Security\Actions\LoginAction;
use MvaBootstrap\Modules\Core\Security\Actions\LogoutAction;
use MvaBootstrap\Modules\Core\Security\Actions\MeAction;
use MvaBootstrap\Modules\Core\Security\Actions\RefreshTokenAction;
use MvaBootstrap\Modules\Core\Security\Middleware\AuthenticationMiddleware;
use MvaBootstrap\Modules\Core\Security\Middleware\AuthorizationMiddleware;
use Slim\App;

/*
 * Security Module Routes.
 *
 * Defines all HTTP routes for authentication and authorization functionality.
 */
return function (App $app): void {
    // Public authentication routes (no authentication required)
    $app->group('/api/auth', function ($group) {
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
    $app->group('/api/admin/security', function ($group) {
        // Get security statistics
        // GET /api/admin/security/statistics
        $group->get('/statistics', function ($request, $response) {
            /** @var Container $container */
            $container = $this->get(\DI\Container::class);
            $securityChecker = $container->get(\MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker::class);

            try {
                $statistics = $securityChecker->getLoginStatistics();

                $data = [
                    'success' => true,
                    'data'    => $statistics,
                ];

                $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: 'modules/Core/Security/routes.php');

                return $response->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Failed to retrieve security statistics',
                    ],
                ];

                $response->getBody()->write(json_encode($errorData) ?: 'modules/Core/Security/routes.php');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        })->setName('admin.security.statistics');

        // Clean old security logs
        // POST /api/admin/security/cleanup
        $group->post('/cleanup', function ($request, $response) {
            /** @var Container $container */
            $container = $this->get(\DI\Container::class);
            $securityChecker = $container->get(\MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker::class);

            try {
                $deletedCount = $securityChecker->cleanOldAttempts();

                $data = [
                    'success' => true,
                    'data'    => [
                        'deleted_records' => $deletedCount,
                        'cleaned_at'      => date('Y-m-d H:i:s'),
                    ],
                    'message' => "Cleaned {$deletedCount} old security records",
                ];

                $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: 'modules/Core/Security/routes.php');

                return $response->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Failed to clean security logs',
                    ],
                ];

                $response->getBody()->write(json_encode($errorData) ?: 'modules/Core/Security/routes.php');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        })->setName('admin.security.cleanup');
    })
    ->add(AuthenticationMiddleware::class)
    ->add(new AuthorizationMiddleware(
        $app->getContainer()->get(\MvaBootstrap\Modules\Core\Security\Services\AuthorizationService::class),
        'admin.security'
    ));

    // Test endpoint for JWT validation (development only)
    if (($_ENV['APP_ENV'] ?? 'dev') !== 'prod') {
        $app->get('/api/test/auth', function ($request, $response) {
            $user = $request->getAttribute('user');
            $token = $request->getAttribute('token');

            $data = [
                'success' => true,
                'message' => 'Authentication test successful',
                'data'    => [
                    'authenticated'    => true,
                    'user_id'          => $user->getId()->toString(),
                    'user_email'       => $user->getEmail(),
                    'user_role'        => $user->getRole(),
                    'token_expires_in' => $token->getTimeToExpiration(),
                    'token_expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s'),
                ],
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: 'modules/Core/Security/routes.php');

            return $response->withHeader('Content-Type', 'application/json');
        })
        ->setName('test.auth')
        ->add(AuthenticationMiddleware::class);
    }
};
