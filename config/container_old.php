<?php

declare(strict_types=1);

use DI\Container;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MvaBootstrap\Shared\Services\DatabaseManager;
use MvaBootstrap\Shared\Helpers\SecurePathHelper;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * DI Container Configuration.
 *
 * Defines core services and dependencies for the bootstrap application.
 */
$containerBuilder = new ContainerBuilder();

// Enable compilation in production
if (($_ENV['APP_ENV'] ?? 'dev') === 'prod') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

$containerBuilder->addDefinitions([
    // Paths (Path Security)
    Paths::class => function (): Paths {
        // Load paths configuration
        $pathsConfig = require __DIR__ . '/paths.php';

        // Create custom paths array for the Paths service
        $customPaths = [
            'var' => $pathsConfig['paths']['var'],
            'logs' => $pathsConfig['paths']['logs'],
            'cache' => $pathsConfig['paths']['cache'],
            'uploads' => $pathsConfig['paths']['uploads'],
            'storage' => $pathsConfig['paths']['storage'],
            'sessions' => $pathsConfig['paths']['sessions'],
            'database' => $pathsConfig['paths']['database'],
        ];

        return new Paths($pathsConfig['base_path'], $customPaths);
    },

    // Secure Path Helper
    SecurePathHelper::class => function (Container $c): SecurePathHelper {
        $paths = $c->get(Paths::class); assert($paths instanceof Paths); return new SecurePathHelper($paths);
    },

    // Logger
    LoggerInterface::class => function (Container $c): LoggerInterface {
        $paths = $c->get(Paths::class);
        $logPath = $paths->base() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

        return $logger;
    },

    // PSR-7 Response Factory (using Slim's factory)
    \Psr\Http\Message\ResponseFactoryInterface::class => function (): \Psr\Http\Message\ResponseFactoryInterface {
        return \Slim\Factory\AppFactory::determineResponseFactory();
    },

    // Session with proper configuration (like samuelgfeller)
    SessionInterface::class => function (Container $container): SessionInterface {
        $settings = $container->get('settings');
        $sessionOptions = [
            'name' => $_ENV['SESSION_NAME'] ?? 'mva_bootstrap_session',
            'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 hours
            'save_path' => null,  // Use default save path
            'domain' => null,     // Use default domain (localhost)
            'secure' => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
            'httponly' => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
            'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
            'cache_limiter' => 'nocache',  // Prevent caching issues
        ];

        // Debug: Log session configuration
        error_log('SessionInterface creation: ' . json_encode($sessionOptions));

        return new PhpSession($sessionOptions);
    },

    // SessionManagerInterface (like samuelgfeller)
    SessionManagerInterface::class => function (Container $container): SessionManagerInterface {
        return $container->get(SessionInterface::class);
    },

    // SessionStartMiddleware
    \Odan\Session\Middleware\SessionStartMiddleware::class => function (Container $container): \Odan\Session\Middleware\SessionStartMiddleware {
        return new \Odan\Session\Middleware\SessionStartMiddleware(
            $container->get(SessionManagerInterface::class)
        );
    },

    // Database connection (will be configured by modules)
    // Database Manager (like in parent project)
    DatabaseManager::class => function (Container $c): DatabaseManager {
        $paths = $c->get(Paths::class); assert($paths instanceof Paths); return new DatabaseManager($paths);
    },

    PDO::class => function (Container $c): PDO {
        return $c->get(DatabaseManager::class)->getConnection();
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
            'jwt_expiry'          => (int) ($_ENV['JWT_EXPIRY'] ?? 3600), // 1 hour
            'password_min_length' => 8,
            'session_lifetime'    => 7200, // 2 hours
        ],
        'database' => [
            'url' => $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/app.db',
        ],
        'paths' => function (Container $c) {
            return $c->get(Paths::class);
        },
    ],

    // Security Services
    \MvaBootstrap\Modules\Core\Security\Services\CsrfService::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\CsrfService {
        $session = $container->get(\Odan\Session\SessionInterface::class);
        return new \MvaBootstrap\Modules\Core\Security\Services\CsrfService($session);
    },

    \MvaBootstrap\Modules\Core\Security\Services\SessionService::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\SessionService {
        return new \MvaBootstrap\Modules\Core\Security\Services\SessionService(
            $container->get(SessionInterface::class)
        );
    },

    // Authentication Validator
    \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator::class => function (): \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator {
        return new \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator();
    },

    // JWT Service
    \MvaBootstrap\Modules\Core\Security\Services\JwtService::class => function (): \MvaBootstrap\Modules\Core\Security\Services\JwtService {
        return new \MvaBootstrap\Modules\Core\Security\Services\JwtService(
            $_ENV['JWT_SECRET'] ?? throw new \RuntimeException('JWT_SECRET environment variable is required'),
            (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
        );
    },

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

    // Let Slim handle Actions with autowiring (like samuelgfeller)

    // Repository Factory (PROPERLY ABSTRACT!)
    \MvaBootstrap\Shared\Factories\RepositoryFactory::class => function (): \MvaBootstrap\Shared\Factories\RepositoryFactory {
        return new \MvaBootstrap\Shared\Factories\RepositoryFactory(
            $_ENV['REPOSITORY_TYPE'] ?? 'sqlite',
            $_ENV['DATABASE_MANAGER'] ?? 'pdo'
        );
    },

    // User Repository (using ABSTRACT factory)
    \MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface::class => function (Container $container): \MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface {
        $factory = $container->get(\MvaBootstrap\Shared\Factories\RepositoryFactory::class);
        $pdo = $container->get(PDO::class);
        $databaseManager = $container->get(\MvaBootstrap\Shared\Contracts\DatabaseManagerInterface::class);

        return $factory->createUserRepository($pdo, $databaseManager);
    },

    // User Services
    \MvaBootstrap\Modules\Core\User\Services\UserService::class => function (Container $container): \MvaBootstrap\Modules\Core\User\Services\UserService {
        $userRepository = $container->get(\MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface::class);
        return new \MvaBootstrap\Modules\Core\User\Services\UserService($userRepository);
    },

    // Language Services
    \MvaBootstrap\Modules\Core\Language\Services\LocaleService::class => function (Container $container): \MvaBootstrap\Modules\Core\Language\Services\LocaleService {
        return new \MvaBootstrap\Modules\Core\Language\Services\LocaleService(
            $container->get(Paths::class),
            $container->get(\Psr\Log\LoggerInterface::class)
        );
    },

    // SecurityLoginChecker
    \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker {
        return new \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker(
            $container->get(\PDO::class)
        );
    },

    // ProfilePageAction (using Slim PhpRenderer like samuelgfeller)
    \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction::class => function (Container $container): \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction {
        return new \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction(
            $container->get(\Slim\Views\PhpRenderer::class),
            $container->get(\Odan\Session\SessionInterface::class),
            $container->get(\MvaBootstrap\Modules\Core\User\Services\UserService::class)
        );
    },

    // Database Managers with Abstraction
    \MvaBootstrap\Shared\Services\DatabaseConnectionManager::class => function (Container $container): \MvaBootstrap\Shared\Services\DatabaseConnectionManager {
        $paths = $container->get(Paths::class);
        return new \MvaBootstrap\Shared\Services\DatabaseConnectionManager(
            $paths,
            $_ENV['APP_ENV'] ?? 'development'
        );
    },

    // Default Database Manager (CakePHP for now, can switch to Doctrine later)
    \MvaBootstrap\Shared\Contracts\DatabaseManagerInterface::class => function (Container $container): \MvaBootstrap\Shared\Contracts\DatabaseManagerInterface {
        return $container->get(\MvaBootstrap\Shared\Services\DatabaseConnectionManager::class);
    },

    // Query Builder Interface (same as DatabaseManager for CakePHP)
    \MvaBootstrap\Shared\Contracts\QueryBuilderInterface::class => function (Container $container): \MvaBootstrap\Shared\Contracts\QueryBuilderInterface {
        return $container->get(\MvaBootstrap\Shared\Services\DatabaseConnectionManager::class);
    },



    // Slim PhpRenderer (like samuelgfeller)
    \Slim\Views\PhpRenderer::class => function (Container $container): \Slim\Views\PhpRenderer {
        $paths = $container->get(Paths::class);
        return new \Slim\Views\PhpRenderer($paths->base() . '/templates');
    },

    // UserAuthenticationMiddleware
    \MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware::class => function (Container $container): \MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware {
        return new \MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware(
            $container->get(\Odan\Session\SessionInterface::class),
            $container->get(\Psr\Http\Message\ResponseFactoryInterface::class),
            $container->get(\MvaBootstrap\Modules\Core\User\Services\UserService::class),
            $container->get(\Psr\Log\LoggerInterface::class)
        );
    },

    // LocaleMiddleware
    \MvaBootstrap\Shared\Middleware\LocaleMiddleware::class => function (Container $container): \MvaBootstrap\Shared\Middleware\LocaleMiddleware {
        return new \MvaBootstrap\Shared\Middleware\LocaleMiddleware(
            $container->get(\MvaBootstrap\Modules\Core\Language\Services\LocaleService::class),
            $container->get(\Odan\Session\SessionInterface::class),
            $container->get(\MvaBootstrap\Modules\Core\User\Services\UserService::class),
            $container->get(\Psr\Log\LoggerInterface::class)
        );
    },

    // Template Renderer (wrapper around PhpRenderer)
    \MvaBootstrap\Shared\Services\TemplateRenderer::class => function (Container $container): \MvaBootstrap\Shared\Services\TemplateRenderer {
        $paths = $container->get(Paths::class);
        $csrfService = $container->get(\MvaBootstrap\Modules\Core\Security\Services\CsrfService::class);
        $session = $container->get(\Odan\Session\SessionInterface::class);

        return new \MvaBootstrap\Shared\Services\TemplateRenderer(
            $paths->base() . '/templates',
            $csrfService,
            $session
        );
    },
]);

return $containerBuilder->build();
