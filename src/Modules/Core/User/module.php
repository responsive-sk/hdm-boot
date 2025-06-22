<?php

/**
 * User Module Manifest
 *
 * This file defines the User module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name' => 'User',
    'version' => '1.0.0',
    'description' => 'User management, profiles, and user-related services',
    'dependencies' => ['Database'], // Depends on Database for user storage
    'config' => 'config.php',
    'routes' => null, // User routes are defined in config.php
    'authors' => [
        'HDM Boot Team'
    ],
    'tags' => [
        'user',
        'profile',
        'management',
        'repository'
    ],
    'provides' => [
        'user-management',
        'user-repository',
        'user-services',
        'profile-pages'
    ],
    'requires' => [
        'php' => '>=8.1',
        'ext-pdo' => '*'
    ],
    'enabled' => true
];
