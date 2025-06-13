<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

/**
 * Event Dispatcher Interface.
 *
 * Contract for dispatching domain events across modules.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch a domain event.
     */
    public function dispatchDomainEvent(DomainEvent $event): void;

    /**
     * Add event listener for specific event.
     *
     * @param callable(DomainEvent): void $listener
     */
    public function addListener(string $eventName, callable $listener): void;

    /**
     * Remove event listener.
     *
     * @param callable(DomainEvent): void $listener
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Check if event has listeners.
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get all listeners for event.
     *
     * @return array<callable>
     */
    public function getListeners(string $eventName): array;

    /**
     * Clear all listeners for event.
     */
    public function clearListeners(string $eventName): void;

    /**
     * Clear all listeners.
     */
    public function clearAllListeners(): void;
}
