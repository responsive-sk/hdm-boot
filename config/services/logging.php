<?php

declare(strict_types=1);

use DI\Container;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Logging Services Configuration.
 */
return [
    // Logger Factory
    \MvaBootstrap\Shared\Services\LoggerFactory::class => function (Container $container): \MvaBootstrap\Shared\Services\LoggerFactory {
        $paths = $container->get(Paths::class);
        return new \MvaBootstrap\Shared\Services\LoggerFactory(
            $paths,
            $_ENV['APP_ENV'] ?? 'development',
            ($_ENV['APP_DEBUG'] ?? 'true') === 'true'
        );
    },

    // Main Application Logger
    \Psr\Log\LoggerInterface::class => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Shared\Services\LoggerFactory::class);
        return $factory->createLogger('app');
    },

    // Security Logger
    'security.logger' => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Shared\Services\LoggerFactory::class);
        return $factory->createSecurityLogger();
    },

    // Performance Logger
    'performance.logger' => function (Container $container): \Psr\Log\LoggerInterface {
        $factory = $container->get(\MvaBootstrap\Shared\Services\LoggerFactory::class);
        return $factory->createPerformanceLogger();
    },
];
