<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Shared\Services\DatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Database Services Configuration.
 */
return [
    // Database Manager (creates its own PDO connection)
    DatabaseManager::class => function (Container $container): DatabaseManager {
        return new DatabaseManager(
            $container->get(Paths::class),
            'app.db'
        );
    },

    // PDO Database Connection (for backward compatibility)
    \PDO::class => function (Container $container): \PDO {
        $databaseManager = $container->get(DatabaseManager::class);
        return $databaseManager->getConnection();
    },
];
