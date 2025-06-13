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
    \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware::class => function (Container $container) {
        return new \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware(
            $container->get(\MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class),
            $container->get(LoggerInterface::class),
            false // displayErrorDetails - set to true for development
        );
    },

    // Error Response Handler (moved from Shared)
    \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class => \DI\autowire(),

    // === MONITORING MODULE ===

    // Health Check Manager (moved from Shared)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager::class => \DI\autowire(),

    // Health Check Action (moved from Shared)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Actions\HealthCheckAction::class => \DI\autowire(),

    // Performance Monitor (moved from Shared)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor::class => function (Container $container) {
        return new \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor(
            $container->get('logger.performance')
        );
    },

    // Monitoring Bootstrap (moved from Shared)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Bootstrap\MonitoringBootstrap::class => \DI\autowire(),

    // === DOCUMENTATION MODULE ===

    // Documentation Viewer Action (moved from Shared)
    \MvaBootstrap\Modules\Core\Documentation\Infrastructure\Actions\DocsViewerAction::class => \DI\autowire(),

    // === SHARED KERNEL (minimal) ===

    // Secure Path Helper (stays in Shared - truly universal)
    \MvaBootstrap\Shared\Helpers\SecurePathHelper::class => \DI\autowire(),

    // === USER MODULE ===

    // SQLite PDO connection for Users module
    'users.pdo' => function (Container $container) {
        $paths = $container->get('settings')['paths'];
        $dbPath = $paths['storage'] . '/users.db';
        $dsn = 'sqlite:' . $dbPath;

        // Ensure storage directory exists
        $storageDir = dirname($dbPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    },

    // User Repository using dedicated SQLite database
    \MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface::class => function (Container $container) {
        return new \MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository(
            $container->get('users.pdo')
        );
    },
];
