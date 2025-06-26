<?php

declare(strict_types=1);

/**
 * Blog Module Routes.
 *
 * Enhanced routes with API endpoints for Orbit CMS functionality.
 */

use HdmBoot\Modules\Optional\Blog\Controllers\BlogController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // Blog web interface routes
    $app->get('/blog', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $controller = new BlogController();
        $html = $controller->home();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/article/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $controller = new BlogController();
        $slug = is_string($args['slug']) ? $args['slug'] : '';
        $html = $controller->article($slug);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/categories', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $controller = new BlogController();
        $html = $controller->categories();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/tags', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $controller = new BlogController();
        $html = $controller->tags();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/about', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $controller = new BlogController();
        $html = $controller->about();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    // Blog API routes (Orbit CMS style)
    $app->group('/api/blog', function (\Slim\Routing\RouteCollectorProxy $group): void {
        // Articles CRUD
        $group->get('/articles', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiList($request, $response);
        });
        $group->get('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /* @var array<string, string> $args */
            // @phpstan-ignore-next-line
            return (new BlogController())->apiShow($request, $response, $args);
        });
        $group->post('/articles', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiCreate($request, $response);
        });
        $group->put('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /* @var array<string, string> $args */
            // @phpstan-ignore-next-line
            return (new BlogController())->apiUpdate($request, $response, $args);
        });
        $group->delete('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /* @var array<string, string> $args */
            // @phpstan-ignore-next-line
            return (new BlogController())->apiDelete($request, $response, $args);
        });

        // Additional API endpoints
        $group->get('/stats', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiStats($request, $response);
        });
        $group->get('/search', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiSearch($request, $response);
        });
        $group->get('/categories', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiCategories($request, $response);
        });
        $group->get('/tags', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogController())->apiTags($request, $response);
        });
    });
};
