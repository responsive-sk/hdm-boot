# Event-Driven Architecture Examples

## Overview

This document provides practical examples of implementing Event-Driven Architecture in the MVA Bootstrap project. Each example demonstrates real-world scenarios with complete code implementations.

## Language Module Events

### LocaleChangedEvent Example

#### Event Definition
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\Events;

use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Shared\Events\DomainEvent;

final readonly class LocaleChangedEvent implements DomainEvent
{
    public function __construct(
        public ?string $userId,
        public Locale $previousLocale,
        public Locale $newLocale,
        public \DateTimeImmutable $occurredAt
    ) {
    }

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

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

#### Event Listener Implementation
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Infrastructure\Listeners;

use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\Shared\Events\DomainEvent;
use MvaBootstrap\Shared\Events\EventListener;
use Psr\Log\LoggerInterface;

final class LocaleChangedListener implements EventListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof LocaleChangedEvent) {
            return;
        }

        $this->logger->info('Processing locale change', [
            'user_id' => $event->userId,
            'previous_locale' => $event->previousLocale->toString(),
            'new_locale' => $event->newLocale->toString(),
        ]);

        // Update user session
        $this->updateUserSession($event);

        // Clear locale-specific caches
        $this->clearLocaleCaches($event);

        // Log analytics
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

    private function updateUserSession(LocaleChangedEvent $event): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $event->newLocale->toString();
            $_SESSION['locale_changed_at'] = $event->occurredAt->format('Y-m-d H:i:s');
        }
    }

    private function clearLocaleCaches(LocaleChangedEvent $event): void
    {
        $cacheKeys = [
            "translations.{$event->previousLocale->toString()}",
            "translations.{$event->newLocale->toString()}",
            "user.{$event->userId}.locale",
        ];

        foreach ($cacheKeys as $key) {
            // Clear cache implementation
            $this->logger->debug('Cache cleared', ['cache_key' => $key]);
        }
    }

    private function logAnalytics(LocaleChangedEvent $event): void
    {
        $this->logger->info('Locale change analytics', [
            'event_type' => 'locale_changed',
            'user_id' => $event->userId,
            'previous_locale' => $event->previousLocale->toString(),
            'new_locale' => $event->newLocale->toString(),
            'language_code' => $event->newLocale->getLanguageCode(),
            'country_code' => $event->newLocale->getCountryCode(),
        ]);
    }
}
```

#### Publishing the Event
```php
<?php

namespace MvaBootstrap\Modules\Core\Language\Domain\Services;

use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Shared\Events\ModuleEventBus;

class LocaleService
{
    public function __construct(
        private readonly ModuleEventBus $moduleEventBus
    ) {
    }

    public function changeLocale(string $userId, Locale $newLocale): void
    {
        // Get current locale
        $previousLocale = $this->getCurrentLocale($userId);

        // Perform business logic
        $this->updateUserLocale($userId, $newLocale);

        // Publish domain event
        $event = LocaleChangedEvent::create($userId, $previousLocale, $newLocale);
        $this->moduleEventBus->publish('Language', $event);
    }

    private function getCurrentLocale(string $userId): Locale
    {
        // Implementation to get current user locale
        return Locale::fromString($_SESSION['locale'] ?? 'en_US');
    }

    private function updateUserLocale(string $userId, Locale $locale): void
    {
        // Implementation to update user locale in database
        $_SESSION['locale'] = $locale->toString();
    }
}
```

## User Module Events

### UserCreatedEvent Example

#### Event Definition
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\User\Domain\Events;

use MvaBootstrap\Shared\Events\DomainEvent;

final readonly class UserCreatedEvent implements DomainEvent
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $name,
        public string $role,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function create(
        string $userId,
        string $email,
        string $name,
        string $role
    ): self {
        return new self(
            $userId,
            $email,
            $name,
            $role,
            new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'user.created';
    }

    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

#### Cross-Module Event Listener
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Infrastructure\Listeners;

use MvaBootstrap\Modules\Core\User\Domain\Events\UserCreatedEvent;
use MvaBootstrap\Shared\Events\DomainEvent;
use MvaBootstrap\Shared\Events\EventListener;
use Psr\Log\LoggerInterface;

final class UserCreatedSecurityListener implements EventListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof UserCreatedEvent) {
            return;
        }

        $this->logger->info('Setting up security for new user', [
            'user_id' => $event->userId,
            'email' => $event->email,
        ]);

        // Create security profile
        $this->createSecurityProfile($event);

        // Set up default permissions
        $this->setupDefaultPermissions($event);

        // Send welcome email
        $this->sendWelcomeEmail($event);
    }

    public function getSupportedEvents(): array
    {
        return ['user.created'];
    }

    public function getPriority(): int
    {
        return 90; // High priority for security setup
    }

    private function createSecurityProfile(UserCreatedEvent $event): void
    {
        // Implementation for creating security profile
        $this->logger->debug('Security profile created', [
            'user_id' => $event->userId,
        ]);
    }

    private function setupDefaultPermissions(UserCreatedEvent $event): void
    {
        // Implementation for setting up default permissions
        $this->logger->debug('Default permissions set', [
            'user_id' => $event->userId,
            'role' => $event->role,
        ]);
    }

    private function sendWelcomeEmail(UserCreatedEvent $event): void
    {
        // Implementation for sending welcome email
        $this->logger->debug('Welcome email sent', [
            'user_id' => $event->userId,
            'email' => $event->email,
        ]);
    }
}
```

## Security Module Events

### LoginAttemptEvent Example

