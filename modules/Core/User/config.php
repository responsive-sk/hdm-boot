<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\User\Actions\CreateUserAction;
use MvaBootstrap\Modules\Core\User\Actions\GetUserAction;
use MvaBootstrap\Modules\Core\User\Actions\ListUsersAction;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;

/*
 * User Module Configuration.
 *
 * Defines services, dependencies, and configuration for the User module.
 */
return [
    'name'        => 'User',
    'version'     => '1.0.0',
    'description' => 'Core user management module with authentication support',

    // Service definitions for DI container
    'services' => [
        // Repository
        UserRepositoryInterface::class => function (Container $c): UserRepositoryInterface {
            return new SqliteUserRepository($c->get(PDO::class));
        },

        // Service
        UserService::class => function (Container $c): UserService {
            return new UserService($c->get(UserRepositoryInterface::class));
        },

        // Actions
        ListUsersAction::class => function (Container $c): ListUsersAction {
            return new ListUsersAction($c->get(UserService::class));
        },

        GetUserAction::class => function (Container $c): GetUserAction {
            return new GetUserAction($c->get(UserService::class));
        },

        CreateUserAction::class => function (Container $c): CreateUserAction {
            return new CreateUserAction($c->get(UserService::class));
        },
    ],

    // Module dependencies (none for User module as it's a core module)
    'dependencies' => [],

    // Module settings
    'settings' => [
        'user' => [
            'password_min_length'         => 8,
            'password_require_uppercase'  => true,
            'password_require_lowercase'  => true,
            'password_require_numbers'    => true,
            'password_require_symbols'    => false,
            'email_verification_required' => false,
            'default_role'                => 'user',
            'allowed_roles'               => ['user', 'editor', 'admin'],
            'allowed_statuses'            => ['active', 'inactive', 'suspended', 'pending'],
        ],
        'pagination' => [
            'default_limit' => 20,
            'max_limit'     => 100,
        ],
    ],

    // Database tables that this module manages
    'database_tables' => [
        'users',
    ],

    // Permissions defined by this module
    'permissions' => [
        'user.view'       => 'View user information',
        'user.create'     => 'Create new users',
        'user.edit'       => 'Edit user information',
        'user.delete'     => 'Delete users',
        'user.manage'     => 'Full user management access',
        'user.statistics' => 'View user statistics',
    ],

    // Events that this module can emit (for future event system)
    'events' => [
        'user.created'          => 'Fired when a new user is created',
        'user.updated'          => 'Fired when a user is updated',
        'user.deleted'          => 'Fired when a user is deleted',
        'user.activated'        => 'Fired when a user is activated',
        'user.deactivated'      => 'Fired when a user is deactivated',
        'user.suspended'        => 'Fired when a user is suspended',
        'user.email_verified'   => 'Fired when user email is verified',
        'user.password_changed' => 'Fired when user password is changed',
        'user.login'            => 'Fired when user logs in',
    ],

    // API endpoints provided by this module
    'api_endpoints' => [
        'GET /api/users'                  => 'List users with pagination and filters',
        'POST /api/users'                 => 'Create new user',
        'GET /api/users/{id}'             => 'Get user by ID',
        'PUT /api/users/{id}'             => 'Update user (planned)',
        'DELETE /api/users/{id}'          => 'Delete user (planned)',
        'POST /api/users/{id}/activate'   => 'Activate user (planned)',
        'POST /api/users/{id}/deactivate' => 'Deactivate user (planned)',
        'POST /api/users/{id}/suspend'    => 'Suspend user (planned)',
        'GET /api/admin/users/statistics' => 'Get user statistics',
    ],

    // Module status
    'status' => [
        'implemented' => [
            'User entity with rich domain logic',
            'Repository pattern with SQLite implementation',
            'User service with business logic',
            'Basic CRUD operations',
            'User listing with pagination and filters',
            'User creation with validation',
            'User statistics',
            'Password hashing with Argon2ID',
            'Email verification token generation',
            'Password reset token generation',
        ],
        'planned' => [
            'User update action',
            'User deletion action',
            'User status management actions',
            'Password change action',
            'Email verification action',
            'User role management',
            'User search functionality',
            'User export functionality',
            'User import functionality',
            'User activity logging',
        ],
    ],
];
