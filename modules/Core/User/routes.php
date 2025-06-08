<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Core\User\Actions\CreateUserAction;
use MvaBootstrap\Modules\Core\User\Actions\GetUserAction;
use MvaBootstrap\Modules\Core\User\Actions\ListUsersAction;
use Slim\App;

/**
 * User Module Routes.
 *
 * Defines all HTTP routes for user management functionality.
 */
return function (App $app): void {
    $app->group('/api/users', function ($group) {
        // List users with pagination and filters
        // GET /api/users?page=1&limit=20&role=admin&status=active&search=john
        $group->get('', ListUsersAction::class)
            ->setName('users.list');

        // Create new user
        // POST /api/users
        $group->post('', CreateUserAction::class)
            ->setName('users.create');

        // Get single user by ID
        // GET /api/users/{id}
        $group->get('/{id}', GetUserAction::class)
            ->setName('users.get');

        // Update user (to be implemented)
        // PUT /api/users/{id}
        // $group->put('/{id}', UpdateUserAction::class)
        //     ->setName('users.update');

        // Delete user (to be implemented)
        // DELETE /api/users/{id}
        // $group->delete('/{id}', DeleteUserAction::class)
        //     ->setName('users.delete');

        // User status management (to be implemented)
        // POST /api/users/{id}/activate
        // $group->post('/{id}/activate', ActivateUserAction::class)
        //     ->setName('users.activate');

        // POST /api/users/{id}/deactivate
        // $group->post('/{id}/deactivate', DeactivateUserAction::class)
        //     ->setName('users.deactivate');

        // POST /api/users/{id}/suspend
        // $group->post('/{id}/suspend', SuspendUserAction::class)
        //     ->setName('users.suspend');

        // Password management (to be implemented)
        // POST /api/users/{id}/change-password
        // $group->post('/{id}/change-password', ChangePasswordAction::class)
        //     ->setName('users.change-password');

        // Email verification (to be implemented)
        // POST /api/users/{id}/send-verification
        // $group->post('/{id}/send-verification', SendEmailVerificationAction::class)
        //     ->setName('users.send-verification');

        // GET /api/users/verify-email/{token}
        // $group->get('/verify-email/{token}', VerifyEmailAction::class)
        //     ->setName('users.verify-email');
    });

    // User statistics endpoint (admin only)
    $app->get('/api/admin/users/statistics', function ($request, $response) {
        $container = $this->get(\DI\Container::class);
        $userService = $container->get(\MvaBootstrap\Modules\Core\User\Services\UserService::class);

        try {
            $statistics = $userService->getStatistics();

            $data = [
                'success' => true,
                'data' => $statistics,
            ];

            $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorData = [
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Failed to retrieve statistics',
                ],
            ];

            $response->getBody()->write(json_encode($errorData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    })->setName('admin.users.statistics');

    // Authentication routes (to be moved to Security module later)
    $app->group('/api/auth', function ($group) {
        // Login endpoint (to be implemented in Security module)
        // POST /api/auth/login
        $group->post('/login', function ($request, $response) {
            $data = [
                'success' => false,
                'error' => [
                    'code' => 'NOT_IMPLEMENTED',
                    'message' => 'Authentication not yet implemented. Will be available in Security module.',
                ],
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(501);
        })->setName('auth.login');

        // Logout endpoint (to be implemented in Security module)
        // POST /api/auth/logout
        $group->post('/logout', function ($request, $response) {
            $data = [
                'success' => false,
                'error' => [
                    'code' => 'NOT_IMPLEMENTED',
                    'message' => 'Authentication not yet implemented. Will be available in Security module.',
                ],
            ];

            $response->getBody()->write(json_encode($data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(501);
        })->setName('auth.logout');
    });
};
