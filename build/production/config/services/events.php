<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\SharedKernel\Events\EventBootstrap;
use HdmBoot\SharedKernel\Events\EventDispatcher;
use HdmBoot\SharedKernel\Events\EventDispatcherInterface;
use HdmBoot\SharedKernel\Events\ModuleEventBus;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\Log\LoggerInterface;

/*
 * Event System Service Configuration.
 *
 * Configuration for Event-Driven Architecture components.
 */
return [
    // === EVENT DISPATCHER ===

    // Event Dispatcher Interface
    EventDispatcherInterface::class => function (Container $container): EventDispatcherInterface {
        return new EventDispatcher(
            $container->get(LoggerInterface::class)
        );
    },

    // Event Dispatcher Implementation
    EventDispatcher::class => function (Container $container): EventDispatcher {
        return new EventDispatcher(
            $container->get(LoggerInterface::class)
        );
    },

    // PSR-14 Event Dispatcher Interface
    PsrEventDispatcherInterface::class => function (Container $container): PsrEventDispatcherInterface {
        return $container->get(EventDispatcher::class);
    },

    // === MODULE EVENT BUS ===

    // Module Event Bus
    ModuleEventBus::class => function (Container $container): ModuleEventBus {
        return new ModuleEventBus(
            $container->get(EventDispatcherInterface::class),
            $container->get(LoggerInterface::class)
        );
    },

    // === EVENT BOOTSTRAP ===

    // Event Bootstrap
    EventBootstrap::class => function (Container $container): EventBootstrap {
        return new EventBootstrap(
            $container,
            $container->get(LoggerInterface::class)
        );
    },

    // === EVENT LISTENERS ===

    // Language Module Listeners
    \HdmBoot\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener(
            $container->get(LoggerInterface::class)
        );
    },
];
