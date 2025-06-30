<?php

declare(strict_types=1);

/**
 * Blog Module Configuration.
 *
 * Optional module for blog functionality using Core Storage and Template modules.
 */

use HdmBoot\Modules\Optional\Blog\Actions\BlogAction;

return [
    'name'         => 'Blog',
    'description'  => 'Blog functionality with Orbit-style content management',
    'version'      => '1.0.0',
    'dependencies' => [
        'Core/Storage',
        'Core/Template',
    ],

    'routes' => [
        'GET /blog'                => [BlogAction::class, 'home'],
        'GET /blog/article/{slug}' => [BlogAction::class, 'article'],
        'GET /blog/categories'     => [BlogAction::class, 'categories'],
        'GET /blog/tags'           => [BlogAction::class, 'tags'],
        'GET /blog/about'          => [BlogAction::class, 'about'],
    ],

    'services' => [
        // Blog module uses Core services
    ],

    'templates' => [
        'blog' => 'Core/Template/Templates/blog',
    ],

    'settings' => [
        'articles_per_page' => 10,
        'enable_comments'   => false,
        'enable_search'     => true,
        'default_author'    => 'HDM Boot Team',
    ],
];
