# Module Isolation & Event-Driven Architecture

## Overview

This document describes the Module Isolation implementation in the MVA Bootstrap project. The architecture ensures proper separation of concerns between modules through contract-based communication and event-driven architecture, preventing tight coupling and enabling independent module development.

## Architecture Principles

### 1. Contract-Based Communication
- Modules communicate only through well-defined interfaces
- Implementation details are hidden from other modules
- Public APIs are explicitly declared in Contract directories

### 2. Event-Driven Architecture
- Modules publish events for significant domain changes
- Other modules subscribe to relevant events
- Loose coupling through asynchronous event handling

### 3. Dependency Inversion
- Modules depend on abstractions (interfaces), not concrete implementations
- Dependency direction follows module hierarchy
- No circular dependencies between modules

## Module Structure

### Directory Organization

```
modules/Core/
├── User/
│   ├── Contracts/                    ← Public API
│   │   ├── Services/
│   │   │   └── UserServiceInterface.php
│   │   ├── Events/
│   │   │   └── UserModuleEvents.php
│   │   └── DTOs/
│   │       └── UserDataDTO.php
│   ├── Domain/                       ← Internal domain logic
│   ├── Application/                  ← Internal application logic
│   ├── Services/                     ← Internal services
│   └── UserModule.php               ← Module definition
├── Security/
│   ├── Contracts/                    ← Public API
│   │   ├── Services/
│   │   │   ├── AuthenticationServiceInterface.php
│   │   │   └── AuthorizationServiceInterface.php
│   │   └── Events/
│   │       └── SecurityModuleEvents.php
│   ├── Domain/                       ← Internal domain logic
│   ├── Application/                  ← Internal application logic
│   ├── Services/                     ← Internal services
│   └── SecurityModule.php           ← Module definition
└── Language/
    ├── Contracts/                    ← Public API
    │   ├── Services/
    │   │   └── LocaleServiceInterface.php
    │   └── Events/
    │       └── LanguageModuleEvents.php
    └── Services/                     ← Internal services
```

### Module Contracts

Each module exposes its public API through contracts:

```php
// modules/Core/User/Contracts/Services/UserServiceInterface.php
interface UserServiceInterface
{
    public function authenticate(string $email, string $password): ?array;
    public function getUserById(string $id): ?array;
    public function getUserByEmail(string $email): ?array;
    public function hasPermission(array $user, string $permission): bool;
    public function emailExists(string $email): bool;
    public function getUserStatistics(): array;
}
```

### Module Events

Modules define events they publish for other modules to subscribe to:

```php
// modules/Core/User/Contracts/Events/UserModuleEvents.php
final class UserModuleEvents
{
    public const USER_REGISTERED = 'user.registered';
    public const USER_UPDATED = 'user.updated';
    public const USER_DELETED = 'user.deleted';
    public const USER_PASSWORD_CHANGED = 'user.password_changed';
    public const USER_STATUS_CHANGED = 'user.status_changed';
    public const USER_ROLE_CHANGED = 'user.role_changed';
    public const USER_LOGGED_IN = 'user.logged_in';
    public const USER_LOGGED_OUT = 'user.logged_out';
}
```

## Module Implementation

### Module Definition

Each module implements the `ModuleInterface`:

```php
// modules/Core/User/UserModule.php
final class UserModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'User';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return []; // User module has no dependencies
    }

    public function getPublicServices(): array
    {
        return [
            UserServiceInterface::class => UserService::class,
        ];
    }

    public function getPublishedEvents(): array
    {
        return UserModuleEvents::getAllEvents();
    }

    public function getEventSubscriptions(): array
    {
        return [
            // Subscribe to events from other modules
            'security.login_successful' => [$this, 'handleLoginSuccess'],
        ];
    }

    public function initialize(): void
    {
        // Module initialization logic
        $this->setupEventListeners();
        $this->validateConfiguration();
    }
}
```

### Service Implementation

Services implement their corresponding interfaces:

```php
// modules/Core/User/Services/UserService.php
final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function authenticate(string $email, string $password): ?array
    {
        // Implementation details hidden from other modules
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    // ... other interface methods
}
```

## Event-Driven Communication

### Event Publishing

Modules publish events when significant domain changes occur:

```php
// In a command handler or service
final class RegisterUserHandler
{
    public function handle(RegisterUserCommand $command): void
    {
        // Business logic
        $savedUser = $this->userRepository->save($userData);

        // Publish domain event
        $event = UserWasRegistered::fromUserData($savedUser);
        $this->eventDispatcher->dispatch($event);
    }
}
```

