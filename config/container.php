<?php

declare(strict_types=1);

use DI\Container;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MvaBootstrap\Shared\Helpers\SecurePathHelper;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
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
    Paths::class => function () {
        // Load paths configuration
        $pathsConfig = require __DIR__ . '/paths.php';
        return new Paths($pathsConfig['base_path']);
    },

    // Secure Path Helper
    SecurePathHelper::class => function (Container $c) {
        return new SecurePathHelper($c->get(Paths::class));
    },
    
    // Logger
    LoggerInterface::class => function (Container $c): LoggerInterface {
        $pathHelper = $c->get(SecurePathHelper::class);
        $logPath = $pathHelper->securePath('app.log', 'logs');
        
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
        return $logger;
    },

    // Session
    SessionInterface::class => function (): SessionInterface {
        $session = new PhpSession();
        $session->start();
        return $session;
    },

    // Database connection (will be configured by modules)
    PDO::class => function (Container $c): PDO {
        $pathHelper = $c->get(SecurePathHelper::class);
        
        // Use secure path for SQLite database
        $defaultDbPath = $pathHelper->securePath('app.db', 'storage');
        $dsn = $_ENV['DATABASE_URL'] ?? 'sqlite:' . $defaultDbPath;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, null, null, $options);
    },

    // Application settings
    'settings' => [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'MVA Bootstrap',
            'version' => '1.0.0',
            'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
        ],
        'security' => [
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production',
            'jwt_expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600), // 1 hour
            'password_min_length' => 8,
            'session_lifetime' => 7200, // 2 hours
        ],
        'database' => [
            'url' => $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/app.db',
        ],
        'paths' => function (Container $c) {
            return $c->get(Paths::class);
        },
    ],

]);

return $containerBuilder->build();
