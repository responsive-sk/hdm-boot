# Event-Driven Architecture

## Overview

The HDM Boot project implements a comprehensive Event-Driven Architecture (EDA) that enables loose coupling between modules through domain events. This architecture facilitates asynchronous communication, improves scalability, and maintains clean separation of concerns.

## Core Components

### 1. Event Infrastructure

#### EventDispatcherInterface
Central contract for dispatching domain events across the system.

```php
interface EventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;
    public function addListener(string $eventName, callable $listener): void;
    public function removeListener(string $eventName, callable $listener): void;
    public function hasListeners(string $eventName): bool;
}
```

#### EventDispatcher
Main implementation providing centralized event dispatching with logging and error handling.

**Features:**
- ✅ Automatic error handling for failed listeners
- ✅ Comprehensive logging of event flow
- ✅ Performance tracking and metrics
- ✅ Support for multiple listeners per event

#### ModuleEventBus
Facilitates inter-module communication through domain events.

**Features:**
- ✅ Module subscription management
- ✅ Event publishing from modules
- ✅ Communication tracking and statistics
- ✅ Module isolation enforcement

### 2. Domain Events

#### DomainEvent Interface
All domain events must implement this interface:

```php
interface DomainEvent
{
    public function getEventName(): string;
    public function getEventData(): array;
    public function getOccurredAt(): \DateTimeImmutable;
}
```

#### Event Naming Convention
Events follow a hierarchical naming pattern:
- `{module}.{entity}.{action}` - e.g., `language.locale_changed`
- `{module}.{action}` - e.g., `user.created`

### 3. Event Listeners

#### EventListener Interface
Contract for event listeners:

```php
interface EventListener
{
    public function handle(DomainEvent $event): void;
    public function getSupportedEvents(): array;
    public function getPriority(): int;
}
```

## Implementation Guide

### Creating Domain Events

#### Step 1: Define the Event
```php
final readonly class LocaleChangedEvent implements DomainEvent
{
    public function __construct(
        public ?string $userId,
        public Locale $previousLocale,
        public Locale $newLocale,
        public \DateTimeImmutable $occurredAt
    ) {}

    public static function create(
        ?string $userId,
        Locale $previousLocale,
        Locale $newLocale
    ): self {
        return new self(
            $userId,
            $previousLocale,
            $newLocale,
            new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'language.locale_changed';
    }

    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'previous_locale' => $this->previousLocale->toString(),
            'new_locale' => $this->newLocale->toString(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

#### Step 2: Create Event Listener
```php
final class LocaleChangedListener implements EventListener
{
    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof LocaleChangedEvent) {
            return;
        }

        // Handle the event
        $this->updateUserSession($event);
        $this->clearLocaleCaches($event);
        $this->logAnalytics($event);
    }

    public function getSupportedEvents(): array
    {
        return ['language.locale_changed'];
    }

    public function getPriority(): int
    {
        return 100;
    }
}
```

#### Step 3: Register Listener
```php
// In EventBootstrap
$eventDispatcher->addListener(
    'language.locale_changed',
    [$localeChangedListener, 'handle']
);