### Event Subscription

Other modules subscribe to relevant events:

```php
// modules/Core/Security/SecurityModule.php
final class SecurityModule implements ModuleInterface
{
    public function getEventSubscriptions(): array
    {
        return [
            'user.registered' => [$this, 'handleUserRegistered'],
            'user.status_changed' => [$this, 'handleUserStatusChanged'],
        ];
    }

    public function handleUserRegistered(object $event): void
    {
        // React to user registration
        // Setup default security settings, send welcome email, etc.
        $this->logger->info('Setting up security for new user', [
            'event' => get_class($event),
        ]);
    }
}
```

## Module Registry & Lifecycle

### Module Registry

The `ModuleRegistry` manages module lifecycle and dependencies:

```php
// src/Shared/Contracts/Modules/ModuleRegistry.php
final class ModuleRegistry
{
    public function registerModule(ModuleInterface $module): void
    {
        $this->modules[$module->getName()] = $module;
    }

    public function initializeModules(): void
    {
        // Calculate initialization order based on dependencies
        $initializationOrder = $this->calculateInitializationOrder();
        
        foreach ($initializationOrder as $moduleName) {
            $this->initializeModule($moduleName);
        }
    }

    private function calculateInitializationOrder(): array
    {
        // Topological sort based on module dependencies
        // Ensures modules are initialized in correct order
    }
}
```

### Module Bootstrap

The `ModuleBootstrap` handles proper module initialization:

```php
// src/Shared/Bootstrap/ModuleBootstrap.php
final class ModuleBootstrap
{
    public function bootstrap(): void
    {
        $moduleRegistry = $this->container->get(ModuleRegistry::class);

        // Register all modules
        $this->registerModules($moduleRegistry);

        // Initialize modules in dependency order
        $moduleRegistry->initializeModules();

        // Setup inter-module event communication
        $this->setupEventCommunication($moduleRegistry);

        // Validate module contracts
        $this->validateModuleContracts($moduleRegistry);
    }
}
```

## Dependency Injection Integration

### Container Configuration

Module contracts are bound to implementations in the container:

```php
// config/services/interfaces.php
return [
    // Module Contracts
    UserServiceInterface::class => function (Container $container): UserServiceInterface {
        return $container->get(UserService::class);
    },

    AuthenticationServiceInterface::class => function (Container $container): AuthenticationServiceInterface {
        return $container->get(AuthenticationService::class);
    },

    LocaleServiceInterface::class => function (Container $container): LocaleServiceInterface {
        return $container->get(LocaleService::class);
    },

    // Module Infrastructure
    ModuleRegistry::class => \DI\autowire(),
    ModuleEventBus::class => \DI\autowire(),
    
    // Module Instances
    UserModule::class => \DI\autowire(),
    SecurityModule::class => \DI\autowire(),
];
```

### Cross-Module Dependencies

Modules depend on interfaces, not concrete implementations:

```php
// Security module depends on User module through interface
final class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly UserServiceInterface $userService,  // Interface dependency
        private readonly LoggerInterface $logger
    ) {
    }

    public function authenticateForWeb(string $email, string $password, string $clientIp): ?array
    {
        // Use User module through interface
        $user = $this->userService->authenticate($email, $password);
        
        if ($user) {
            // Publish security event
            $this->eventDispatcher->dispatch(new LoginSuccessfulEvent($user, $clientIp));
        }

        return $user;
    }
}
```

## Benefits

### 1. Loose Coupling

**Before Module Isolation:**
```php
// ❌ Tight coupling - direct dependencies
class SecurityService
{
    public function __construct(
        private UserService $userService  // Direct dependency on concrete class
    ) {}
}
```

**After Module Isolation:**
```php
// ✅ Loose coupling - interface dependencies
class SecurityService
{
    public function __construct(
        private UserServiceInterface $userService  // Dependency on interface
    ) {}
}
```

### 2. Independent Development

- **Team Isolation**: Different teams can work on different modules
- **Parallel Development**: Modules can be developed independently
- **Clear Boundaries**: Well-defined interfaces prevent conflicts

### 3. Testability

