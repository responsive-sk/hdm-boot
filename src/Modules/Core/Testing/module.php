<?php

declare(strict_types=1);

/**
 * Testing Module
 *
 * Provides testing utilities, fixtures, and test helpers for the application.
 * Includes unit test helpers, integration test utilities, and mock factories.
 */

return [
    'name' => 'Testing',
    'version' => '1.0.0',
    'description' => 'Testing utilities and helpers for comprehensive application testing',
    'type' => 'core',
    'dependencies' => [
        'Logging',
        'Database',
        'Storage',
    ],
    'autoload' => [
        'psr-4' => [
            'MvaBootstrap\\Modules\\Core\\Testing\\' => __DIR__,
        ],
    ],
    'config_file' => __DIR__ . '/config.php',
    'routes_file' => null, // No routes for testing module
    'enabled' => true,
    'priority' => 100, // Load after other core modules
];
