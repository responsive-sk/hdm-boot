# Dependency Injection & Inversion of Control (DI/IoC)

## Overview

This document describes the comprehensive Dependency Injection and Inversion of Control implementation in the MVA Bootstrap project. The system follows SOLID principles and provides proper abstraction layers through interface-based bindings.

## Architecture Principles

### 1. Interface-Based Design
- All services depend on interfaces, not concrete implementations
- Repository pattern with interface contracts
- Easy mocking and testing through interface substitution

### 2. Constructor Injection
- All dependencies injected through constructors
- No direct instantiation (`new` operators) in services
- Clear dependency declarations

### 3. Auto-Wiring
- PHP-DI automatically resolves dependencies
- Reduced manual configuration
- Type-safe dependency resolution

## Container Configuration

### Main Container Structure
```php
// config/container.php
$services = array_merge(
    // Interface-based bindings (DI/IoC)
    require __DIR__ . '/services/interfaces.php',
    
    // Core services
    require __DIR__ . '/services/database.php',
    require __DIR__ . '/services/session.php',
    // ... other service files
);
```

### Interface Bindings
```php
// config/services/interfaces.php
return [
    // Repository Interfaces
    UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
        return new SqliteUserRepository($container->get(\PDO::class));
    },

    // Service Auto-Wiring
    UserService::class => \DI\autowire(),
    AuthenticationService::class => \DI\autowire(),
    AuthorizationService::class => \DI\autowire(),
    
    // Middleware Auto-Wiring
    LocaleMiddleware::class => \DI\autowire(),
    UserAuthenticationMiddleware::class => \DI\autowire(),
];
```

## Service Layer Implementation

### Repository Pattern
```php
// Interface definition
interface UserRepositoryInterface
{
    public function findById(string $id): ?array;
    public function findByEmail(string $email): ?array;
    public function save(array $userData): array;
}

// Implementation with DI
final class SqliteUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo  // ✅ Injected dependency
    ) {
        $this->initializeDatabase();
    }
}

// Service using repository
final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,  // ✅ Interface injection
        private readonly LoggerInterface $logger                   // ✅ Interface injection
    ) {
    }
}
```

### Authentication Service
```php
final class AuthenticationService
{
    public function __construct(
        private readonly UserService $userService,              // ✅ Service injection
        private readonly JwtService $jwtService,                // ✅ Service injection
        private readonly SecurityLoginChecker $securityChecker, // ✅ Service injection
        private readonly LoggerInterface $logger                // ✅ Interface injection
    ) {
    }
}
```

## Middleware Layer

### Authorization Middleware
```php
final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,    // ✅ Service injection
        private readonly UserService $userService,                      // ✅ Service injection
        private readonly SessionInterface $session,                     // ✅ Interface injection
        private readonly ResponseFactoryInterface $responseFactory,     // ✅ Interface injection
        private readonly LoggerInterface $logger,                       // ✅ Interface injection
        private readonly array $requiredPermissions = []               // ✅ Configuration injection
    ) {
    }
}
```

### Locale Middleware
```php
final class LocaleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LocaleService $localeService,    // ✅ Service injection
        private readonly SessionInterface $session,       // ✅ Interface injection
        private readonly UserService $userService,        // ✅ Service injection
        private readonly LoggerInterface $logger          // ✅ Interface injection
    ) {
    }
}
```

## Benefits

### 1. SOLID Principles Compliance

#### Single Responsibility Principle (SRP)
- Each service has a single, well-defined purpose
- Clear separation of concerns

#### Open/Closed Principle (OCP)
- Services are open for extension through interfaces
- Closed for modification of existing implementations

#### Liskov Substitution Principle (LSP)
- Interface implementations are interchangeable
- Polymorphic behavior through interface contracts

#### Interface Segregation Principle (ISP)
- Focused, specific interfaces
- No forced dependencies on unused methods

#### Dependency Inversion Principle (DIP)
- High-level modules depend on abstractions (interfaces)
- Low-level modules implement interfaces
- No direct dependencies on concrete classes

