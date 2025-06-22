<?php

declare(strict_types=1);

use HdmBoot\Modules\Core\User\Actions\Api\ListUsersAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxy $group) {
        // Users API endpoints
        $group->get('/users', ListUsersAction::class);
    });
};
