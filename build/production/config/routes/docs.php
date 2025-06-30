<?php

declare(strict_types=1);

use HdmBoot\Modules\Optional\Docs\Actions\DocsAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

/*
 * Documentation Routes.
 *
 * Theme-aware documentation routes with modern UI.
 */
return function (App $app): void {
    // Documentation viewer routes
    $app->get('/docs[/{path:.*}]', function (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $docsAction = new DocsAction();
        $path = $args['path'] ?? '';
        $html = $docsAction->index($path);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('docs');
};
