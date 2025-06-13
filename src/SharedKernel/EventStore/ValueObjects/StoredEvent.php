<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\EventStore\ValueObjects;

use MvaBootstrap\SharedKernel\CQRS\Events\DomainEventInterface;

/**
 * Stored Event Value Object.
 * 
 * Represents a domain event as stored in the event store with metadata.
 */
final class StoredEvent
{
    public function __construct(
        private readonly string $id,
        private readonly string $aggregateId,
        private readonly string $aggregateType,
        private readonly string $eventType,
        private readonly array $eventData,
        private readonly array $metadata,
        private readonly int $version,
        private readonly \DateTimeImmutable $occurredAt,
        private readonly \DateTimeImmutable $storedAt
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getStoredAt(): \DateTimeImmutable
    {
        return $this->storedAt;
    }

    /**
     * Create StoredEvent from DomainEvent.
     */
    public static function fromDomainEvent(
        DomainEventInterface $event,
        string $aggregateId,
        string $aggregateType,
        int $version,
        array $metadata = []
    ): self {
        return new self(
            id: self::generateId(),
            aggregateId: $aggregateId,
            aggregateType: $aggregateType,
            eventType: get_class($event),
            eventData: $event->toArray(),
            metadata: array_merge([
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ], $metadata),
            version: $version,
            occurredAt: $event->getOccurredAt(),
            storedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Convert to array for storage.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'aggregate_id' => $this->aggregateId,
            'aggregate_type' => $this->aggregateType,
            'event_type' => $this->eventType,
            'event_data' => json_encode($this->eventData),
            'metadata' => json_encode($this->metadata),
            'version' => $this->version,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s.u'),
            'stored_at' => $this->storedAt->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * Create from array (from storage).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            aggregateId: $data['aggregate_id'],
            aggregateType: $data['aggregate_type'],
            eventType: $data['event_type'],
            eventData: json_decode($data['event_data'], true),
            metadata: json_decode($data['metadata'], true),
            version: (int) $data['version'],
            occurredAt: new \DateTimeImmutable($data['occurred_at']),
            storedAt: new \DateTimeImmutable($data['stored_at'])
        );
    }

    /**
     * Generate unique event ID.
     */
    private static function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
