<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Psr\Log\LoggerInterface;

/*
 * User Module Configuration.
 */
return [
    // === MODULE METADATA ===

    'name'        => 'User',
    'version'     => '1.0.0',
    'description' => 'User management module with CRUD operations, authentication, and profile management',
    'author'      => 'MVA Bootstrap Team',
    'license'     => 'MIT',

    // === MODULE DEPENDENCIES ===

    'dependencies' => [
        // No module dependencies - User is a core module
    ],

    // === MODULE SETTINGS ===

    'settings' => [
        'enabled'                     => true,
        'registration_enabled'        => true,
        'email_verification_required' => false,
        'password_min_length'         => 8,
        'session_timeout'             => 3600,
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // User Repository Interface
        UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
            return new SqliteUserRepository(
                $container->get(\PDO::class)
            );
        },

        // User Service
        UserService::class => function (Container $container): UserService {
            return new UserService(
                $container->get(UserRepositoryInterface::class),
                $container->get(LoggerInterface::class)
            );
        },

        // Profile Page Action
        ProfilePageAction::class => function (Container $container): ProfilePageAction {
            return new ProfilePageAction(
                $container->get(\MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer::class),
                $container->get(\Odan\Session\SessionInterface::class),
                $container->get(UserService::class)
            );
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        UserRepositoryInterface::class => UserService::class,
    ],

    // === ROUTES ===

    'routes' => [
        'GET /profile' => ProfilePageAction::class,
    ],

    // === EVENT SYSTEM ===

    'published_events' => [
        'user.created' => 'Fired when a new user is created',
        'user.updated' => 'Fired when user profile is updated',
        'user.deleted' => 'Fired when user account is deleted',
        'user.login'   => 'Fired when user logs in',
        'user.logout'  => 'Fired when user logs out',
    ],

    'event_subscriptions' => [
        'security.login_success' => [ProfilePageAction::class, 'handleLoginSuccess'],
    ],

    // === API ENDPOINTS ===

    'api_endpoints' => [
        'GET /api/users'         => 'List all users',
        'GET /api/users/{id}'    => 'Get specific user',
        'POST /api/users'        => 'Create new user',
        'PUT /api/users/{id}'    => 'Update user',
        'DELETE /api/users/{id}' => 'Delete user',
        'GET /profile'           => 'User profile page',
    ],

    // === PERMISSIONS ===

    'permissions' => [
        'user.read'     => 'Read user data',
        'user.write'    => 'Create and update users',
        'user.delete'   => 'Delete users',
        'user.admin'    => 'Administrative access to user management',
        'profile.read'  => 'Read own profile',
        'profile.write' => 'Update own profile',
    ],

    // === DATABASE ===

    'database_tables' => [
        'users',
        'user_sessions',
        'user_preferences',
    ],

    // === MODULE STATUS ===

    'status' => [
        'implemented' => [
            'User CRUD operations',
            'Profile management',
            'Session handling',
            'SQLite repository',
        ],

        'planned' => [
            'Email verification',
            'Password reset',
            'User roles and permissions',
            'User activity logging',
        ],
    ],
];
