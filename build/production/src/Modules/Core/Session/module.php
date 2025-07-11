<?php

/**
 * Session Module Manifest.
 *
 * This file defines the Session module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name'         => 'Session',
    'version'      => '1.0.0',
    'description'  => 'Session management, CSRF protection, and session persistence',
    'dependencies' => [], // Base module - no dependencies
    'config'       => 'config.php',
    'routes'       => null, // Session module has no routes
    'authors'      => [
        'HDM Boot Team',
    ],
    'tags' => [
        'session',
        'csrf',
        'security',
        'persistence',
        'middleware',
    ],
    'provides' => [
        'session-management',
        'csrf-protection',
        'session-persistence',
        'session-middleware',
    ],
    'requires' => [
        'php'         => '>=8.1',
        'ext-session' => '*',
    ],
    'enabled' => true,
];