#### Event Definition
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Domain\Events;

use MvaBootstrap\Shared\Events\DomainEvent;

final readonly class LoginAttemptEvent implements DomainEvent
{
    public function __construct(
        public string $email,
        public string $ipAddress,
        public string $userAgent,
        public bool $successful,
        public ?string $userId,
        public ?string $failureReason,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    public static function successful(
        string $email,
        string $userId,
        string $ipAddress,
        string $userAgent
    ): self {
        return new self(
            $email,
            $ipAddress,
            $userAgent,
            true,
            $userId,
            null,
            new \DateTimeImmutable()
        );
    }

    public static function failed(
        string $email,
        string $ipAddress,
        string $userAgent,
        string $failureReason
    ): self {
        return new self(
            $email,
            $ipAddress,
            $userAgent,
            false,
            null,
            $failureReason,
            new \DateTimeImmutable()
        );
    }

    public function getEventName(): string
    {
        return 'security.login_attempt';
    }

    public function getEventData(): array
    {
        return [
            'email' => $this->email,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'successful' => $this->successful,
            'user_id' => $this->userId,
            'failure_reason' => $this->failureReason,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
```

## Event Registration Examples

### EventBootstrap Registration
```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Events;

final class EventBootstrap
{
    private function registerLanguageModuleEvents(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // Register LocaleChangedListener
        $localeChangedListener = $this->container->get(
            \MvaBootstrap\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener::class
        );

        $eventDispatcher->addListener(
            'language.locale_changed',
            [$localeChangedListener, 'handle']
        );

        // Subscribe Language module to its events
        $moduleEventBus->subscribe(
            'Language',
            ['language.locale_changed', 'language.translation_added'],
            [$localeChangedListener, 'handle']
        );
    }

    private function registerUserModuleEvents(
        EventDispatcherInterface $eventDispatcher,
        ModuleEventBus $moduleEventBus
    ): void {
        // Register cross-module listeners
        $securityListener = $this->container->get(
            \MvaBootstrap\Modules\Core\Security\Infrastructure\Listeners\UserCreatedSecurityListener::class
        );

        // Security module listens to User events
        $moduleEventBus->subscribe(
            'Security',
            ['user.created'],
            [$securityListener, 'handle']
        );
    }
}
```

## Testing Examples

### Event Testing
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Language\Events;

use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use PHPUnit\Framework\TestCase;

class LocaleChangedEventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $userId = 'user123';
        $previousLocale = Locale::fromString('en_US');
        $newLocale = Locale::fromString('sk_SK');

        $event = LocaleChangedEvent::create($userId, $previousLocale, $newLocale);

        $this->assertEquals('language.locale_changed', $event->getEventName());
        $this->assertEquals($userId, $event->userId);
        $this->assertEquals('en_US', $event->previousLocale->toString());
        $this->assertEquals('sk_SK', $event->newLocale->toString());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt);
    }

    public function testEventData(): void
    {
        $event = LocaleChangedEvent::create(
            'user123',
            Locale::fromString('en_US'),
            Locale::fromString('sk_SK')
        );

        $data = $event->getEventData();

        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('previous_locale', $data);
        $this->assertArrayHasKey('new_locale', $data);
        $this->assertArrayHasKey('occurred_at', $data);
        $this->assertEquals('user123', $data['user_id']);
        $this->assertEquals('en_US', $data['previous_locale']);
        $this->assertEquals('sk_SK', $data['new_locale']);
    }
}
```

### Listener Testing
```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Language\Listeners;

use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LocaleChangedListenerTest extends TestCase
{
    public function testHandleLocaleChangedEvent(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new LocaleChangedListener($logger);

        $event = LocaleChangedEvent::create(
            'user123',
            Locale::fromString('en_US'),
            Locale::fromString('sk_SK')
        );

        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with('Processing locale change');

        $listener->handle($event);
    }

    public function testGetSupportedEvents(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new LocaleChangedListener($logger);

        $supportedEvents = $listener->getSupportedEvents();

        $this->assertContains('language.locale_changed', $supportedEvents);
    }

    public function testGetPriority(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new LocaleChangedListener($logger);

        $this->assertEquals(100, $listener->getPriority());
    }
}
```

## Integration Testing

### Event Flow Integration Test
```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Events;

use MvaBootstrap\Bootstrap\App;
use MvaBootstrap\Modules\Core\Language\Domain\Events\LocaleChangedEvent;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Shared\Events\EventDispatcherInterface;
use MvaBootstrap\Shared\Events\ModuleEventBus;
use PHPUnit\Framework\TestCase;

class EventFlowIntegrationTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        $this->app = new App();
        $this->app->initialize();
    }

    public function testCompleteEventFlow(): void
    {
        $container = $this->app->getContainer();
        $moduleEventBus = $container->get(ModuleEventBus::class);
        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        // Create test event
        $event = LocaleChangedEvent::create(
            'test_user',
            Locale::fromString('en_US'),
            Locale::fromString('sk_SK')
        );

        // Verify listeners are registered
        $this->assertTrue($eventDispatcher->hasListeners('language.locale_changed'));

        // Publish event
        $moduleEventBus->publish('Language', $event);

        // Verify statistics
        $stats = $moduleEventBus->getStatistics();
        $this->assertGreaterThan(0, $stats['total_events']);
        $this->assertGreaterThan(0, $stats['total_subscriptions']);
    }
}
```

This comprehensive documentation provides practical examples for implementing Event-Driven Architecture in the MVA Bootstrap project, covering event creation, listener implementation, registration, and testing strategies.
