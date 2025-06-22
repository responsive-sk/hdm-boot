<?php

/**
 * Storage Module Manifest
 *
 * This file defines the Storage module metadata, dependencies,
 * and configuration paths for automatic module discovery.
 */

declare(strict_types=1);

use HdmBoot\SharedKernel\Services\PathsFactory;

$paths = PathsFactory::create();

return [
    'name' => 'Storage',
    'version' => '1.0.0',
    'description' => 'File-based storage system with Markdown, JSON, and YAML drivers. Provides Article and Documentation models.',
    'type' => 'core',
    'dependencies' => [],
    'config' => $paths->getPath($paths->src('Modules/Core/Storage'), 'config.php'),
    'routes' => null,
    'authors' => ['HDM Boot Team'],
    'tags' => ['storage', 'files', 'markdown', 'articles', 'documentation'],
    'provides' => [
        'file-storage',
        'markdown-driver',
        'json-driver',
        'article-model',
        'documentation-model',
    ],
    'requires' => [
        'php' => '>=8.1',
    ],
    'enabled' => true,
];
