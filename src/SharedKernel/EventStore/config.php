<?php

/**
 * EventStore Configuration
 *
 * Provides DI container bindings for EventStore infrastructure.
 */

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\SharedKernel\EventStore\Contracts\EventStoreInterface;
use MvaBootstrap\SharedKernel\EventStore\Infrastructure\DatabaseEventStore;
use MvaBootstrap\SharedKernel\EventStore\Infrastructure\InMemoryEventStore;

return [
    // === SETTINGS ===

    'settings' => [
        'event_store' => [
            'driver' => 'database', // 'database' or 'memory'
            'table_name' => 'event_store',
            'auto_create_table' => true,
        ],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // EventStore Interface - chooses implementation based on settings
        EventStoreInterface::class => function (Container $container): EventStoreInterface {
            $settings = $container->get('settings');
            if (!is_array($settings) || !is_array($settings['event_store'] ?? null)) {
                throw new \RuntimeException('EventStore settings not properly configured');
            }

            $driver = $settings['event_store']['driver'] ?? 'database';
            if (!is_string($driver)) {
                throw new \RuntimeException('EventStore driver must be a string');
            }

            $eventStore = match ($driver) {
                'memory' => $container->get(InMemoryEventStore::class),
                'database' => $container->get(DatabaseEventStore::class),
                default => throw new \InvalidArgumentException("Unsupported EventStore driver: $driver"),
            };

            if (!$eventStore instanceof EventStoreInterface) {
                throw new \RuntimeException('EventStore implementation not properly configured');
            }

            return $eventStore;
        },

        // In-Memory EventStore
        InMemoryEventStore::class => function (): InMemoryEventStore {
            return new InMemoryEventStore();
        },

        // Database EventStore
        DatabaseEventStore::class => function (Container $container): DatabaseEventStore {
            $pdo = $container->get(\PDO::class);
            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException('PDO service not properly configured');
            }
            return new DatabaseEventStore($pdo);
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        EventStoreInterface::class,
    ],

    // === INITIALIZATION ===

    'initialize' => function (): void {
        // EventStore initialization if needed
        // Table creation is handled by DatabaseEventStore constructor
    },

    // === HEALTH CHECK ===

    'health_check' => function (): array {
        return [
            'eventstore_available' => true,
            'drivers_available' => [
                'memory' => class_exists(InMemoryEventStore::class),
                'database' => class_exists(DatabaseEventStore::class),
            ],
            'last_check' => date('Y-m-d H:i:s'),
        ];
    },

    // === MODULE INFO ===

    'info' => [
        'name' => 'EventStore',
        'description' => 'Event Store infrastructure for Event Sourcing and Domain Events',
        'version' => '1.0.0',
        'features' => [
            'Multiple storage drivers (Database, In-Memory)',
            'Event versioning and aggregate tracking',
            'Event querying by type, date range, aggregate',
            'Transaction support for event batches',
            'Automatic table creation',
            'Event metadata tracking',
            'Pagination support',
        ],
        'drivers' => [
            'database' => 'Persistent storage using PDO',
            'memory' => 'In-memory storage for testing',
        ],
    ],
];
