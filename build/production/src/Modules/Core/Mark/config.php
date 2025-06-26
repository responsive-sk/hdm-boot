<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Database\MarkSqliteDatabaseManager;
use HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginPageAction;
use HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginSubmitAction;
use HdmBoot\Modules\Core\Mark\Repository\MarkRepositoryInterface;
use HdmBoot\Modules\Core\Mark\Repository\SqliteMarkRepository;
use HdmBoot\Modules\Core\Mark\Services\MarkAuthenticationService;
use HdmBoot\SharedKernel\Database\DatabaseManagerFactory;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Mark Module Configuration.
 * 
 * Configures the Mark system for super user management.
 * Uses mark.db database exclusively.
 */
return [
    // === MODULE METADATA ===

    'name'        => 'Mark',
    'version'     => '1.0.0',
    'description' => 'Mark system for super user management and administration',
    'author'      => 'HDM Boot Team',
    'license'     => 'MIT',

    // === MODULE DEPENDENCIES ===

    'dependencies' => [
        // No module dependencies - Mark is a core module
    ],

    // === MODULE ROUTES ===

    'routes' => [
        // Mark login page
        ['GET', '/mark', MarkLoginPageAction::class],
        
        // Mark login form submission
        ['POST', '/mark/login', MarkLoginSubmitAction::class],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // Mark Repository Interface
        MarkRepositoryInterface::class => function (Container $container): MarkRepositoryInterface {
            // Use MarkSqliteDatabaseManager for mark.db
            $paths = $container->get(Paths::class);
            $factory = new DatabaseManagerFactory($paths);
            $markManager = $factory->createMarkManager();
            
            return new SqliteMarkRepository(
                $markManager->getConnection(),
                $container->get(LoggerInterface::class)
            );
        },

        // Mark Authentication Service
        MarkAuthenticationService::class => function (Container $container): MarkAuthenticationService {
            return new MarkAuthenticationService(
                $container->get(MarkRepositoryInterface::class),
                $container->get(LoggerInterface::class)
            );
        },

        // Mark Login Page Action
        MarkLoginPageAction::class => function (Container $container): MarkLoginPageAction {
            return new MarkLoginPageAction();
        },

        // Mark Login Submit Action
        MarkLoginSubmitAction::class => function (Container $container): MarkLoginSubmitAction {
            return new MarkLoginSubmitAction(
                $container->get(MarkAuthenticationService::class),
                $container->get(LoggerInterface::class)
            );
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        MarkRepositoryInterface::class => MarkAuthenticationService::class,
    ],

    // === MODULE EVENTS ===

    'events' => [
        // Mark-specific events can be added here
    ],

    // === MODULE MIDDLEWARE ===

    'middleware' => [
        // Mark-specific middleware can be added here
    ],

    // === MODULE PERMISSIONS ===

    'permissions' => [
        'mark.login' => 'Allow mark system login',
        'mark.dashboard' => 'Access mark dashboard',
        'mark.users' => 'Manage mark users',
        'mark.system' => 'System administration',
    ],

    // === MODULE SETTINGS ===

    'settings' => [
        'session_timeout' => 24 * 60 * 60, // 24 hours
        'max_login_attempts' => 5,
        'lockout_duration' => 15 * 60, // 15 minutes
    ],
];
