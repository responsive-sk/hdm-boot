<?php

/**
 * Storage Module Configuration.
 *
 * Provides file-based storage with Markdown, JSON, and YAML drivers.
 * Includes Article and Documentation models for content management.
 */

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Drivers\MarkdownDriver;
use MvaBootstrap\Modules\Core\Storage\Drivers\JsonDriver;
use MvaBootstrap\Modules\Core\Storage\Models\Article;
use MvaBootstrap\Modules\Core\Storage\Models\Documentation;
use ResponsiveSk\Slim4Paths\Paths;

return [
    // === MODULE METADATA ===

    'name' => 'Storage',
    'version' => '1.0.0',
    'description' => 'File-based storage system with multiple drivers',

    // === SETTINGS ===

    'settings' => [
        'content_directory' => 'content',
        'default_driver' => 'markdown',
        'cache_enabled' => true,
        'auto_create_directories' => true,

        'drivers' => [
            'markdown' => [
                'extension' => 'md',
                'content_column' => 'content',
            ],
            'json' => [
                'extension' => 'json',
                'pretty_print' => true,
            ],
        ],

        'models' => [
            'articles' => [
                'driver' => 'markdown',
                'directory' => 'articles',
                'auto_slug' => true,
                'auto_reading_time' => true,
            ],
            'docs' => [
                'driver' => 'markdown',
                'directory' => 'docs',
                'auto_slug' => true,
                'navigation' => true,
            ],
        ],
    ],

    // === SERVICES ===

    'services' => [
        // File Storage Service
        FileStorageService::class => function (Container $container): FileStorageService {
            $paths = $container->get(Paths::class);
            if (!$paths instanceof Paths) {
                throw new \RuntimeException('Paths service not properly configured');
            }

            $contentDir = $paths->base() . '/content';
            $service = new FileStorageService($contentDir);

            // Register additional drivers if needed
            // $service->registerDriver('yaml', new YamlDriver());

            return $service;
        },

        // Markdown Driver
        MarkdownDriver::class => function (): MarkdownDriver {
            return new MarkdownDriver();
        },

        // JSON Driver
        JsonDriver::class => function (): JsonDriver {
            return new JsonDriver();
        },

        // Article Model Factory
        'article.factory' => function (): callable {
            return function (array $attributes = []): Article {
                /** @var array<string, mixed> $typedAttributes */
                $typedAttributes = $attributes;
                return new Article($typedAttributes);
            };
        },

        // Documentation Model Factory
        'documentation.factory' => function (): callable {
            return function (array $attributes = []): Documentation {
                /** @var array<string, mixed> $typedAttributes */
                $typedAttributes = $attributes;
                return new Documentation($typedAttributes);
            };
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        FileStorageService::class => FileStorageService::class,
        'storage' => FileStorageService::class,
        'article.factory' => 'article.factory',
        'documentation.factory' => 'documentation.factory',
    ],

    // === EVENT SYSTEM ===

    'published_events' => [
        'storage.file_saved' => 'Fired when a file is saved to storage',
        'storage.file_deleted' => 'Fired when a file is deleted from storage',
        'storage.cache_cleared' => 'Fired when storage cache is cleared',
        'article.created' => 'Fired when a new article is created',
        'article.updated' => 'Fired when an article is updated',
        'article.published' => 'Fired when an article is published',
        'documentation.created' => 'Fired when new documentation is created',
        'documentation.updated' => 'Fired when documentation is updated',
    ],

    'event_subscriptions' => [
        // No external event subscriptions currently
    ],

    // === API ENDPOINTS ===

    'api_endpoints' => [
        'GET /api/articles' => 'List all published articles',
        'GET /api/articles/{slug}' => 'Get specific article by slug',
        'GET /api/articles/category/{category}' => 'Get articles by category',
        'GET /api/articles/tag/{tag}' => 'Get articles by tag',
        'GET /api/docs' => 'List all documentation',
        'GET /api/docs/{slug}' => 'Get specific documentation by slug',
        'GET /api/docs/category/{category}' => 'Get documentation by category',
        'GET /api/storage/stats' => 'Get storage statistics',
    ],

    // === MIDDLEWARE ===

    'middleware' => [
        // No specific middleware currently
    ],

    // === PERMISSIONS ===

    'permissions' => [
        'storage.read' => 'Read access to storage files',
        'storage.write' => 'Write access to storage files',
        'storage.delete' => 'Delete access to storage files',
        'articles.read' => 'Read access to articles',
        'articles.write' => 'Write access to articles',
        'articles.publish' => 'Publish articles',
        'docs.read' => 'Read access to documentation',
        'docs.write' => 'Write access to documentation',
    ],

    // === CONTENT TYPES ===

    'content_types' => [
        'articles' => [
            'model' => Article::class,
            'directory' => 'articles',
            'driver' => 'markdown',
            'schema' => Article::schema(),
        ],
        'docs' => [
            'model' => Documentation::class,
            'directory' => 'docs',
            'driver' => 'markdown',
            'schema' => Documentation::schema(),
        ],
    ],

    // === MODULE STATUS ===

    'status' => [
        'implemented' => [
            'File-based storage system',
            'Markdown driver with YAML front-matter',
            'JSON driver for structured data',
            'Article model with publishing features',
            'Documentation model with navigation',
            'Caching system for performance',
            'Search functionality',
            'Category and tag management',
        ],

        'planned' => [
            'YAML driver implementation',
            'Image and media handling',
            'Version control integration',
            'Content migration tools',
            'Advanced search with indexing',
            'Content templates',
            'Bulk operations',
            'Content validation',
        ],
    ],

    // === INITIALIZATION ===

    'initialize' => function (Container $container): void {
        // Create content directory structure
        $paths = $container->get(Paths::class);
        if ($paths instanceof Paths) {
            $contentDir = $paths->base() . '/content';

            $directories = [
                $contentDir,
                $contentDir . '/articles',
                $contentDir . '/docs',
            ];

            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // Create sample files if directories are empty
            $articlesDir = $contentDir . '/articles';
            $docsDir = $contentDir . '/docs';

            $articleFiles = is_dir($articlesDir) ? scandir($articlesDir) : false;
            if (is_dir($articlesDir) && is_array($articleFiles) && count($articleFiles) <= 2) {
                // Create sample article
                $sampleArticle = <<<'MD'
---
title: "Welcome to MVA Bootstrap"
slug: "welcome"
author: "MVA Team"
published: true
published_at: "2024-01-01 12:00:00"
featured: true
category: "getting-started"
tags: ["welcome", "introduction"]
excerpt: "Welcome to MVA Bootstrap - a modern PHP framework for rapid application development."
---

# Welcome to MVA Bootstrap

This is your first article! MVA Bootstrap provides a powerful file-based storage system that allows you to manage content using simple Markdown files.

## Features

- **Markdown Support**: Write content in Markdown with YAML front-matter
- **File-based Storage**: No database required for content
- **Git-friendly**: Version control your content easily
- **Fast Performance**: Built-in caching for optimal speed

## Getting Started

To create a new article, simply create a new `.md` file in the `content/articles/` directory with YAML front-matter at the top.

Happy writing! 🚀
MD;

                file_put_contents($articlesDir . '/welcome.md', $sampleArticle);
            }

            $docFiles = is_dir($docsDir) ? scandir($docsDir) : false;
            if (is_dir($docsDir) && is_array($docFiles) && count($docFiles) <= 2) {
                // Create sample documentation
                $sampleDoc = <<<'MD'
---
title: "Installation Guide"
slug: "installation"
category: "getting-started"
order: 1
description: "Learn how to install and set up MVA Bootstrap"
difficulty: "beginner"
estimated_time: "5 minutes"
---

# Installation Guide

This guide will help you install and set up MVA Bootstrap on your system.

## Requirements

- PHP 8.1 or higher
- Composer
- Web server (Apache/Nginx)

## Installation Steps

1. Clone the repository
2. Install dependencies with Composer
3. Configure your environment
4. Set up your web server

That's it! You're ready to start building with MVA Bootstrap.
MD;

                file_put_contents($docsDir . '/installation.md', $sampleDoc);
            }
        }
    },

    // === HEALTH CHECK ===

    'health_check' => function (Container $container): array {
        $health = [
            'content_directory_exists' => false,
            'content_directory_writable' => false,
            'articles_directory_exists' => false,
            'docs_directory_exists' => false,
            'sample_files_exist' => false,
            'last_check' => date('Y-m-d H:i:s'),
        ];

        try {
            $paths = $container->get(Paths::class);
            if ($paths instanceof Paths) {
                $contentDir = $paths->base() . '/content';

                $health['content_directory_exists'] = is_dir($contentDir);
                $health['content_directory_writable'] = is_writable($contentDir);
                $health['articles_directory_exists'] = is_dir($contentDir . '/articles');
                $health['docs_directory_exists'] = is_dir($contentDir . '/docs');

                $health['sample_files_exist'] =
                    file_exists($contentDir . '/articles/welcome.md') &&
                    file_exists($contentDir . '/docs/installation.md');
            }
        } catch (\Exception $e) {
            $health['error'] = $e->getMessage();
        }

        return $health;
    },
];
