<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Database\Domain\Contracts\DatabaseManagerInterface;
use MvaBootstrap\Modules\Core\Database\Infrastructure\Services\DatabaseManager;
use MvaBootstrap\Modules\Core\Database\Infrastructure\Services\CakePHPDatabaseManager;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/*
 * Database Module Configuration.
 */
return [
    // === MODULE METADATA ===

    'name'        => 'Database',
    'version'     => '1.0.0',
    'description' => 'Database abstraction layer supporting multiple database managers (PDO, CakePHP, Doctrine)',
    'author'      => 'MVA Bootstrap Team',
    'license'     => 'MIT',

    // === MODULE DEPENDENCIES ===

    'dependencies' => [
        // No module dependencies - Database is a core infrastructure module
    ],

    // === MODULE SETTINGS ===

    'settings' => [
        'enabled'            => true,
        'default_manager'    => 'pdo', // 'pdo', 'cakephp', 'doctrine'
        'database_url'       => $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/app.db',
        'connection_timeout' => 30,
        'query_timeout'      => 60,
        'auto_initialize'    => true,
        'migration_enabled'  => true,
        'query_logging'      => $_ENV['APP_DEBUG'] === 'true',
        'supported_managers' => [
            'pdo'      => 'Native PDO database manager',
            'cakephp'  => 'CakePHP Database query builder',
            'doctrine' => 'Doctrine ORM and DBAL',
        ],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // Database Manager Interface (using PDO)
        DatabaseManagerInterface::class => function (Container $container): DatabaseManagerInterface {
            return $container->get(DatabaseManager::class);
        },

        // CakePHP Database Manager (primary implementation)
        CakePHPDatabaseManager::class => function (Container $container): CakePHPDatabaseManager {
            return new CakePHPDatabaseManager(
                $container->get(Paths::class)
            );
        },

        // Legacy PDO Database Manager (for backward compatibility)
        DatabaseManager::class => function (Container $container): DatabaseManager {
            return new DatabaseManager(
                $container->get(Paths::class)
            );
        },

        // PDO Connection
        \PDO::class => function (Container $container): \PDO {
            $moduleManager = $container->get(\MvaBootstrap\SharedKernel\Modules\ModuleManager::class);
            $config = $moduleManager->getModuleConfig('Database');
            $databaseUrl = $config['settings']['database_url'];

            // Parse database URL
            if (str_starts_with($databaseUrl, 'sqlite:')) {
                $dbPath = substr($databaseUrl, 7);

                // Convert to absolute path if relative
                if (!str_starts_with($dbPath, '/')) {
                    $paths = $container->get(Paths::class);
                    $dbPath = $paths->base() . '/' . $dbPath;
                }

                $dsn = "sqlite:{$dbPath}";

                // Ensure directory exists
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0o755, true);
                }

                $pdo = new \PDO($dsn);
            } else {
                // For other database types (MySQL, PostgreSQL, etc.)
                $pdo = new \PDO($databaseUrl);
            }

            // Set PDO attributes
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            return $pdo;
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        DatabaseManagerInterface::class => DatabaseManager::class,
        DatabaseManager::class          => DatabaseManager::class,
        \PDO::class                     => \PDO::class,
    ],

    // === EVENT SYSTEM ===

    'published_events' => [
        'database.connected'               => 'Fired when database connection is established',
        'database.disconnected'            => 'Fired when database connection is closed',
        'database.query_executed'          => 'Fired when SQL query is executed',
        'database.transaction_started'     => 'Fired when database transaction begins',
        'database.transaction_committed'   => 'Fired when database transaction is committed',
        'database.transaction_rolled_back' => 'Fired when database transaction is rolled back',
        'database.schema_initialized'      => 'Fired when database schema is initialized',
    ],

    'event_subscriptions' => [
        // No external event subscriptions currently
    ],

    // === API ENDPOINTS ===

    'api_endpoints' => [
        'GET /api/database/status'      => 'Get database connection status and health',
        'POST /api/database/initialize' => 'Initialize database schema',
        'GET /api/database/info'        => 'Get database configuration information',
    ],

    // === MIDDLEWARE ===

    'middleware' => [
        // No specific middleware currently
    ],

    // === PERMISSIONS ===

    'permissions' => [
        'database.read'       => 'Read access to database information',
        'database.admin'      => 'Administrative access to database management',
        'database.initialize' => 'Initialize database schema',
        'database.migrate'    => 'Run database migrations',
    ],

    // === DATABASE ===

    'database_tables' => [
        // This module manages database connections, not specific tables
        // Individual modules define their own tables
    ],

    // === MODULE STATUS ===

    'status' => [
        'implemented' => [
            'Database Manager Interface and contracts',
            'PDO database manager implementation',
            'Database connection management',
            'Transaction support',
            'Query execution',
            'Connection health monitoring',
            'Database initialization',
            'Event-driven architecture integration',
        ],

        'planned' => [
            'CakePHP Database integration',
            'Doctrine ORM integration',
            'Database migration system',
            'Query builder abstraction',
            'Connection pooling',
            'Database performance monitoring',
            'Automatic failover support',
            'Database backup and restore',
        ],
    ],

    // === INITIALIZATION ===

    'initialize' => function (): void {
        // Create database storage directory
        if (!file_exists('var/storage')) {
            mkdir('var/storage', 0o755, true);
        }

        // Initialize database if auto_initialize is enabled
        // This will be handled by DatabaseManager
    },

    // === HEALTH CHECK ===

    'health_check' => function (): array {
        $health = [
            'storage_directory_exists'   => is_dir('var/storage'),
            'storage_directory_writable' => is_writable('var/storage'),
            'database_file_exists'       => file_exists('var/storage/app.db'),
            'pdo_extension_loaded'       => extension_loaded('pdo'),
            'sqlite_extension_loaded'    => extension_loaded('pdo_sqlite'),
            'last_check'                 => date('Y-m-d H:i:s'),
        ];

        // Test database connection
        try {
            $pdo = new \PDO('sqlite:var/storage/app.db');
            $health['database_connection'] = true;
            $health['database_version'] = $pdo->query('SELECT sqlite_version()')->fetchColumn();
        } catch (\Exception $e) {
            $health['database_connection'] = false;
            $health['database_error'] = $e->getMessage();
        }

        return $health;
    },
];