```php
// Easy to mock interfaces for testing
class SecurityServiceTest extends TestCase
{
    public function testAuthentication(): void
    {
        // Mock the User module interface
        $mockUserService = $this->createMock(UserServiceInterface::class);
        $mockUserService->expects($this->once())
            ->method('authenticate')
            ->willReturn(['id' => '123', 'email' => 'test@example.com']);

        $securityService = new SecurityService($mockUserService, $mockLogger);
        
        $result = $securityService->authenticateForWeb('test@example.com', 'password', '127.0.0.1');
        
        $this->assertNotNull($result);
    }
}
```

### 4. Flexibility & Extensibility

- **Easy to Replace**: Swap module implementations without affecting others
- **Add New Modules**: Extend functionality without modifying existing modules
- **Version Management**: Each module can evolve independently

### 5. Event-Driven Benefits

- **Asynchronous Processing**: Events can be processed asynchronously
- **Multiple Subscribers**: Multiple modules can react to the same event
- **Audit Trail**: Events provide natural audit logging

## Module Communication Patterns

### 1. Synchronous Communication (Interface Calls)

Use for immediate data needs:

```php
// Security module needs user data immediately
final class AuthorizationService
{
    public function hasPermission(array $user, string $permission): bool
    {
        // Synchronous call to User module
        return $this->userService->hasPermission($user, $permission);
    }
}
```

### 2. Asynchronous Communication (Events)

Use for side effects and notifications:

```php
// User registration triggers multiple side effects
final class RegisterUserHandler
{
    public function handle(RegisterUserCommand $command): void
    {
        $user = $this->userRepository->save($userData);

        // Publish event - other modules can react asynchronously
        $this->eventDispatcher->dispatch(new UserWasRegistered($user));
    }
}

// Security module reacts to user registration
final class SecurityModule
{
    public function handleUserRegistered(UserWasRegistered $event): void
    {
        // Setup default security settings
        // Send welcome email
        // Create audit log entry
    }
}
```

## Best Practices

### 1. Interface Design

**Keep interfaces focused and cohesive:**

```php
// ✅ Good - Focused interface
interface UserServiceInterface
{
    public function authenticate(string $email, string $password): ?array;
    public function getUserById(string $id): ?array;
    public function hasPermission(array $user, string $permission): bool;
}

// ❌ Bad - Too many responsibilities
interface UserServiceInterface
{
    public function authenticate(string $email, string $password): ?array;
    public function sendEmail(string $to, string $subject, string $body): void;  // Email responsibility
    public function logActivity(string $activity): void;  // Logging responsibility
}
```

**Use DTOs for complex data structures:**

```php
// ✅ Good - Standardized DTO
final readonly class UserDataDTO
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public string $status
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            email: $data['email'],
            name: $data['name'],
            role: $data['role'],
            status: $data['status']
        );
    }
}
```

### 2. Event Design

**Make events immutable and self-contained:**

```php
// ✅ Good - Immutable event with all necessary data
final readonly class UserWasRegistered implements DomainEventInterface
{
    public function __construct(
        public string $eventId,
        public string $userId,
        public string $email,
        public string $name,
        public string $role,
        public \DateTimeImmutable $occurredAt
    ) {}
}
```

**Use descriptive event names:**

```php
// ✅ Good - Clear, descriptive names
public const USER_REGISTERED = 'user.registered';
public const USER_PASSWORD_CHANGED = 'user.password_changed';
public const USER_STATUS_CHANGED = 'user.status_changed';

// ❌ Bad - Vague names
public const USER_EVENT = 'user.event';
public const SOMETHING_HAPPENED = 'something.happened';
```

### 3. Module Dependencies

**Keep dependency graph simple:**

```
User Module (no dependencies)
    ↑
Security Module (depends on User)
    ↑
Admin Module (depends on Security)
```

**Avoid circular dependencies:**

```php
// ❌ Bad - Circular dependency
class UserModule
{
    public function getDependencies(): array
    {
        return ['Security'];  // User depends on Security
    }
}

class SecurityModule
{
    public function getDependencies(): array
    {
        return ['User'];  // Security depends on User - CIRCULAR!
    }
}
```

### 4. Error Handling

**Handle interface failures gracefully:**

```php
final class SecurityService
{
    public function authenticateUser(string $email, string $password): ?array
    {
        try {
            return $this->userService->authenticate($email, $password);
        } catch (\Exception $e) {
            $this->logger->error('User service authentication failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            // Return null instead of propagating exception
            return null;
        }
    }
}
```

## Testing Strategies

### 1. Unit Testing with Mocks

