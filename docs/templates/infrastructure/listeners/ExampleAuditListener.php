<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Example\Infrastructure\Listeners;

use MvaBootstrap\Modules\Example\Domain\Events\ExampleCreatedEvent;
use MvaBootstrap\SharedKernel\Events\EventListenerInterface;
use Psr\Log\LoggerInterface;
use DateTimeInterface;

/**
 * Listener for auditing example creation events.
 *
 * @implements EventListenerInterface<ExampleCreatedEvent>
 */
final readonly class ExampleAuditListener implements EventListenerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the example created event.
     */
    public function __invoke(ExampleCreatedEvent $event): void
    {
        $this->logger->info('Example entity created', [
            'example_id' => $event->getExampleId(),  // now returns int
            'name' => $event->getName(),
            'occurred_at' => $event->getOccurredAt()->format(DateTimeInterface::ATOM),
            'audit_type' => 'example.created'
        ]);
    }
}