$moduleEventBus->subscribe(
    'Language',
    ['language.locale_changed'],
    [$localeChangedListener, 'handle']
);
```

### Publishing Events

#### From Domain Services
```php
class LocaleService
{
    public function changeLocale(string $userId, Locale $newLocale): void
    {
        $previousLocale = $this->getCurrentLocale($userId);
        
        // Perform the business logic
        $this->updateUserLocale($userId, $newLocale);
        
        // Publish domain event
        $event = LocaleChangedEvent::create($userId, $previousLocale, $newLocale);
        $this->moduleEventBus->publish('Language', $event);
    }
}
```

#### From Application Actions
```php
class LanguageSettingsAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // Process request
        $newLocale = $this->parseLocale($request);
        
        // Change locale (this will publish the event)
        $this->localeService->changeLocale($userId, $newLocale);
        
        return $this->createResponse(['success' => true]);
    }
}
```

## Event Flow

### 1. Event Creation
```
Domain Service → Create Event → Validate Event Data
```

### 2. Event Publishing
```
ModuleEventBus::publish() → EventDispatcher::dispatch() → Execute Listeners
```

### 3. Event Processing
```
Listener 1 (Priority 100) → Listener 2 (Priority 50) → Log Results
```

### 4. Error Handling
```
Listener Failure → Log Error → Continue with Other Listeners → Report Statistics
```

## Module Communication

### Inter-Module Events

#### Language Module Events
- `language.locale_changed` - User changed their locale
- `language.translation_added` - New translation was added
- `language.translation_updated` - Translation was modified

#### User Module Events
- `user.created` - New user registered
- `user.updated` - User profile updated
- `user.deleted` - User account deleted

#### Security Module Events
- `security.login_attempt` - User attempted to log in
- `security.login_success` - Successful login
- `security.logout` - User logged out
- `security.password_changed` - Password was changed

### Event Subscription Examples

#### User Module Listening to Language Events
```php
// User module can react to locale changes
$moduleEventBus->subscribe(
    'User',
    ['language.locale_changed'],
    [$userPreferencesListener, 'handle']
);
```

#### Security Module Listening to User Events
```php
// Security module can react to user creation
$moduleEventBus->subscribe(
    'Security',
    ['user.created'],
    [$securitySetupListener, 'handle']
);
```

## Best Practices

### 1. Event Design
- ✅ **Immutable Events**: Events should be immutable value objects
- ✅ **Rich Domain Events**: Include all necessary data in the event
- ✅ **Clear Naming**: Use descriptive, hierarchical event names
- ✅ **Backward Compatibility**: Don't remove fields from existing events

### 2. Listener Implementation
- ✅ **Idempotent**: Listeners should be safe to execute multiple times
- ✅ **Fast Execution**: Keep listener logic lightweight
- ✅ **Error Handling**: Handle exceptions gracefully
- ✅ **Single Responsibility**: One listener per concern

### 3. Performance Considerations
- ✅ **Async Processing**: Consider async processing for heavy operations
- ✅ **Batch Processing**: Group related events when possible
- ✅ **Circuit Breaker**: Implement circuit breaker for external services
- ✅ **Monitoring**: Track event processing performance

### 4. Testing
- ✅ **Unit Tests**: Test event creation and listener logic
- ✅ **Integration Tests**: Test event flow end-to-end
- ✅ **Event Assertions**: Verify events are published correctly
- ✅ **Listener Mocking**: Mock listeners for isolated testing

## Monitoring and Observability

### Event Metrics
The system automatically tracks:
- ✅ **Event Count**: Number of events dispatched
- ✅ **Listener Success Rate**: Percentage of successful listener executions
- ✅ **Processing Time**: Time taken to process events
- ✅ **Error Rate**: Frequency of listener failures

### Logging
All events are logged with:
- ✅ **Event Details**: Name, data, timestamp
- ✅ **Module Communication**: Source and target modules
- ✅ **Listener Execution**: Success/failure status
- ✅ **Performance Metrics**: Execution time and resource usage

### Statistics API
```php
$stats = $moduleEventBus->getStatistics();
// Returns:
// - total_events: 2
// - total_subscriptions: 2
// - module_subscription_counts: ['Language' => 2]
// - event_subscriber_counts: ['language.locale_changed' => 1]
```

## Configuration

### Event System Bootstrap
The event system is automatically bootstrapped during application initialization:

```php
// In boot/App.php
private function setupEventSystem(): void
{
    $eventBootstrap = new EventBootstrap($this->container, $logger);
    $eventBootstrap->bootstrap();
}
```

### Container Configuration
Event components are registered in `config/services/events.php`:

```php
return [
    EventDispatcherInterface::class => EventDispatcher::class,
    ModuleEventBus::class => \DI\autowire(),
    EventBootstrap::class => \DI\autowire(),
    // ... listener registrations
];
```

## Troubleshooting

### Common Issues

#### Events Not Being Processed
1. Check if listeners are registered in EventBootstrap
2. Verify event names match exactly
3. Ensure EventBootstrap is called during app initialization

#### Listener Failures
1. Check application logs for error details
2. Verify listener dependencies are available
3. Test listener logic in isolation

#### Performance Issues
1. Monitor event processing time in logs
2. Consider async processing for heavy operations
3. Optimize listener implementations

### Debug Mode
Enable debug logging to see detailed event flow:
```php
// In .env
APP_DEBUG=true
LOG_LEVEL=debug
```

This will log:
- Event dispatching details
- Listener execution results
- Module communication tracking
- Performance metrics

## Real-World Example

### Complete Event Flow Example

#### 1. User Changes Language
```php
// User clicks language selector in UI
POST /api/language
{
    "locale": "sk_SK"
}
```

#### 2. Action Processes Request
```php
class LanguageSettingsAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $newLocale = Locale::fromString($data['locale']);

        // This will trigger the event
        $this->localeService->changeLocale($userId, $newLocale);

        return $this->createSuccessResponse();
    }
}
```

#### 3. Service Publishes Event
```php
class LocaleService
{
    public function changeLocale(string $userId, Locale $newLocale): void
    {
        $previousLocale = $this->getCurrentLocale($userId);

        // Update the locale
        $this->userRepository->updateLocale($userId, $newLocale);

        // Publish domain event
        $event = LocaleChangedEvent::create($userId, $previousLocale, $newLocale);
        $this->moduleEventBus->publish('Language', $event);
    }
}
```

#### 4. Multiple Listeners React
```php
// LocaleChangedListener handles the event
class LocaleChangedListener
{
    public function handle(DomainEvent $event): void
    {
        // Update session
        $_SESSION['locale'] = $event->newLocale->toString();

        // Clear caches
        $this->cacheManager->clear("translations.{$event->previousLocale}");
        $this->cacheManager->clear("translations.{$event->newLocale}");

        // Log analytics
        $this->analytics->track('locale_changed', [
            'user_id' => $event->userId,
            'from' => $event->previousLocale->toString(),
            'to' => $event->newLocale->toString(),
        ]);
    }
}
```

#### 5. System Logs Everything
```
🚀 [11:02:46] app.INFO: Module publishing event {
    "source_module": "Language",
    "event_name": "language.locale_changed",
    "event_data": {
        "user_id": "test_user_123",
        "previous_locale": "en_US",
        "new_locale": "sk_SK",
        "occurred_at": "2025-06-11 11:02:46"
    }
}

🚀 [11:02:46] app.INFO: Processing locale change {
    "user_id": "test_user_123",
    "previous_locale": "en_US",
    "new_locale": "sk_SK"
}

🚀 [11:02:46] app.INFO: Domain event dispatched {
    "event_name": "language.locale_changed",
    "listeners_count": 2,
    "success_count": 2,
    "error_count": 0
}
```

## Future Enhancements

### Planned Features
- ✅ **Async Event Processing**: Queue-based event processing
- ✅ **Event Sourcing**: Store events for replay and audit
- ✅ **Saga Pattern**: Coordinate complex business processes
- ✅ **Event Versioning**: Handle event schema evolution
- ✅ **Dead Letter Queue**: Handle permanently failed events

### Integration Opportunities
- ✅ **Message Queues**: RabbitMQ, Redis, AWS SQS integration
- ✅ **Event Stores**: EventStore, Apache Kafka integration
- ✅ **Monitoring**: Prometheus, Grafana metrics integration
- ✅ **Tracing**: Distributed tracing for event flows
