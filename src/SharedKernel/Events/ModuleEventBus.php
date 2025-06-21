<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Events;

use Psr\Log\LoggerInterface;

/**
 * Module Event Bus.
 *
 * Facilitates communication between modules through domain events.
 */
final class ModuleEventBus
{
    /**
     * @var array<string, array<string>>
     */
    private array $moduleSubscriptions = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Publish event from a module.
     */
    public function publish(string $sourceModule, DomainEvent $event): void
    {
        $this->logger->info('Module publishing event', [
            'source_module' => $sourceModule,
            'event_name'    => $event->getEventName(),
            'event_data'    => $event->getEventData(),
        ]);

        // Dispatch through central event dispatcher
        $this->eventDispatcher->dispatchDomainEvent($event);

        // Track module-to-module communication
        $this->trackModuleCommunication($sourceModule, $event);
    }

    /**
     * Subscribe module to specific events.
     *
     * @param array<string> $eventNames
     */
    public function subscribe(string $module, array $eventNames, callable $handler): void
    {
        foreach ($eventNames as $eventName) {
            // Register with central dispatcher
            $this->eventDispatcher->addListener($eventName, $handler);

            // Track module subscription
            if (!isset($this->moduleSubscriptions[$eventName])) {
                $this->moduleSubscriptions[$eventName] = [];
            }

            if (!in_array($module, $this->moduleSubscriptions[$eventName], true)) {
                $this->moduleSubscriptions[$eventName][] = $module;
            }

            $this->logger->debug('Module subscribed to event', [
                'module'            => $module,
                'event_name'        => $eventName,
                'total_subscribers' => count($this->moduleSubscriptions[$eventName]),
            ]);
        }
    }

    /**
     * Unsubscribe module from specific events.
     *
     * @param array<string> $eventNames
     */
    public function unsubscribe(string $module, array $eventNames, callable $handler): void
    {
        foreach ($eventNames as $eventName) {
            // Remove from central dispatcher
            $this->eventDispatcher->removeListener($eventName, $handler);

            // Remove from module subscriptions
            if (isset($this->moduleSubscriptions[$eventName])) {
                $key = array_search($module, $this->moduleSubscriptions[$eventName], true);
                if ($key !== false) {
                    unset($this->moduleSubscriptions[$eventName][$key]);
                    $this->moduleSubscriptions[$eventName] = array_values($this->moduleSubscriptions[$eventName]);
                }
            }

            $this->logger->debug('Module unsubscribed from event', [
                'module'                => $module,
                'event_name'            => $eventName,
                'remaining_subscribers' => count($this->moduleSubscriptions[$eventName] ?? []),
            ]);
        }
    }

    /**
     * Get modules subscribed to event.
     *
     * @return array<string>
     */
    public function getSubscribers(string $eventName): array
    {
        return $this->moduleSubscriptions[$eventName] ?? [];
    }

    /**
     * Get all module subscriptions.
     *
     * @return array<string, array<string>>
     */
    public function getAllSubscriptions(): array
    {
        return $this->moduleSubscriptions;
    }

    /**
     * Check if module is subscribed to event.
     */
    public function isSubscribed(string $module, string $eventName): bool
    {
        return in_array($module, $this->getSubscribers($eventName), true);
    }

    /**
     * Get event communication statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $totalEvents = array_keys($this->moduleSubscriptions);
        $totalSubscriptions = array_sum(array_map('count', $this->moduleSubscriptions));

        $moduleStats = [];
        foreach ($this->moduleSubscriptions as $eventName => $modules) {
            foreach ($modules as $module) {
                if (!isset($moduleStats[$module])) {
                    $moduleStats[$module] = 0;
                }
                ++$moduleStats[$module];
            }
        }

        return [
            'total_events'               => count($totalEvents),
            'total_subscriptions'        => $totalSubscriptions,
            'events'                     => $totalEvents,
            'module_subscription_counts' => $moduleStats,
            'event_subscriber_counts'    => array_map('count', $this->moduleSubscriptions),
        ];
    }

    /**
     * Track module-to-module communication.
     */
    private function trackModuleCommunication(string $sourceModule, DomainEvent $event): void
    {
        $eventName = $event->getEventName();
        $subscribers = $this->getSubscribers($eventName);

        if (!empty($subscribers)) {
            $this->logger->info('Inter-module communication', [
                'source_module'      => $sourceModule,
                'event_name'         => $eventName,
                'target_modules'     => $subscribers,
                'communication_type' => 'event_driven',
            ]);
        }
    }
}
