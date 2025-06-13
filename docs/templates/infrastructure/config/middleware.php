<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Example\Infrastructure\Middleware\ExampleAuthMiddleware;
use MvaBootstrap\Modules\Example\Infrastructure\Middleware\ExampleValidationMiddleware;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Example Module Middleware Configuration
 *
 * This file defines the module's middleware stack.
 * Order is important - middleware will be executed in the order defined.
 *
 * @return array<class-string<MiddlewareInterface>>
 */
return [
    // Authentication middleware
    ExampleAuthMiddleware::class,

    // Validation middleware
    ExampleValidationMiddleware::class
];
