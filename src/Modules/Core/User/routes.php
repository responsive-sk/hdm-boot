<?php

declare(strict_types=1);

use HdmBoot\Modules\Core\Security\Middleware\AuthenticationMiddleware;
use HdmBoot\Modules\Core\Security\Middleware\AuthorizationMiddleware;
use HdmBoot\Modules\Core\Security\Services\AuthorizationService;
use HdmBoot\Modules\Core\User\Actions\CreateUserAction;
use HdmBoot\Modules\Core\User\Actions\GetUserAction;
use HdmBoot\Modules\Core\User\Actions\ListUsersAction;
use Slim\App;

/*
 * User Module Routes.
 *
 * Defines all HTTP routes for user management functionality.
 * All routes are protected with JWT authentication.
 */
return function (App $app): void {
    // User API routes (protected with JWT authentication)
    $app->group('/api/users', function ($group) {
        // List users with pagination and filters
        // GET /api/users?page=1&limit=20&role=admin&status=active&search=john
        $group->get('', ListUsersAction::class)
            ->setName('users.list')
            ->add(new AuthorizationMiddleware(
                $this->get(AuthorizationService::class),
                'user.view'
            ));

        // Create new user
        // POST /api/users
        $group->post('', CreateUserAction::class)
            ->setName('users.create')
            ->add(new AuthorizationMiddleware(
                $this->get(AuthorizationService::class),
                'user.create'
            ));

        // Get single user by ID
        // GET /api/users/{id}
        $group->get('/{id}', GetUserAction::class)
            ->setName('users.get')
            ->add(new AuthorizationMiddleware(
                $this->get(AuthorizationService::class),
                'user.view'
            ));

        // Update user (placeholder for future implementation)
        // PUT /api/users/{id}
        $group->put('/{id}', function ($request, $response) {
            $data = [
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_IMPLEMENTED',
                    'message' => 'User update not yet implemented',
                ],
            ];

            $response->getBody()->write(json_encode($data) ?: 'modules/Core/User/routes.php');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(501);
        })->setName('users.update')
          ->add(new AuthorizationMiddleware(
              $this->get(AuthorizationService::class),
              'user.edit'
          ));

        // Delete user (placeholder for future implementation)
        // DELETE /api/users/{id}
        $group->delete('/{id}', function ($request, $response) {
            $data = [
                'success' => false,
                'error'   => [
                    'code'    => 'NOT_IMPLEMENTED',
                    'message' => 'User deletion not yet implemented',
                ],
            ];

            $response->getBody()->write(json_encode($data) ?: 'modules/Core/User/routes.php');

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(501);
        })->setName('users.delete')
          ->add(new AuthorizationMiddleware(
              $this->get(AuthorizationService::class),
              'user.delete'
          ));
    })
    ->add(AuthenticationMiddleware::class); // Require authentication for all user endpoints

    // Admin routes for user management
    $app->group('/api/admin/users', function ($group) {
        // Get user statistics
        // GET /api/admin/users/statistics
        $group->get('/statistics', function ($request, $response) {
            try {
                /** @var Container $container */
                $container = $this->get(\DI\Container::class);
                $userService = $container->get(\HdmBoot\Modules\Core\User\Services\UserService::class);

                $statistics = $userService->getUserStatistics();

                $data = [
                    'success' => true,
                    'data'    => $statistics,
                ];

                $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT) ?: 'modules/Core/User/routes.php');

                return $response->withHeader('Content-Type', 'application/json');
            } catch (\Exception $e) {
                $errorData = [
                    'success' => false,
                    'error'   => [
                        'code'    => 'INTERNAL_ERROR',
                        'message' => 'Failed to retrieve statistics',
                    ],
                ];

                $response->getBody()->write(json_encode($errorData) ?: 'modules/Core/User/routes.php');

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        })->setName('admin.users.statistics');
    })
    ->add(AuthenticationMiddleware::class)
    ->add(new AuthorizationMiddleware(
        $app->getContainer()->get(AuthorizationService::class),
        'admin.users'
    ));

    // Authentication routes are now handled by the Security module
};
