<?php

declare(strict_types=1);

use MvaBootstrap\SharedKernel\Events\DomainEventInterface;
use MvaBootstrap\SharedKernel\Events\EventListenerInterface;
use MvaBootstrap\Modules\Example\Domain\Events\ExampleCreatedEvent;
use MvaBootstrap\Modules\Example\Domain\Events\ExampleUpdatedEvent;
use MvaBootstrap\Modules\Example\Infrastructure\Listeners\ExampleAuditListener;
use MvaBootstrap\Modules\Example\Infrastructure\Listeners\ExampleNotificationListener;

/**
 * Example Module Event Configuration
 *
 * @return array<class-string<DomainEventInterface>, array<class-string<EventListenerInterface<DomainEventInterface>>>>
 */
return [
    // ExampleCreated event listeners
    ExampleCreatedEvent::class => [
        ExampleAuditListener::class,
        ExampleNotificationListener::class
    ],

    // ExampleUpdated event listeners
    ExampleUpdatedEvent::class => [
        ExampleAuditListener::class
    ]
];
