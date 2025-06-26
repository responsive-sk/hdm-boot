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
        'storage'  => $basePath . '/storage',
        'sessions' => $basePath . '/var/sessions',
        'database' => $basePath . '/database',

        // Template directories
        'templates' => $basePath . '/templates',
        'views'     => $basePath . '/templates',
        'layouts'   => $basePath . '/templates/layouts',
        'partials'  => $basePath . '/templates/partials',

        // Content directories (Orbit CMS)
        'content'      => $basePath . '/content',
        'articles'     => $basePath . '/content/articles',
        'docs_content' => $basePath . '/content/docs',
        'orbit'        => $basePath . '/var/orbit',

        // Asset directories
        'css'    => $basePath . '/public/assets/css',
        'js'     => $basePath . '/public/assets/js',
        'images' => $basePath . '/public/assets/images',
        'fonts'  => $basePath . '/public/assets/fonts',
        'media'  => $basePath . '/public/media',

        // Security directories
        'keys'    => $basePath . '/var/keys',
        'exports' => $basePath . '/var/exports',
        'imports' => $basePath . '/var/imports',

        // Localization directories
        'lang'         => $basePath . '/resources/lang',
        'translations' => $basePath . '/resources/translations',
        'locales'      => $basePath . '/resources/locales',

        // Development directories
        'tests'    => $basePath . '/tests',
        'fixtures' => $basePath . '/tests/fixtures',
        'docs'     => $basePath . '/docs',
        'scripts'  => $basePath . '/scripts',
        'bin'      => $basePath . '/bin',
    ],

    // Security and runtime settings
    'security' => [
        'allowed_directories' => [
            'var', 'logs', 'cache', 'uploads', 'storage', 'sessions', 'fixtures',
            'content', 'articles', 'docs_content', 'orbit',
            'css', 'js', 'images', 'fonts', 'media',
            'keys', 'exports', 'imports',
            'lang', 'translations', 'locales',
            'scripts', 'bin',
        ],
        'forbidden_paths'     => ['.env', 'config', 'src', 'bootstrap', 'modules', 'vendor', 'bin'],
        'upload_restrictions' => [
            'max_size'             => 5 * 1024 * 1024, // 5MB
            'allowed_extensions'   => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md', 'zip', 'csv', 'json'],
            'forbidden_extensions' => ['php', 'exe', 'bat', 'js', 'html', 'asp'],
        ],
    ],

    'auto_create' => [
        'var', 'logs', 'cache', 'uploads', 'storage', 'sessions',
        'templates', 'layouts', 'partials',
        'content', 'articles', 'docs_content', 'orbit',
        'css', 'js', 'images', 'fonts', 'media',
        'keys', 'exports', 'imports',
        'lang', 'translations', 'locales',
        'scripts', 'bin',
    ],
    'permissions' => ['directories' => 0o755, 'files' => 0o644],
];
