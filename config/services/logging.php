<?php

declare(strict_types=1);

use DI\Container;
use ResponsiveSk\Slim4Paths\Paths;

/*
 * Logging Services Configuration.
 */
return [
    // Logger Factory (moved to Core module)
    \MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory::class => function (Container $container) {
        $paths = $container->get(Paths::class);

        return new \MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory(
            $paths,
            $_ENV['APP_ENV'] ?? 'development',
            ($_ENV['APP_DEBUG'] ?? 'true') === 'true'
        );
    },

    // Main Application Logger
    \Psr\Log\LoggerInterface::class => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory::class);

        return $factory->createLogger('app');
    },

    // Security Logger
    'security.logger' => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory::class);

        return $factory->createSecurityLogger();
    },

    // Performance Logger
    'performance.logger' => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory::class);

        return $factory->createPerformanceLogger();
    },

    // Audit Logger
    'audit.logger' => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LoggerFactory::class);

        return $factory->createAuditLogger();
    },
];
