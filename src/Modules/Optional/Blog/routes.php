<?php

declare(strict_types=1);

/**
 * Blog Module Routes
 *
 * Enhanced routes with API endpoints for Orbit CMS functionality.
 */

use MvaBootstrap\Modules\Optional\Blog\Controllers\BlogController;
use Slim\App;

return function (App $app): void {
    // Blog web interface routes
    $app->get('/blog', function ($request, $response) {
        $controller = new BlogController();
        $html = $controller->home();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/article/{slug}', function ($request, $response, $args) {
        $controller = new BlogController();
        $html = $controller->article($args['slug']);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/categories', function ($request, $response) {
        $controller = new BlogController();
        $html = $controller->categories();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/tags', function ($request, $response) {
        $controller = new BlogController();
        $html = $controller->tags();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/about', function ($request, $response) {
        $controller = new BlogController();
        $html = $controller->about();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Blog API routes (Orbit CMS style)
    $app->group('/api/blog', function ($group) {
        // Articles CRUD
        $group->get('/articles', [BlogController::class, 'apiList']);
        $group->get('/articles/{slug}', [BlogController::class, 'apiShow']);
        $group->post('/articles', [BlogController::class, 'apiCreate']);
        $group->put('/articles/{slug}', [BlogController::class, 'apiUpdate']);
        $group->delete('/articles/{slug}', [BlogController::class, 'apiDelete']);

        // Additional API endpoints
        $group->get('/stats', [BlogController::class, 'apiStats']);
        $group->get('/search', [BlogController::class, 'apiSearch']);
        $group->get('/categories', [BlogController::class, 'apiCategories']);
        $group->get('/tags', [BlogController::class, 'apiTags']);
    });
};
