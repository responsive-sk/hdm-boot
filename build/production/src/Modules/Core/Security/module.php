<?php

/**
 * Security Module Manifest.
 *
 * This file defines the Security module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name'         => 'Security',
    'version'      => '1.0.0',
    'description'  => 'Authentication, authorization, and security services',
    'dependencies' => ['User', 'Session'], // Depends on User for authentication and Session for sessions/CSRF
    'config'       => 'config.php',
    'routes'       => null, // Security routes are defined in config.php
    'authors'      => [
        'HDM Boot Team',
    ],
    'tags' => [
        'security',
        'authentication',
        'authorization',
        'jwt',
    ],
    'provides' => [
        'authentication',
        'authorization',
        'jwt-tokens',
        'security-middleware',
    ],
    'requires' => [
        'php'         => '>=8.1',
        'ext-openssl' => '*',
    ],
    'enabled' => true,
];
