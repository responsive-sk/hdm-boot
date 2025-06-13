<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

/**
 * Abstract Domain Event.
 *
 * Base implementation for domain events providing common functionality.
 */
abstract class AbstractDomainEvent implements DomainEvent
{
    private readonly string $eventId;
    private readonly \DateTimeImmutable $occurredAt;
    private readonly int $version;

    public function __construct(
        int $version = 1,
        ?\DateTimeImmutable $occurredAt = null,
        ?string $eventId = null
    ) {
        $this->eventId = $eventId ?? $this->generateEventId();
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
        $this->version = $version;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s.u'),
            'version' => $this->getVersion(),
            'data' => $this->getEventData(),
        ];
    }

    public function toLogArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s'),
            'version' => $this->getVersion(),
            'data' => $this->getEventData(),
        ];
    }

    /**
     * Generate unique event ID.
     */
    private function generateEventId(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('Ymd-His'),
            uniqid(),
            bin2hex(random_bytes(4))
        );
    }

    /**
     * Get event name - must be implemented by concrete classes.
     */
    abstract public function getEventName(): string;

    /**
     * Get event data - must be implemented by concrete classes.
     *
     * @return array<string, mixed>
     */
    abstract public function getEventData(): array;
}
