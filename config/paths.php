<?php

/**
 * Paths Configuration for responsive/path package.
 *
 * Adapted from the parent MVA project for the bootstrap application.
 */

declare(strict_types=1);

return [
    // Base application directory
    'base_path' => dirname(__DIR__),

    // Application paths
    'paths' => [
        'config'    => dirname(__DIR__) . '/config',
        'src'       => dirname(__DIR__) . '/src',
        'bootstrap' => dirname(__DIR__) . '/bootstrap',
        'modules'   => dirname(__DIR__) . '/modules',
        'public'    => dirname(__DIR__) . '/public',
        'vendor'    => dirname(__DIR__) . '/vendor',

        // Variable/runtime paths
        'var'      => dirname(__DIR__) . '/var',
        'logs'     => dirname(__DIR__) . '/var/logs',
        'cache'    => dirname(__DIR__) . '/var/cache',
        'uploads'  => dirname(__DIR__) . '/var/uploads',
        'storage'  => dirname(__DIR__) . '/var/storage',
        'sessions' => dirname(__DIR__) . '/var/sessions',

        // Test paths
        'tests'    => dirname(__DIR__) . '/tests',
        'fixtures' => dirname(__DIR__) . '/tests/fixtures',

        // Documentation
        'docs' => dirname(__DIR__) . '/docs',

        // Binary/scripts
        'bin' => dirname(__DIR__) . '/bin',
    ],

    // Security settings
    'security' => [
        // Allowed directories for file operations
        'allowed_directories' => [
            'var',
            'logs',
            'cache',
            'uploads',
            'storage',
            'sessions',
            'fixtures',
        ],

        // Forbidden paths (additional security)
        'forbidden_paths' => [
            '.env',
            'config',
            'src',
            'bootstrap',
            'modules',
            'vendor',
            'bin',
        ],

        // File upload restrictions
        'upload_restrictions' => [
            'max_size'           => 5 * 1024 * 1024, // 5MB
            'allowed_extensions' => [
                'jpg', 'jpeg', 'png', 'gif', 'webp',
                'pdf', 'doc', 'docx', 'txt', 'md',
                'zip', 'csv', 'json', 'xml',
            ],
            'forbidden_extensions' => [
                'php', 'phtml', 'php3', 'php4', 'php5',
                'exe', 'bat', 'cmd', 'com', 'scr',
                'js', 'html', 'htm', 'asp', 'aspx',
            ],
        ],
    ],

    // Directory creation settings
    'auto_create' => [
        'var',
        'logs',
        'cache',
        'uploads',
        'storage',
        'sessions',
    ],

    // Permissions for created directories
    'permissions' => [
        'directories' => 0o755,
        'files'       => 0o644,
    ],
];
