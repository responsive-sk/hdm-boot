<?php

declare(strict_types=1);

use HdmBoot\Modules\Example\Actions\ExampleAction;
use HdmBoot\Modules\Example\Infrastructure\Middleware\ExampleAuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Routing\RouteCollectorProxy;

/**
 * Example Module Routes Configuration
 *
 * This file defines the module's routes using Slim's routing system.
 *
 * @return callable(App): void
 */
return static function (App $app): void {
    $app->group('/example', function (Group $group): void {
        $group->get('', [ExampleAction::class, 'list']);

        $group->get('/{id}', [ExampleAction::class, 'show'])
            ->setArgument('id', '[0-9]+');

        $group->post('', [ExampleAction::class, 'create'])
            ->add(ExampleAuthMiddleware::class);

        $group->put('/{id}', [ExampleAction::class, 'update'])
            ->add(ExampleAuthMiddleware::class)
            ->setArgument('id', '[0-9]+');

        $group->delete('/{id}', [ExampleAction::class, 'delete'])
            ->add(ExampleAuthMiddleware::class)
            ->setArgument('id', '[0-9]+');
    });
};
