<?php

declare(strict_types=1);

use HdmBoot\Modules\Core\Monitoring\Actions\StatusAction;
use HdmBoot\Modules\Core\Monitoring\Infrastructure\Actions\HealthCheckAction;
use Slim\App;

/*
 * Monitoring Routes.
 *
 * Routes for health checks, metrics, and monitoring endpoints.
 */
return function (App $app): void {
    // Health check endpoint
    $app->get('/_status', HealthCheckAction::class);

    // API status endpoint
    $app->get('/api/status', StatusAction::class);

    // Alternative health check endpoints
    $app->get('/health', HealthCheckAction::class);
    $app->get('/healthz', HealthCheckAction::class);
    $app->get('/ping', HealthCheckAction::class);
};
