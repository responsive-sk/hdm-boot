<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Simplified DI Container Configuration.
 *
 * Loads service definitions from separate files for better organization.
 */

// Create container builder
$containerBuilder = new ContainerBuilder();

// Enable compilation for production
if (($_ENV['APP_ENV'] ?? 'dev') === 'prod') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Load core service definitions (non-module services)
$services = array_merge(
    // Interface-based bindings (DI/IoC)
    require __DIR__ . '/services/interfaces.php',

    // Core infrastructure services (non-module)
    // Note: Session services moved to Session module
    require __DIR__ . '/services/logging.php',
    require __DIR__ . '/services/events.php',
    require __DIR__ . '/services/monitoring.php',

    // Note: Module-specific services are now loaded via ModuleServiceLoader
    // This eliminates: database.php, template.php, user.php, language.php, security.php, middleware.php, actions.php, modules.php

    // Application-specific services
    [
        // Paths (Path Security)
        Paths::class => function (): Paths {
            $pathsConfig = require __DIR__ . '/paths.php';

            return new Paths($pathsConfig['base_path'], $pathsConfig['paths']);
        },

        // Secure Path Helper
        \MvaBootstrap\SharedKernel\Helpers\SecurePathHelper::class => function (\DI\Container $container): \MvaBootstrap\SharedKernel\Helpers\SecurePathHelper {
            return new \MvaBootstrap\SharedKernel\Helpers\SecurePathHelper(
                $container->get(Paths::class)
            );
        },

        // Module Service Loader
        \MvaBootstrap\SharedKernel\Modules\ModuleServiceLoader::class => function (\DI\Container $container): \MvaBootstrap\SharedKernel\Modules\ModuleServiceLoader {
            return new \MvaBootstrap\SharedKernel\Modules\ModuleServiceLoader(
                $container->get(\MvaBootstrap\SharedKernel\Modules\ModuleManager::class),
                $container->get(\Psr\Log\LoggerInterface::class)
            );
        },

        // PSR-7 Response Factory
        \Psr\Http\Message\ResponseFactoryInterface::class => function (): \Psr\Http\Message\ResponseFactoryInterface {
            return \Slim\Factory\AppFactory::determineResponseFactory();
        },

        // Application settings
        'settings' => [
            'app' => [
                'name'     => $_ENV['APP_NAME'] ?? 'MVA Bootstrap',
                'version'  => '1.0.0',
                'debug'    => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
            ],
            'security' => [
                'jwt_secret'          => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production',
                'jwt_expiry'          => (int) ($_ENV['JWT_EXPIRY'] ?? 3600),
                'password_min_length' => 8,
                'session_lifetime'    => 7200,
            ],
            'template' => [
                'cache_enabled' => ($_ENV['APP_ENV'] ?? 'dev') === 'prod',
                'auto_reload'   => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            ],
        ],
    ]
);

// Add all service definitions
$containerBuilder->addDefinitions($services);

return $containerBuilder->build();
