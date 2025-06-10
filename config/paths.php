<?php

declare(strict_types=1);

/**
 * Simplified Paths Configuration.
 */

$basePath = dirname(__DIR__);

return [
    'base_path' => $basePath,

    'paths' => [
        // Core directories
        'config'    => $basePath . '/config',
        'src'       => $basePath . '/src',
        'bootstrap' => $basePath . '/bootstrap',
        'modules'   => $basePath . '/modules',
        'public'    => $basePath . '/public',
        'vendor'    => $basePath . '/vendor',

        // Runtime directories
        'var'      => $basePath . '/var',
        'logs'     => $basePath . '/var/logs',
        'cache'    => $basePath . '/var/cache',
        'uploads'  => $basePath . '/var/uploads',
        'storage'  => $basePath . '/var/storage',
        'sessions' => $basePath . '/var/sessions',
        'database' => $basePath . '/database',

        // Development directories
        'tests'    => $basePath . '/tests',
        'fixtures' => $basePath . '/tests/fixtures',
        'docs'     => $basePath . '/docs',
        'bin'      => $basePath . '/bin',
    ],

    // Security and runtime settings
    'security' => [
        'allowed_directories' => ['var', 'logs', 'cache', 'uploads', 'storage', 'sessions', 'fixtures'],
        'forbidden_paths' => ['.env', 'config', 'src', 'bootstrap', 'modules', 'vendor', 'bin'],
        'upload_restrictions' => [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md', 'zip', 'csv', 'json'],
            'forbidden_extensions' => ['php', 'exe', 'bat', 'js', 'html', 'asp'],
        ],
    ],

    'auto_create' => ['var', 'logs', 'cache', 'uploads', 'storage', 'sessions'],
    'permissions' => ['directories' => 0o755, 'files' => 0o644],
];