### 2. Improved Testability
```php
// Easy mocking for unit tests
$mockRepository = $this->createMock(UserRepositoryInterface::class);
$userService = new UserService($mockRepository, $mockLogger);
```

### 3. Better Maintainability
- Clear dependency declarations in constructors
- Easy to understand service relationships
- Reduced coupling between components

### 4. Enhanced Flexibility
- Easy to swap implementations
- Configuration-driven service resolution
- Support for different environments

## Usage Examples

### Service Resolution
```php
// Container automatically resolves all dependencies
$userService = $container->get(UserService::class);

// Interface binding resolution
$userRepository = $container->get(UserRepositoryInterface::class);
// Returns: SqliteUserRepository instance
```

### Middleware Usage
```php
// Auto-wired middleware in routes
$app->get('/admin', AdminAction::class)
    ->add(AuthorizationMiddleware::class);  // ✅ Auto-resolved dependencies
```

### Testing with Mocks
```php
class UserServiceTest extends TestCase
{
    public function testGetUserById(): void
    {
        // Mock dependencies
        $mockRepository = $this->createMock(UserRepositoryInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // Inject mocks
        $userService = new UserService($mockRepository, $mockLogger);
        
        // Test service behavior
        $mockRepository->expects($this->once())
            ->method('findById')
            ->with('123')
            ->willReturn(['id' => '123', 'name' => 'Test User']);
            
        $result = $userService->getUserById('123');
        $this->assertEquals('Test User', $result['name']);
    }
}
```

## Best Practices

### 1. Always Use Interface Injection
```php
// ✅ Good - Interface injection
public function __construct(UserRepositoryInterface $repository) {}

// ❌ Bad - Concrete class injection
public function __construct(SqliteUserRepository $repository) {}
```

### 2. Avoid Direct Instantiation
```php
// ✅ Good - Constructor injection
public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
}

// ❌ Bad - Direct instantiation
public function someMethod() {
    $logger = new Logger();  // Avoid this
}
```

### 3. Use Auto-Wiring When Possible
```php
// ✅ Good - Auto-wired service
UserService::class => \DI\autowire(),

// ❌ Unnecessary - Manual configuration when auto-wiring works
UserService::class => function (Container $container) {
    return new UserService(
        $container->get(UserRepositoryInterface::class),
        $container->get(LoggerInterface::class)
    );
}
```

### 4. Keep Constructors Simple
```php
// ✅ Good - Simple constructor with clear dependencies
public function __construct(
    private readonly UserRepositoryInterface $userRepository,
    private readonly LoggerInterface $logger
) {
}

// ❌ Bad - Complex constructor logic
public function __construct(UserRepositoryInterface $userRepository) {
    $this->userRepository = $userRepository;
    $this->initializeComplexLogic();  // Avoid this
}
```

## Migration Guide

### From Direct Instantiation to DI

#### Before (Direct Instantiation)
```php
class UserService
{
    private UserRepository $repository;
    
    public function __construct()
    {
        $this->repository = new SqliteUserRepository();  // ❌ Direct instantiation
    }
}
```

#### After (Dependency Injection)
```php
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository  // ✅ Interface injection
    ) {
    }
}
```

### Container Configuration
```php
// Add to config/services/interfaces.php
UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
    return new SqliteUserRepository($container->get(\PDO::class));
},

UserService::class => \DI\autowire(),
```

## Performance Considerations

### 1. Lazy Loading
- Services are created only when needed
- Container caches resolved instances
- No performance penalty for unused services

### 2. Auto-Wiring Efficiency
- PHP-DI uses reflection caching
- Minimal overhead in production
- Compile container for maximum performance

### 3. Interface Resolution
- Interface bindings resolved once
- Subsequent calls use cached instances
- No repeated instantiation overhead

## Conclusion

The implemented DI/IoC system provides:

- **Clean Architecture**: SOLID principles compliance
- **Better Testability**: Easy mocking and unit testing
- **Improved Maintainability**: Clear dependencies and loose coupling
- **Enhanced Flexibility**: Easy to extend and modify
- **Production Ready**: Optimized for performance and reliability

This foundation enables scalable, maintainable, and testable application development while following industry best practices for dependency management.
