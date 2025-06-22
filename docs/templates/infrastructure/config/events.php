<?php

declare(strict_types=1);

use HdmBoot\SharedKernel\Events\DomainEventInterface;
use HdmBoot\SharedKernel\Events\EventListenerInterface;
use HdmBoot\Modules\Example\Domain\Events\ExampleCreatedEvent;
use HdmBoot\Modules\Example\Domain\Events\ExampleUpdatedEvent;
use HdmBoot\Modules\Example\Infrastructure\Listeners\ExampleAuditListener;
use HdmBoot\Modules\Example\Infrastructure\Listeners\ExampleNotificationListener;

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
