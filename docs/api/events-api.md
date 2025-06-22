# Events API Reference

## Overview

This document provides a complete API reference for the Event-Driven Architecture components in the HDM Boot project.

## Core Interfaces

### DomainEvent

Base interface for all domain events in the system.

```php
interface DomainEvent
{
    /**
     * Get unique event name.
     * 
     * @return string Event name in format: {module}.{action}
     */
    public function getEventName(): string;

    /**
     * Get event data as associative array.
     * 
     * @return array<string, mixed> Event payload data
     */
    public function getEventData(): array;

    /**
     * Get event occurrence timestamp.
     * 
     * @return \DateTimeImmutable When the event occurred
     */
    public function getOccurredAt(): \DateTimeImmutable;
}
```

### EventDispatcherInterface

Central event dispatching contract.

```php
interface EventDispatcherInterface
{
    /**
     * Dispatch domain event to all registered listeners.
     * 
     * @param DomainEvent $event Event to dispatch
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * Register event listener for specific event.
     * 
     * @param string $eventName Event name to listen for
     * @param callable $listener Listener callable
     */
    public function addListener(string $eventName, callable $listener): void;

    /**
     * Remove event listener.
     * 
     * @param string $eventName Event name
     * @param callable $listener Listener to remove
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Check if event has registered listeners.
     * 
     * @param string $eventName Event name to check
     * @return bool True if listeners exist
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get all listeners for specific event.
     * 
     * @param string $eventName Event name
     * @return array<callable> Array of listener callables
     */
    public function getListeners(string $eventName): array;

    /**
     * Clear all listeners for specific event.
     * 
     * @param string $eventName Event name
     */
    public function clearListeners(string $eventName): void;

    /**
     * Clear all registered listeners.
     */
    public function clearAllListeners(): void;
}
```

### EventListener

Contract for event listener implementations.

```php
interface EventListener
{
    /**
     * Handle domain event.
     * 
     * @param DomainEvent $event Event to handle
     */
    public function handle(DomainEvent $event): void;

    /**
     * Get list of events this listener supports.
     * 
     * @return array<string> Array of event names
     */
    public function getSupportedEvents(): array;

    /**
     * Get listener execution priority.
     * Higher values execute first.
     * 
     * @return int Priority value (0-1000)
     */
    public function getPriority(): int;
}
```

## ModuleEventBus API

### Class: ModuleEventBus

Facilitates inter-module communication through events.

#### Methods

##### publish()
```php
public function publish(string $sourceModule, DomainEvent $event): void
```

**Parameters:**
- `$sourceModule` - Name of the module publishing the event
- `$event` - Domain event to publish

**Description:** Publishes an event from a specific module to all subscribers.

**Example:**
```php
$moduleEventBus->publish('Language', $localeChangedEvent);
```

##### subscribe()
```php
public function subscribe(string $module, array $eventNames, callable $handler): void
```

**Parameters:**
- `$module` - Name of the subscribing module
- `$eventNames` - Array of event names to subscribe to
- `$handler` - Callable to handle the events

**Description:** Subscribes a module to specific events.

**Example:**
```php
$moduleEventBus->subscribe(
    'User',
    ['language.locale_changed'],
    [$userPreferencesListener, 'handle']
);
```

##### unsubscribe()
```php
public function unsubscribe(string $module, array $eventNames, callable $handler): void
```

**Parameters:**
- `$module` - Name of the module to unsubscribe
- `$eventNames` - Array of event names to unsubscribe from
- `$handler` - Handler to remove

**Description:** Unsubscribes a module from specific events.

##### getSubscribers()
```php
public function getSubscribers(string $eventName): array
```

**Parameters:**
- `$eventName` - Event name to check

**Returns:** Array of module names subscribed to the event

**Example:**
```php
$subscribers = $moduleEventBus->getSubscribers('language.locale_changed');
// Returns: ['Language', 'User', 'Analytics']
```

##### getAllSubscriptions()
```php
public function getAllSubscriptions(): array
```

**Returns:** Complete mapping of events to subscribed modules

**Example:**
```php
$subscriptions = $moduleEventBus->getAllSubscriptions();
// Returns:
// [
//     'language.locale_changed' => ['Language', 'User'],
//     'user.created' => ['Security', 'Analytics']
// ]
```

##### isSubscribed()
```php
public function isSubscribed(string $module, string $eventName): bool
```

**Parameters:**
- `$module` - Module name to check
- `$eventName` - Event name to check

**Returns:** True if module is subscribed to the event

##### getStatistics()
```php
public function getStatistics(): array
```

**Returns:** Event bus statistics and metrics

**Example Response:**
```php
[
    'total_events' => 5,
    'total_subscriptions' => 12,
    'events' => ['language.locale_changed', 'user.created', ...],
    'module_subscription_counts' => [
        'Language' => 2,
        'User' => 3,
        'Security' => 4
    ],
    'event_subscriber_counts' => [
        'language.locale_changed' => 3,
        'user.created' => 2
    ]
]
```

## EventDispatcher API

### Class: EventDispatcher

Main implementation of EventDispatcherInterface.

#### Constructor
```php
public function __construct(LoggerInterface $logger)
```

**Parameters:**
- `$logger` - PSR-3 logger for event tracking

#### Methods

All methods implement EventDispatcherInterface. Additional features:

