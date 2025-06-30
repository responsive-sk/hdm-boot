# Event Integration Guide

Komplexný sprievodca integráciou event-driven architektúry v HDM Boot aplikácii.

## 🎯 Event-Driven Architecture Overview

HDM Boot používa **Event-Driven Architecture** pre:

- **Loose Coupling** - Moduly komunikujú cez eventy
- **Scalability** - Asynchrónne spracovanie
- **Auditability** - Sledovanie všetkých zmien
- **Extensibility** - Jednoduché pridávanie funkcionalít

## 🏗️ Event System Architecture

```
Event Flow:
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│   Domain    │───▶│ Event Bus    │───▶│  Listeners  │
│   Service   │    │ (Dispatcher) │    │ (Handlers)  │
└─────────────┘    └──────────────┘    └─────────────┘
                           │                    │
                           ▼                    ▼
                   ┌──────────────┐    ┌─────────────┐
                   │ Event Store  │    │ Side Effects│
                   │ (Audit Log)  │    │ (Email, etc)│
                   └──────────────┘    └─────────────┘
```

## 📝 Event Definition

### Base Event Interface

```php
<?php
// src/SharedKernel/Event/EventInterface.php

namespace HdmBoot\SharedKernel\Event;

interface EventInterface
{
    public function getEventName(): string;
    public function getOccurredAt(): \DateTimeImmutable;
    public function getPayload(): array;
    public function getAggregateId(): ?string;
    public function getVersion(): int;
}
```

### Abstract Base Event

```php
<?php
// src/SharedKernel/Event/AbstractEvent.php

namespace HdmBoot\SharedKernel\Event;

abstract class AbstractEvent implements EventInterface
{
    private readonly \DateTimeImmutable $occurredAt;
    private readonly string $eventId;

    public function __construct(
        private readonly ?string $aggregateId = null,
        private readonly int $version = 1
    ) {
        $this->occurredAt = new \DateTimeImmutable();
        $this->eventId = $this->generateEventId();
    }

    public function getEventName(): string
    {
        return static::class;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateId(): ?string
    {
        return $this->aggregateId;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    abstract public function getPayload(): array;

    private function generateEventId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
```

### Domain Event Examples

```php
<?php
// src/Modules/Core/User/Domain/Event/UserCreatedEvent.php

namespace HdmBoot\Modules\Core\User\Domain\Event;

use HdmBoot\SharedKernel\Event\AbstractEvent;
use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class UserCreatedEvent extends AbstractEvent
{
    public function __construct(
        private readonly User $user
    ) {
        parent::__construct(
            aggregateId: $user->getId()->toString(),
            version: 1
        );
    }

    public function getPayload(): array
    {
        return [
            'user_id' => $this->user->getId()->toString(),
            'email' => $this->user->getEmail(),
            'name' => $this->user->getName(),
            'role' => $this->user->getRole(),
            'created_at' => $this->user->getCreatedAt()->format('c'),
        ];
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
```

```php
<?php
// src/Modules/Core/Security/Domain/Event/LoginAttemptEvent.php

namespace HdmBoot\Modules\Core\Security\Domain\Event;

use HdmBoot\SharedKernel\Event\AbstractEvent;

final class LoginAttemptEvent extends AbstractEvent
{
    public function __construct(
        private readonly string $email,
        private readonly bool $successful,
        private readonly string $ipAddress,
        private readonly string $userAgent
    ) {
        parent::__construct();
    }

    public function getPayload(): array
    {
        return [
            'email' => $this->email,
            'successful' => $this->successful,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'timestamp' => $this->getOccurredAt()->format('c'),
        ];
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
```

## 🚀 Event Dispatcher

### Event Dispatcher Interface

```php
<?php
// src/SharedKernel/Event/EventDispatcherInterface.php

namespace HdmBoot\SharedKernel\Event;

interface EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void;
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;
    public function removeListener(string $eventName, callable $listener): void;
    public function hasListeners(string $eventName): bool;
    public function getListeners(string $eventName): array;
}
```

### Synchronous Event Dispatcher

