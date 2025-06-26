<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\EventStore\Contracts;

use HdmBoot\SharedKernel\CQRS\Events\DomainEventInterface;
use HdmBoot\SharedKernel\EventStore\ValueObjects\StoredEvent;

/**
 * Event Store Interface.
 *
 * Provides a contract for storing and retrieving domain events.
 * Supports event sourcing patterns and audit trails.
 */
interface EventStoreInterface
{
    /**
     * Store a domain event.
     */
    public function store(DomainEventInterface $event): void;

    /**
     * Store multiple domain events in a single transaction.
     *
     * @param DomainEventInterface[] $events
     */
    public function storeMany(array $events): void;

    /**
     * Retrieve events for a specific aggregate.
     *
     * @return StoredEvent[]
     */
    public function getEventsForAggregate(string $aggregateId, ?string $aggregateType = null): array;

    /**
     * Retrieve events from a specific version onwards.
     *
     * @return StoredEvent[]
     */
    public function getEventsFromVersion(string $aggregateId, int $fromVersion, ?string $aggregateType = null): array;

    /**
     * Retrieve all events of a specific type.
     *
     * @return StoredEvent[]
     */
    public function getEventsByType(string $eventType): array;

    /**
     * Retrieve events within a date range.
     *
     * @return StoredEvent[]
     */
    public function getEventsByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Get the current version of an aggregate.
     */
    public function getAggregateVersion(string $aggregateId, ?string $aggregateType = null): int;

    /**
     * Check if an aggregate exists.
     */
    public function aggregateExists(string $aggregateId, ?string $aggregateType = null): bool;

    /**
     * Get total number of events in the store.
     */
    public function getEventCount(): int;

    /**
     * Get events with pagination.
     *
     * @return StoredEvent[]
     */
    public function getEventsPaginated(int $offset = 0, int $limit = 100): array;

    /**
     * Clear all events (use with caution).
     */
    public function clear(): void;
}
