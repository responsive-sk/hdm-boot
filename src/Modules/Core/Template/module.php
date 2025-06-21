<?php

/**
 * Template Module Manifest
 *
 * This file defines the Template module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

return [
    'name' => 'Template',
    'version' => '1.0.0',
    'description' => 'Template rendering engine supporting PHP and Twig templates',
    'dependencies' => ['Session'], // Depends on Session for CSRF tokens
    'config' => 'config.php',
    'routes' => null, // Template module has no routes
    'authors' => [
        'MvaBootstrap Team'
    ],
    'tags' => [
        'template',
        'rendering',
        'php',
        'twig',
        'views'
    ],
    'provides' => [
        'template-rendering',
        'php-templates',
        'twig-templates',
        'template-caching'
    ],
    'requires' => [
        'php' => '>=8.1'
    ],
    'enabled' => true
];
