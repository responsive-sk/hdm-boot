<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Core\Documentation\Infrastructure\Actions\DocsViewerAction;
use Slim\App;

/*
 * Documentation Routes.
 *
 * Routes for viewing project documentation through web interface.
 */
return function (App $app): void {
    // Documentation viewer routes
    $app->get('/docs', DocsViewerAction::class);
    $app->get('/docs/', DocsViewerAction::class);
    $app->get('/docs/{path:.+}', DocsViewerAction::class);
};
