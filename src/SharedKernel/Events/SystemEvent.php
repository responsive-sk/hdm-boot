<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

/**
 * System Event Interface.
 *
 * Interface for runtime system events that are used for inter-module
 * communication and notifications. These events are typically not
 * persisted and are used for immediate processing.
 */
interface SystemEvent extends DomainEvent
{
    /**
     * Get event priority for processing order.
     * Higher numbers = higher priority.
     */
    public function getPriority(): int;

    /**
     * Check if event should be processed asynchronously.
     */
    public function isAsync(): bool;

    /**
     * Get event context for processing.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;
}
