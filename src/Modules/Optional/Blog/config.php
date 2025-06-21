<?php

declare(strict_types=1);

/**
 * Blog Module Configuration
 *
 * Optional module for blog functionality using Core Storage and Template modules.
 */

use MvaBootstrap\Modules\Optional\Blog\Controllers\BlogController;
use MvaBootstrap\Modules\Core\Template\Services\TemplateRenderer;

return [
    'name' => 'Blog',
    'description' => 'Blog functionality with Orbit-style content management',
    'version' => '1.0.0',
    'dependencies' => [
        'Core/Storage',
        'Core/Template',
    ],

    'routes' => [
        'GET /blog' => [BlogController::class, 'home'],
        'GET /blog/article/{slug}' => [BlogController::class, 'article'],
        'GET /blog/categories' => [BlogController::class, 'categories'],
        'GET /blog/tags' => [BlogController::class, 'tags'],
        'GET /blog/about' => [BlogController::class, 'about'],
    ],

    'services' => [
        // Blog module uses Core services
    ],

    'templates' => [
        'blog' => 'Core/Template/Templates/blog',
    ],

    'settings' => [
        'articles_per_page' => 10,
        'enable_comments' => false,
        'enable_search' => true,
        'default_author' => 'MVA Bootstrap Team',
    ],
];