- **Automatic Error Handling**: Failed listeners don't stop other listeners
- **Performance Tracking**: Execution time monitoring
- **Comprehensive Logging**: Detailed event flow logging
- **Statistics Collection**: Success/failure metrics

#### Logging Output

The EventDispatcher logs the following information:

##### Debug Level
```
app.DEBUG: Dispatching domain event {
    "event_name": "language.locale_changed",
    "event_data": {...},
    "occurred_at": "2025-06-11 11:02:46"
}

app.DEBUG: Event listener executed successfully {
    "event_name": "language.locale_changed",
    "listener": "LocaleChangedListener::handle"
}
```

##### Info Level
```
app.INFO: Domain event dispatched {
    "event_name": "language.locale_changed",
    "listeners_count": 2,
    "success_count": 2,
    "error_count": 0
}
```

##### Error Level
```
app.ERROR: Event listener failed {
    "event_name": "language.locale_changed",
    "listener": "FailingListener::handle",
    "error": "Database connection failed",
    "file": "/path/to/listener.php",
    "line": 42
}
```

## Event Naming Conventions

### Standard Format
```
{module}.{entity}.{action}
{module}.{action}
```

### Examples
- `language.locale_changed` - Language module, locale changed
- `user.created` - User module, user created
- `security.login_attempt` - Security module, login attempted
- `order.payment.completed` - Order module, payment completed

### Guidelines
- Use lowercase with underscores
- Be descriptive but concise
- Follow hierarchical structure
- Use past tense for actions (created, updated, deleted)

## Event Data Standards

### Required Fields
All events should include:
```php
[
    'occurred_at' => '2025-06-11 11:02:46',  // ISO 8601 timestamp
    'event_version' => '1.0',                // Event schema version
]
```

### Recommended Fields
```php
[
    'user_id' => 'user123',                  // Acting user (if applicable)
    'session_id' => 'sess_abc123',           // Session identifier
    'correlation_id' => 'corr_xyz789',       // Request correlation ID
    'source_ip' => '192.168.1.100',          // Source IP address
]
```

### Entity Events
For entity-related events:
```php
[
    'entity_id' => 'entity123',              // Entity identifier
    'entity_type' => 'User',                 // Entity class/type
    'previous_state' => [...],               // Previous entity state
    'current_state' => [...],                // Current entity state
    'changed_fields' => ['email', 'name'],   // Modified fields
]
```

## Error Handling

### Listener Failures
- Individual listener failures don't stop event processing
- Errors are logged with full context
- Other listeners continue to execute
- Statistics track success/failure rates

### Event Validation
Events are validated for:
- Required interface implementation
- Event name format
- Data structure consistency
- Timestamp validity

### Recovery Strategies
- **Retry Logic**: Implement in listeners for transient failures
- **Circuit Breaker**: Disable failing listeners temporarily
- **Dead Letter Queue**: Store permanently failed events
- **Compensation**: Implement compensating actions for failures

## Performance Considerations

### Listener Guidelines
- Keep listeners lightweight (< 100ms execution time)
- Avoid blocking operations
- Use async processing for heavy operations
- Implement timeouts for external calls

### Event Size Limits
- Keep event data under 64KB
- Use references for large objects
- Consider event splitting for complex data

### Monitoring Metrics
The system tracks:
- Event dispatch frequency
- Listener execution time
- Success/failure rates
- Memory usage
- Queue depths (if using async processing)

## Testing Support

### Event Assertions
```php
// Test that event was dispatched
$this->assertEventDispatched('language.locale_changed');

// Test event data
$this->assertEventDispatchedWith('user.created', [
    'user_id' => 'user123',
    'email' => 'test@example.com'
]);

// Test listener was called
$this->assertListenerCalled(LocaleChangedListener::class);
```

### Mock Implementations
```php
// Mock event dispatcher
$mockDispatcher = $this->createMock(EventDispatcherInterface::class);
$mockDispatcher->expects($this->once())
    ->method('dispatch')
    ->with($this->isInstanceOf(LocaleChangedEvent::class));

// Mock module event bus
$mockEventBus = $this->createMock(ModuleEventBus::class);
$mockEventBus->expects($this->once())
    ->method('publish')
    ->with('Language', $this->isInstanceOf(LocaleChangedEvent::class));
```

## Configuration Reference

### Container Bindings
```php
// config/services/events.php
return [
    EventDispatcherInterface::class => EventDispatcher::class,
    ModuleEventBus::class => \DI\autowire(),
    EventBootstrap::class => \DI\autowire(),
    
    // Listeners
    LocaleChangedListener::class => \DI\autowire(),
];
```

### Bootstrap Configuration
```php
// boot/App.php
private function setupEventSystem(): void
{
    $eventBootstrap = new EventBootstrap($this->container, $logger);
    $eventBootstrap->bootstrap();
}
```

### Environment Variables
```bash
# .env
APP_DEBUG=true                    # Enable debug logging
LOG_LEVEL=debug                   # Set log level
EVENT_ASYNC_PROCESSING=false      # Enable async event processing
EVENT_RETRY_ATTEMPTS=3            # Number of retry attempts
EVENT_TIMEOUT=30                  # Event processing timeout (seconds)
```

This API reference provides complete documentation for implementing and using the Event-Driven Architecture in the HDM Boot project.