```php
<?php
// src/SharedKernel/Event/SynchronousEventDispatcher.php

namespace HdmBoot\SharedKernel\Event;

use Psr\Log\LoggerInterface;

final class SynchronousEventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?EventStoreInterface $eventStore = null
    ) {}

    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getEventName();
        
        $this->logger->debug('Dispatching event', [
            'event' => $eventName,
            'event_id' => $event->getEventId(),
            'aggregate_id' => $event->getAggregateId(),
        ]);

        // Store event if event store is available
        if ($this->eventStore) {
            $this->eventStore->store($event);
        }

        // Get listeners sorted by priority
        $listeners = $this->getListeners($eventName);
        
        foreach ($listeners as $listener) {
            try {
                $listener($event);
            } catch (\Throwable $e) {
                $this->logger->error('Event listener failed', [
                    'event' => $eventName,
                    'listener' => $this->getListenerName($listener),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue with other listeners
            }
        }
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            $key = array_search($listener, $listeners, true);
            if ($key !== false) {
                unset($this->listeners[$eventName][$priority][$key]);
                if (empty($this->listeners[$eventName][$priority])) {
                    unset($this->listeners[$eventName][$priority]);
                }
                if (empty($this->listeners[$eventName])) {
                    unset($this->listeners[$eventName]);
                }
                break;
            }
        }
    }

    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    public function getListeners(string $eventName): array
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        // Sort by priority (higher priority first)
        $listeners = $this->listeners[$eventName];
        krsort($listeners);

        // Flatten array
        $result = [];
        foreach ($listeners as $priorityListeners) {
            $result = array_merge($result, $priorityListeners);
        }

        return $result;
    }

    private function getListenerName(callable $listener): string
    {
        if (is_array($listener)) {
            return get_class($listener[0]) . '::' . $listener[1];
        }
        
        if (is_object($listener)) {
            return get_class($listener);
        }
        
        return (string) $listener;
    }
}
```

## 🎧 Event Listeners

### Event Listener Interface

```php
<?php
// src/SharedKernel/Event/EventListenerInterface.php

namespace HdmBoot\SharedKernel\Event;

interface EventListenerInterface
{
    public function handle(EventInterface $event): void;
    public function supports(EventInterface $event): bool;
}
```

### Example Event Listeners

```php
<?php
// src/Modules/Core/User/Application/EventListener/UserCreatedListener.php

namespace HdmBoot\Modules\Core\User\Application\EventListener;

use HdmBoot\SharedKernel\Event\{EventInterface, EventListenerInterface};
use HdmBoot\Modules\Core\User\Domain\Event\UserCreatedEvent;
use HdmBoot\Modules\Core\Notification\Application\Service\NotificationService;
use Psr\Log\LoggerInterface;

final class UserCreatedListener implements EventListenerInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger
    ) {}

    public function handle(EventInterface $event): void
    {
        if (!$this->supports($event)) {
            return;
        }

        /** @var UserCreatedEvent $event */
        $user = $event->getUser();

        $this->logger->info('Processing user created event', [
            'user_id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
        ]);

        // Send welcome email
        $this->notificationService->sendWelcomeEmail($user);

        // Create user profile
        $this->createUserProfile($user);

        // Log audit event
        $this->logAuditEvent($event);
    }

    public function supports(EventInterface $event): bool
    {
        return $event instanceof UserCreatedEvent;
    }

    private function createUserProfile(User $user): void
    {
        // Implementation for creating user profile
    }

    private function logAuditEvent(UserCreatedEvent $event): void
    {
        $this->logger->info('User created', [
            'event_id' => $event->getEventId(),
            'user_id' => $event->getUser()->getId()->toString(),
            'timestamp' => $event->getOccurredAt()->format('c'),
        ]);
    }
}
```

```php
<?php
// src/Modules/Core/Security/Application/EventListener/SecurityEventListener.php

namespace HdmBoot\Modules\Core\Security\Application\EventListener;

use HdmBoot\SharedKernel\Event\{EventInterface, EventListenerInterface};
use HdmBoot\Modules\Core\Security\Domain\Event\{LoginAttemptEvent, LoginFailedEvent};
use HdmBoot\Modules\Core\Security\Application\Service\SecurityAuditService;

final class SecurityEventListener implements EventListenerInterface
{
    public function __construct(
        private readonly SecurityAuditService $auditService
    ) {}

    public function handle(EventInterface $event): void
    {
        match (true) {
            $event instanceof LoginAttemptEvent => $this->handleLoginAttempt($event),
            $event instanceof LoginFailedEvent => $this->handleLoginFailed($event),
            default => null
        };
    }

    public function supports(EventInterface $event): bool
    {
        return $event instanceof LoginAttemptEvent || 
               $event instanceof LoginFailedEvent;
    }

    private function handleLoginAttempt(LoginAttemptEvent $event): void
    {
        $this->auditService->logLoginAttempt(
            email: $event->getEmail(),
            successful: $event->isSuccessful(),
            ipAddress: $event->getPayload()['ip_address'],
            userAgent: $event->getPayload()['user_agent']
        );
    }

    private function handleLoginFailed(LoginFailedEvent $event): void
    {
        $this->auditService->handleFailedLogin($event->getEmail());
        
        // Implement rate limiting, alerting, etc.
    }
}
```

## 🔧 Event Registration

### Service Container Registration

