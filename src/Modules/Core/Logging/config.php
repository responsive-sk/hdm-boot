<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Logging\Infrastructure\Services\LoggerFactory;
use HdmBoot\Modules\Core\Logging\Infrastructure\Services\LogCleanupService;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/*
 * Logging Module Configuration.
 *
 * Defines specialized loggers for different purposes.
 */

return [
    'services' => [
        // Logger Factory
        LoggerFactory::class => function (Container $c): LoggerFactory {
            /** @var Paths $paths */
            $paths = $c->get(Paths::class);
            /** @var string $env */
            $env = $_ENV['APP_ENV'] ?? 'production';

            return new LoggerFactory($paths, $env, (bool) ($_ENV['APP_DEBUG'] ?? false));
        },

        // Default application logger
        LoggerInterface::class => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createLogger('app');
        },

        // Specialized loggers with unique names
        'logger.security' => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createSecurityLogger();
        },

        'logger.audit' => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createAuditLogger();
        },

        'logger.performance' => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createPerformanceLogger();
        },

        // Action-specific loggers
        'logger.login' => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createLogger('login');
        },

        'logger.profile' => function (Container $c): LoggerInterface {
            /** @var LoggerFactory $factory */
            $factory = $c->get(LoggerFactory::class);
            return $factory->createLogger('profile');
        },

        // Log Cleanup Service
        LogCleanupService::class => function (Container $c): LogCleanupService {
            /** @var Paths $paths */
            $paths = $c->get(Paths::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new LogCleanupService($paths, $logger);
        },
    ],

    'dependencies' => [
        // This module depends on no other modules
    ],

    'routes' => [
        // No routes for logging module
    ],

    'middleware' => [
        // No middleware for logging module
    ],
];