```php
class AuthenticationServiceTest extends TestCase
{
    public function testSuccessfulAuthentication(): void
    {
        // Arrange
        $mockUserService = $this->createMock(UserServiceInterface::class);
        $mockUserService->method('authenticate')
            ->willReturn(['id' => '123', 'email' => 'test@example.com']);

        $authService = new AuthenticationService($mockUserService, $mockLogger);

        // Act
        $result = $authService->authenticateForWeb('test@example.com', 'password', '127.0.0.1');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals('123', $result['id']);
    }
}
```

### 2. Integration Testing

```php
class ModuleIntegrationTest extends TestCase
{
    public function testUserSecurityIntegration(): void
    {
        // Test real integration between User and Security modules
        $container = $this->createContainer();

        $userService = $container->get(UserServiceInterface::class);
        $authService = $container->get(AuthenticationServiceInterface::class);

        // Create user through User module
        $user = $userService->createUser('test@example.com', 'Test User', 'password123');

        // Authenticate through Security module
        $authenticatedUser = $authService->authenticateForWeb('test@example.com', 'password123', '127.0.0.1');

        $this->assertNotNull($authenticatedUser);
        $this->assertEquals($user['id'], $authenticatedUser['id']);
    }
}
```

### 3. Event Testing

```php
class EventCommunicationTest extends TestCase
{
    public function testUserRegistrationEvent(): void
    {
        $eventDispatcher = new TestEventDispatcher();
        $userModule = new UserModule($this->createLogger());

        // Setup event listener
        $eventDispatcher->addListener('user.registered', [$userModule, 'handleUserRegistered']);

        // Trigger event
        $event = new UserWasRegistered('123', 'test@example.com', 'Test User');
        $eventDispatcher->dispatch($event);

        // Assert event was handled
        $this->assertTrue($eventDispatcher->wasEventDispatched('user.registered'));
    }
}
```

## Migration Guide

### Step 1: Identify Module Boundaries

1. **Analyze current dependencies** between services
2. **Group related functionality** into logical modules
3. **Identify cross-cutting concerns** (logging, validation, etc.)

### Step 2: Create Module Contracts

1. **Extract public interfaces** from existing services
2. **Define event contracts** for inter-module communication
3. **Create DTOs** for complex data structures

### Step 3: Implement Module Classes

1. **Create ModuleInterface implementations** for each module
2. **Define dependencies** and public services
3. **Specify published events** and subscriptions

### Step 4: Update Container Configuration

1. **Bind interfaces to implementations**
2. **Register module instances**
3. **Setup event dispatcher** configuration

### Step 5: Refactor Existing Code

1. **Replace direct dependencies** with interface dependencies
2. **Convert direct calls** to event publishing where appropriate
3. **Update tests** to use interface mocks

### Step 6: Validate Module Isolation

1. **Run module contract validation**
2. **Test cross-module communication**
3. **Verify no circular dependencies**

## Troubleshooting

### Common Issues

1. **Circular Dependencies**
   - **Symptom**: Module initialization fails
   - **Solution**: Redesign module boundaries or use events instead of direct calls

2. **Interface Mismatches**
   - **Symptom**: Type errors when calling interface methods
   - **Solution**: Ensure implementations match interface signatures exactly

3. **Event Not Received**
   - **Symptom**: Event listeners not triggered
   - **Solution**: Verify event names match exactly and listeners are registered

4. **Missing Dependencies**
   - **Symptom**: Container cannot resolve module services
   - **Solution**: Check container bindings and module registration

### Debugging Tools

```php
// Get module statistics
$moduleBootstrap = $container->get(ModuleBootstrap::class);
$stats = $moduleBootstrap->getModuleStatistics();

// Validate module contracts
$moduleEventBus = $container->get(ModuleEventBus::class);
$issues = $moduleEventBus->validateModuleEventContracts();

// Check cross-module event mappings
$mappings = $moduleEventBus->getCrossModuleEventMappings();
```

## Conclusion

Module Isolation provides:

- **Clear Boundaries**: Well-defined interfaces between modules
- **Loose Coupling**: Modules communicate through contracts, not implementations
- **Event-Driven Architecture**: Asynchronous communication for side effects
- **Independent Development**: Teams can work on modules independently
- **Better Testability**: Easy to mock interfaces and test in isolation
- **Flexibility**: Easy to replace or extend modules without affecting others

This architecture enables scalable, maintainable, and testable application development while following industry best practices for modular design and inter-module communication.
