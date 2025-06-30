<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Events;

/**
 * Event Listener Interface.
 *
 * Contract for event listeners that handle domain events.
 */
interface EventListener
{
    /**
     * Handle the domain event.
     */
    public function handle(DomainEvent $event): void;

    /**
     * Get events this listener supports.
     *
     * @return array<string>
     */
    public function getSupportedEvents(): array;

    /**
     * Get listener priority (higher = executed first).
     */
    public function getPriority(): int;
}
