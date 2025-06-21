<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Unified Event Dispatcher.
 *
 * Central event bus for dispatching both domain events (CQRS-level)
 * and system events (runtime notifications) across modules.
 * Implements PSR-14 EventDispatcherInterface for compatibility.
 */
final class EventDispatcher implements EventDispatcherInterface, PsrEventDispatcherInterface
{
    /**
     * @var array<string, array<callable>>
     */
    private array $listeners = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Dispatch a domain event (PSR-14 compatible).
     */
    public function dispatch(object $event): object
    {
        if ($event instanceof DomainEvent) {
            $this->dispatchDomainEvent($event);
        } else {
            $this->dispatchGenericEvent($event);
        }

        return $event;
    }

    /**
     * Dispatch a domain event.
     */
    public function dispatchDomainEvent(DomainEvent $event): void
    {
        $eventName = $event->getEventName();

        $this->logger->debug('Dispatching domain event', [
            'event_name'  => $eventName,
            'event_id'    => $event->getEventId(),
            'event_data'  => $event->getEventData(),
            'occurred_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
            'version'     => $event->getVersion(),
        ]);

        if (!isset($this->listeners[$eventName])) {
            $this->logger->debug('No listeners registered for event', [
                'event_name' => $eventName,
            ]);

            return;
        }

        $listeners = $this->listeners[$eventName];
        $successCount = 0;
        $errorCount = 0;

        foreach ($listeners as $listener) {
            try {
                $listener($event);
                ++$successCount;

                $this->logger->debug('Event listener executed successfully', [
                    'event_name' => $eventName,
                    'listener'   => $this->getListenerName($listener),
                ]);
            } catch (\Throwable $e) {
                ++$errorCount;

                $this->logger->error('Event listener failed', [
                    'event_name' => $eventName,
                    'listener'   => $this->getListenerName($listener),
                    'error'      => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]);
            }
        }

        $this->logger->info('Domain event dispatched', [
            'event_name'      => $eventName,
            'listeners_count' => count($listeners),
            'success_count'   => $successCount,
            'error_count'     => $errorCount,
        ]);
    }

    /**
     * Dispatch a generic event (non-domain).
     */
    private function dispatchGenericEvent(object $event): void
    {
        $eventName = get_class($event);

        $this->logger->debug('Dispatching generic event', [
            'event_name'  => $eventName,
            'event_class' => get_class($event),
        ]);

        if (!isset($this->listeners[$eventName])) {
            $this->logger->debug('No listeners registered for generic event', [
                'event_name' => $eventName,
            ]);

            return;
        }

        $listeners = $this->listeners[$eventName];
        $successCount = 0;
        $errorCount = 0;

        foreach ($listeners as $listener) {
            try {
                $listener($event);
                ++$successCount;

                $this->logger->debug('Generic event listener executed successfully', [
                    'event_name' => $eventName,
                    'listener'   => $this->getListenerName($listener),
                ]);
            } catch (\Throwable $e) {
                ++$errorCount;

                $this->logger->error('Generic event listener failed', [
                    'event_name' => $eventName,
                    'listener'   => $this->getListenerName($listener),
                    'error'      => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]);
            }
        }

        $this->logger->info('Generic event dispatched', [
            'event_name'      => $eventName,
            'listeners_count' => count($listeners),
            'success_count'   => $successCount,
            'error_count'     => $errorCount,
        ]);
    }

    /**
     * Add event listener for specific event.
     */
    public function addListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;

        $this->logger->debug('Event listener registered', [
            'event_name'      => $eventName,
            'listener'        => $this->getListenerName($listener),
            'total_listeners' => count($this->listeners[$eventName]),
        ]);
    }

    /**
     * Remove event listener.
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $key = array_search($listener, $this->listeners[$eventName], true);
        if ($key !== false) {
            unset($this->listeners[$eventName][$key]);
            $this->listeners[$eventName] = array_values($this->listeners[$eventName]);

            $this->logger->debug('Event listener removed', [
                'event_name'          => $eventName,
                'listener'            => $this->getListenerName($listener),
                'remaining_listeners' => count($this->listeners[$eventName]),
            ]);
        }
    }

    /**
     * Check if event has listeners.
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && !empty($this->listeners[$eventName]);
    }

    /**
     * Get all listeners for event.
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * Clear all listeners for event.
     */
    public function clearListeners(string $eventName): void
    {
        if (isset($this->listeners[$eventName])) {
            $count = count($this->listeners[$eventName]);
            unset($this->listeners[$eventName]);

            $this->logger->debug('Event listeners cleared', [
                'event_name'    => $eventName,
                'cleared_count' => $count,
            ]);
        }
    }

    /**
     * Clear all listeners.
     */
    public function clearAllListeners(): void
    {
        $totalCount = array_sum(array_map('count', $this->listeners));
        $this->listeners = [];

        $this->logger->debug('All event listeners cleared', [
            'cleared_count' => $totalCount,
        ]);
    }

    /**
     * Get listener name for logging.
     */
    private function getListenerName(callable $listener): string
    {
        if (is_array($listener)) {
            $class = is_object($listener[0]) ? get_class($listener[0]) : $listener[0];
            $method = $listener[1] ?? 'unknown';

            $className = is_string($class) ? $class : 'unknown';
            $methodName = is_string($method) ? $method : 'unknown';

            return $className . '::' . $methodName;
        }

        if (is_object($listener)) {
            return get_class($listener) . '::__invoke';
        }

        if (is_string($listener)) {
            return $listener;
        }

        return 'anonymous';
    }
}