```php
<?php
// config/container.php

use HdmBoot\SharedKernel\Event\{EventDispatcherInterface, SynchronousEventDispatcher};
use HdmBoot\Modules\Core\User\Application\EventListener\UserCreatedListener;

return [
    // Event Dispatcher
    EventDispatcherInterface::class => function (ContainerInterface $container) {
        return new SynchronousEventDispatcher(
            logger: $container->get(LoggerInterface::class),
            eventStore: $container->get(EventStoreInterface::class)
        );
    },

    // Event Listeners
    UserCreatedListener::class => function (ContainerInterface $container) {
        return new UserCreatedListener(
            notificationService: $container->get(NotificationService::class),
            logger: $container->get(LoggerInterface::class)
        );
    },
];
```

### Event Listener Registration

```php
<?php
// src/SharedKernel/Event/EventListenerRegistry.php

namespace HdmBoot\SharedKernel\Event;

final class EventListenerRegistry
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function registerListeners(array $listeners): void
    {
        foreach ($listeners as $eventName => $eventListeners) {
            foreach ($eventListeners as $listenerConfig) {
                $listener = $listenerConfig['listener'];
                $priority = $listenerConfig['priority'] ?? 0;
                
                $this->eventDispatcher->addListener($eventName, $listener, $priority);
            }
        }
    }
}
```

### Module Event Configuration

```php
<?php
// src/Modules/Core/User/config.php

return [
    'name' => 'User',
    'version' => '1.0.0',
    
    // Event listeners
    'listeners' => [
        \HdmBoot\Modules\Core\User\Domain\Event\UserCreatedEvent::class => [
            [
                'listener' => \HdmBoot\Modules\Core\User\Application\EventListener\UserCreatedListener::class,
                'priority' => 100
            ],
            [
                'listener' => \HdmBoot\Modules\Core\Audit\Application\EventListener\AuditListener::class,
                'priority' => 50
            ],
        ],
    ],
];
```

## 🧪 Testing Events

### Event Testing

```php
<?php
// tests/Unit/Event/UserCreatedEventTest.php

namespace Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use HdmBoot\Modules\Core\User\Domain\Event\UserCreatedEvent;
use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class UserCreatedEventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $user = User::create('test@example.com', 'password', 'Test User');
        $event = new UserCreatedEvent($user);

        $this->assertEquals(UserCreatedEvent::class, $event->getEventName());
        $this->assertEquals($user->getId()->toString(), $event->getAggregateId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
        
        $payload = $event->getPayload();
        $this->assertEquals($user->getId()->toString(), $payload['user_id']);
        $this->assertEquals('test@example.com', $payload['email']);
    }
}
```

### Event Dispatcher Testing

```php
<?php
// tests/Unit/Event/EventDispatcherTest.php

namespace Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use HdmBoot\SharedKernel\Event\SynchronousEventDispatcher;

final class EventDispatcherTest extends TestCase
{
    private SynchronousEventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = new SynchronousEventDispatcher($logger);
    }

    public function testEventDispatch(): void
    {
        $called = false;
        $listener = function (EventInterface $event) use (&$called) {
            $called = true;
        };

        $this->dispatcher->addListener(TestEvent::class, $listener);
        $this->dispatcher->dispatch(new TestEvent());

        $this->assertTrue($called);
    }

    public function testListenerPriority(): void
    {
        $order = [];
        
        $listener1 = function () use (&$order) { $order[] = 1; };
        $listener2 = function () use (&$order) { $order[] = 2; };

        $this->dispatcher->addListener(TestEvent::class, $listener1, 10);
        $this->dispatcher->addListener(TestEvent::class, $listener2, 20);
        
        $this->dispatcher->dispatch(new TestEvent());

        $this->assertEquals([2, 1], $order); // Higher priority first
    }
}
```

## 📋 Event Integration Checklist

### Setup:
- [ ] Event dispatcher nakonfigurovaný v DI container
- [ ] Event listeners registrované
- [ ] Event store implementovaný (voliteľné)
- [ ] Logging nakonfigurované

### Implementation:
- [ ] Domain events definované
- [ ] Event listeners implementované
- [ ] Error handling v listeneroch
- [ ] Event versioning strategy

### Testing:
- [ ] Unit testy pre eventy
- [ ] Unit testy pre listeners
- [ ] Integration testy pre event flow
- [ ] Performance testy pre high-volume events

### Monitoring:
- [ ] Event dispatching logged
- [ ] Listener failures monitored
- [ ] Event store performance tracked
- [ ] Dead letter queue pre failed events

## 🔗 Ďalšie zdroje

- [Event-Driven Architecture](../architecture/event-driven-architecture.md)
- [Domain-Driven Design](../architecture/domain-driven-design.md)
- [Module Development](module-development.md)
- [Testing Guide](testing-guide.md)
