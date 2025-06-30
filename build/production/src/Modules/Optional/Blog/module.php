<?php

declare(strict_types=1);

/**
 * Blog Module Configuration.
 *
 * Enhanced Orbit CMS-style blog module with API endpoints.
 */

return [
    'name'         => 'Blog',
    'version'      => '2.0.0',
    'description'  => 'Enhanced blog module with Orbit CMS features and API endpoints',
    'authors'      => ['HDM Boot Team'],
    'dependencies' => ['Storage', 'Template'],
    'provides'     => ['blog', 'articles', 'content-management'],
    'tags'         => ['blog', 'cms', 'content', 'api', 'orbit'],
    'enabled'      => true,

    // Module files
    'routes' => 'routes.php',
    'config' => 'config.php',

    // Module metadata
    'meta' => [
        'api_version'      => '2.0',
        'orbit_compatible' => true,
        'features'         => [
            'article_management',
            'api_endpoints',
            'markdown_support',
            'tag_system',
            'category_system',
            'search_functionality',
        ],
    ],
];
