<?php

declare(strict_types=1);

/**
 * Blog Module Routes.
 *
 * Enhanced routes with API endpoints for Orbit CMS functionality.
 */

use HdmBoot\Modules\Optional\Blog\Actions\BlogAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // Blog web interface routes
    $app->get('/blog', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $action = new BlogAction();
        $html = $action->home();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/article/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $action = new BlogAction();
        $slug = is_string($args['slug']) ? $args['slug'] : '';
        $html = $action->article($slug);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/search', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $action = new BlogAction();
        $queryParams = $request->getQueryParams();
        $query = isset($queryParams['q']) && is_string($queryParams['q']) ? $queryParams['q'] : '';
        $html = $action->search($query);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/categories/{category}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $action = new BlogAction();
        $category = is_string($args['category']) ? $args['category'] : '';
        $html = $action->category($category);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/tags/{tag}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $action = new BlogAction();
        $tag = is_string($args['tag']) ? $args['tag'] : '';
        $html = $action->tag($tag);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/blog/about', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $action = new BlogAction();
        $html = $action->about();
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    });

    // Blog API routes (Orbit CMS style)
    $app->group('/api/blog', function (\Slim\Routing\RouteCollectorProxy $group): void {
        // Articles CRUD
        $group->get('/articles', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiList($request, $response);
        });
        $group->get('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /** @var array<string, string> $args */
            return (new BlogAction())->apiShow($request, $response, $args);
        });
        $group->post('/articles', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiCreate($request, $response);
        });
        $group->put('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /** @var array<string, string> $args */
            return (new BlogAction())->apiUpdate($request, $response, $args);
        });
        $group->delete('/articles/{slug}', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
            /** @var array<string, string> $args */
            return (new BlogAction())->apiDelete($request, $response, $args);
        });

        // Additional API endpoints
        $group->get('/stats', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiStats($request, $response);
        });
        $group->get('/search', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiSearch($request, $response);
        });
        $group->get('/categories', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiCategories($request, $response);
        });
        $group->get('/tags', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return (new BlogAction())->apiTags($request, $response);
        });
    });
};
