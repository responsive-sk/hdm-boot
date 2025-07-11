<?php

/**
 * Database Module Manifest.
 *
 * This file defines the Database module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name'         => 'Database',
    'version'      => '1.0.0',
    'description'  => 'Database abstraction layer using native PDO',
    'dependencies' => [], // Core module with no dependencies
    'config'       => 'config.php',
    'routes'       => null, // Database module has no routes
    'authors'      => [
        'HDM Boot Team',
    ],
    'tags' => [
        'database',
        'pdo',
        'sqlite',
        'abstraction',
    ],
    'provides' => [
        'database-manager',
        'pdo-connection',
        'database-abstraction',
    ],
    'requires' => [
        'php'         => '>=8.1',
        'ext-pdo'     => '*',
        'ext-sqlite3' => '*',
    ],
    'enabled' => true,
];
