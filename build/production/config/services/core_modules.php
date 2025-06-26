<?php

declare(strict_types=1);

use DI\Container;
use Psr\Log\LoggerInterface;

/*
 * Core Modules Service Configuration.
 *
 * Configuration for Core modules that were moved from Shared Kernel.
 */
return [
    // === ERROR HANDLING MODULE ===

    // Error Handler Middleware (moved from Shared)
    \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware(
            $container->get(\HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class),
            $container->get(LoggerInterface::class),
            false // displayErrorDetails - set to true for development
        );
    },

    // Error Response Handler (moved from Shared)
    \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class => \DI\autowire(),

    // === MONITORING MODULE ===

    // Health Check Manager (moved from Shared)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager::class => \DI\autowire(),

    // Health Check Action (moved from Shared)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\Actions\HealthCheckAction::class => \DI\autowire(),

    // Performance Monitor (moved from Shared)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor(
            $container->get('logger.performance')
        );
    },

    // Monitoring Bootstrap (moved from Shared)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\Bootstrap\MonitoringBootstrap::class => \DI\autowire(),

    // === DOCUMENTATION MODULE ===

    // Documentation Viewer Action (moved from Shared)
    \HdmBoot\Modules\Core\Documentation\Infrastructure\Actions\DocsViewerAction::class => \DI\autowire(),

    // === SHARED KERNEL (minimal) ===

    // Secure Path Helper (stays in Shared - truly universal)
    \HdmBoot\Shared\Helpers\SecurePathHelper::class => \DI\autowire(),

    // === USER MODULE ===

    // SQLite PDO connection for Users module
    'users.pdo' => function (Container $container) {
        $paths = $container->get('settings')['paths'];
        $dbPath = $paths['storage'] . '/users.db';
        $dsn = 'sqlite:' . $dbPath;

        // Ensure storage directory exists
        $storageDir = dirname($dbPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0o777, true);
        }

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    },

    // User Repository using dedicated SQLite database
    \HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\User\Repository\SqliteUserRepository(
            $container->get('users.pdo')
        );
    },

    // === MARK MODULE ===

    // Mark Repository Interface
    \HdmBoot\Modules\Core\Mark\Repository\MarkRepositoryInterface::class => function (Container $container) {
        $paths = $container->get(\ResponsiveSk\Slim4Paths\Paths::class);
        $factory = new \HdmBoot\SharedKernel\Database\DatabaseManagerFactory($paths);
        $markManager = $factory->createMarkManager();

        return new \HdmBoot\Modules\Core\Mark\Repository\SqliteMarkRepository(
            $markManager->getConnection(),
            $container->get(LoggerInterface::class)
        );
    },

    // Mark Authentication Service
    \HdmBoot\Modules\Core\Mark\Services\MarkAuthenticationService::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Mark\Services\MarkAuthenticationService(
            $container->get(\HdmBoot\Modules\Core\Mark\Repository\MarkRepositoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    },

    // Mark Login Page Action
    \HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginPageAction::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginPageAction();
    },

    // Mark Login Submit Action
    \HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginSubmitAction::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginSubmitAction(
            $container->get(\HdmBoot\Modules\Core\Mark\Services\MarkAuthenticationService::class),
            $container->get(LoggerInterface::class)
        );
    },
];
