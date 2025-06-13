<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\EventStore\Infrastructure;

use MvaBootstrap\SharedKernel\EventStore\Contracts\EventStoreInterface;
use MvaBootstrap\SharedKernel\EventStore\ValueObjects\StoredEvent;
use MvaBootstrap\SharedKernel\CQRS\Events\DomainEventInterface;

/**
 * In-Memory Event Store Implementation.
 * 
 * Stores events in memory for testing and development purposes.
 * Events are lost when the process ends.
 */
final class InMemoryEventStore implements EventStoreInterface
{
    /**
     * @var StoredEvent[]
     */
    private array $events = [];

    /**
     * @var array<string, int>
     */
    private array $aggregateVersions = [];

    public function store(DomainEventInterface $event): void
    {
        $this->storeMany([$event]);
    }

    public function storeMany(array $events): void
    {
        foreach ($events as $event) {
            if (!$event instanceof DomainEventInterface) {
                throw new \InvalidArgumentException('All events must implement DomainEventInterface');
            }

            // Extract aggregate info from event (assuming event has these methods)
            $aggregateId = method_exists($event, 'getAggregateId') 
                ? $event->getAggregateId() 
                : 'unknown';
            
            $aggregateType = method_exists($event, 'getAggregateType') 
                ? $event->getAggregateType() 
                : get_class($event);

            // Get next version
            $aggregateKey = $this->getAggregateKey($aggregateId, $aggregateType);
            $version = ($this->aggregateVersions[$aggregateKey] ?? 0) + 1;
            $this->aggregateVersions[$aggregateKey] = $version;

            // Create stored event
            $storedEvent = StoredEvent::fromDomainEvent(
                $event,
                $aggregateId,
                $aggregateType,
                $version
            );

            $this->events[] = $storedEvent;
        }
    }

    public function getEventsForAggregate(string $aggregateId, ?string $aggregateType = null): array
    {
        return array_filter($this->events, function (StoredEvent $event) use ($aggregateId, $aggregateType) {
            $matchesId = $event->getAggregateId() === $aggregateId;
            $matchesType = $aggregateType === null || $event->getAggregateType() === $aggregateType;
            
            return $matchesId && $matchesType;
        });
    }

    public function getEventsFromVersion(string $aggregateId, int $fromVersion, ?string $aggregateType = null): array
    {
        return array_filter($this->events, function (StoredEvent $event) use ($aggregateId, $fromVersion, $aggregateType) {
            $matchesId = $event->getAggregateId() === $aggregateId;
            $matchesType = $aggregateType === null || $event->getAggregateType() === $aggregateType;
            $matchesVersion = $event->getVersion() >= $fromVersion;
            
            return $matchesId && $matchesType && $matchesVersion;
        });
    }

    public function getEventsByType(string $eventType): array
    {
        return array_filter($this->events, function (StoredEvent $event) use ($eventType) {
            return $event->getEventType() === $eventType;
        });
    }

    public function getEventsByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return array_filter($this->events, function (StoredEvent $event) use ($from, $to) {
            $occurredAt = $event->getOccurredAt();
            return $occurredAt >= $from && $occurredAt <= $to;
        });
    }

    public function getAggregateVersion(string $aggregateId, ?string $aggregateType = null): int
    {
        $aggregateKey = $this->getAggregateKey($aggregateId, $aggregateType ?? 'unknown');
        return $this->aggregateVersions[$aggregateKey] ?? 0;
    }

    public function aggregateExists(string $aggregateId, ?string $aggregateType = null): bool
    {
        return $this->getAggregateVersion($aggregateId, $aggregateType) > 0;
    }

    public function getEventCount(): int
    {
        return count($this->events);
    }

    public function getEventsPaginated(int $offset = 0, int $limit = 100): array
    {
        return array_slice($this->events, $offset, $limit);
    }

    public function clear(): void
    {
        $this->events = [];
        $this->aggregateVersions = [];
    }

    /**
     * Get aggregate key for version tracking.
     */
    private function getAggregateKey(string $aggregateId, ?string $aggregateType): string
    {
        return ($aggregateType ?? 'unknown') . ':' . $aggregateId;
    }
}
