<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Events;

/**
 * Domain Event Interface.
 *
 * Base interface for all domain events in the system.
 * This is the unified interface that supports both CQRS domain events
 * and system runtime events.
 */
interface DomainEvent
{
    /**
     * Get event identifier for tracking.
     */
    public function getEventId(): string;

    /**
     * Get event name for identification.
     */
    public function getEventName(): string;

    /**
     * Get when the event occurred.
     */
    public function getOccurredAt(): \DateTimeImmutable;

    /**
     * Get event version for evolution.
     */
    public function getVersion(): int;

    /**
     * Get event payload for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Get event data for logging.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array;

    /**
     * Get event data (alias for backward compatibility).
     *
     * @return array<string, mixed>
     */
    public function getEventData(): array;
}
