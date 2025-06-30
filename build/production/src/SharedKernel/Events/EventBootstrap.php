<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Events;

use DI\Container;
use Psr\Log\LoggerInterface;

/**
 * Event Bootstrap.
 *
 * Bootstraps the event system and registers event listeners.
 */
final class EventBootstrap
{
    public function __construct(
        private readonly Container $container,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Bootstrap the event system.
     */
    public function bootstrap(): void
    {
        $this->logger->info('Starting event system bootstrap');

        // Get event dispatcher and module event bus
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $moduleEventBus = $this->container->get(ModuleEventBus::class);

        if (!$eventDispatcher instanceof EventDispatcherInterface) {
            throw new \RuntimeException('EventDispatcher service not properly configured');
        }

        if (!$moduleEventBus instanceof ModuleEventBus) {
            throw new \RuntimeException('ModuleEventBus service not properly configured');
        }

        // Register core event listeners
        $this->registerCoreEventListeners($eventDispatcher, $moduleEventBus);

        // Register module event listeners
        $this->registerModuleEventListeners($eventDispatcher, $moduleEventBus);

        $this->logger->info('Event system bootstrap completed', [
            'registered_listeners' => $this->getRegisteredListenersCount($eventDispatcher),
            'module_subscriptions' => count($moduleEventBus->getAllSubscriptions()),
        ]);
    }

    /**
     * Register core event listeners.
     */
    private function registerCoreEventListeners(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // Core system event listeners can be registered here
        $this->logger->debug('Core event listeners registered');
    }

    /**
     * Register module event listeners.
     */
    private function registerModuleEventListeners(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // Language Module Events
        $this->registerLanguageModuleEvents($eventDispatcher, $moduleEventBus);

        // User Module Events (if needed)
        $this->registerUserModuleEvents($eventDispatcher, $moduleEventBus);

        // Security Module Events (if needed)
        $this->registerSecurityModuleEvents($eventDispatcher, $moduleEventBus);
    }

    /**
     * Register Language module events.
     */
    private function registerLanguageModuleEvents(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        try {
            // Register LocaleChangedListener
            $localeChangedListener = $this->container->get(
                \HdmBoot\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener::class
            );

            if (
                $localeChangedListener !== null &&
                is_object($localeChangedListener) &&
                method_exists($localeChangedListener, 'handle')
            ) {
                $listener = [$localeChangedListener, 'handle'];
                // @phpstan-ignore-next-line function.alreadyNarrowedType
                if (is_callable($listener)) {
                    $eventDispatcher->addListener('language.locale_changed', $listener);

                    // Subscribe Language module to its own events
                    $moduleEventBus->subscribe(
                        'Language',
                        ['language.locale_changed', 'language.translation_added'],
                        $listener
                    );
                }
            }

            $this->logger->debug('Language module events registered', [
                'listeners' => ['LocaleChangedListener'],
                'events'    => ['language.locale_changed', 'language.translation_added'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to register Language module events', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
        }
    }

    /**
     * Register User module events.
     */
    private function registerUserModuleEvents(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // User module event listeners can be registered here
        // Example: user.created, user.updated, user.deleted events

        $this->logger->debug('User module events registered');
    }

    /**
     * Register Security module events.
     */
    private function registerSecurityModuleEvents(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // Security module event listeners can be registered here
        // Example: security.login_attempt, security.logout, security.password_changed events

        $this->logger->debug('Security module events registered');
    }

    /**
     * Get count of registered listeners.
     */
    private function getRegisteredListenersCount(EventDispatcherInterface $eventDispatcher): int
    {
        $count = 0;

        // Count listeners for known events
        $knownEvents = [
            'language.locale_changed',
            'language.translation_added',
            'user.created',
            'user.updated',
            'security.login_attempt',
        ];

        foreach ($knownEvents as $eventName) {
            $count += count($eventDispatcher->getListeners($eventName));
        }

        return $count;
    }
}
