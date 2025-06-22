# Clean Architecture Implementation

## Overview

This document describes the Clean Architecture implementation in the HDM Boot project. The architecture follows Uncle Bob's Clean Architecture principles with clear layer separation and dependency inversion.

## Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                        │
│              (HTTP/CLI/API Adapters)                       │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                Domain Layer                         │   │
│  │            (Pure Business Logic)                    │   │
│  │  ┌─────────────────────────────────────────────┐   │   │
│  │  │              Core Entities                  │   │   │
│  │  │           (Domain Models)                   │   │   │
│  │  └─────────────────────────────────────────────┘   │   │
│  │                                                     │   │
│  │  Domain Services    DTOs    Domain Exceptions      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  Infrastructure Layer (Repositories, External Services)    │
└─────────────────────────────────────────────────────────────┘
```

## Dependency Direction

**The Dependency Rule:** Dependencies point inward. Inner layers know nothing about outer layers.

- **Application Layer** depends on **Domain Layer**
- **Domain Layer** depends on **Core Entities**
- **Infrastructure Layer** depends on **Domain Layer** (through interfaces)

## Layer Responsibilities

### 1. Core Entities (Domain Models)
- Business entities with behavior and validation
- No dependencies on outer layers
- Pure PHP objects with business rules

```php
// modules/Core/User/Domain/Models/User.php
final readonly class User
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public string $status
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function validate(): array
    {
        $errors = [];
        
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        return $errors;
    }
}
```

### 2. Domain Layer (Business Logic)
- Domain services with pure business logic
- Use cases and business rules
- DTOs for data transfer
- Domain-specific exceptions

```php
// modules/Core/Security/Domain/Services/AuthenticationDomainService.php
final class AuthenticationDomainService
{
    public function handleLogin(LoginRequest $loginRequest): LoginResult
    {
        // Pure business logic - no framework dependencies
        $validationErrors = $loginRequest->validate();
        if (!empty($validationErrors)) {
            return LoginResult::failure('Invalid input', 'VALIDATION_ERROR');
        }

        $user = $this->userService->authenticate(
            $loginRequest->email, 
            $loginRequest->password
        );

        return $user 
            ? LoginResult::success($user)
            : LoginResult::failure('Invalid credentials', 'INVALID_CREDENTIALS');
    }
}
```

### 3. Application Layer (Adapters)
- HTTP/CLI/API adapters
- Framework-specific code
- Transform external requests to domain DTOs
- Transform domain results to external responses

```php
// modules/Core/Security/Application/Actions/LoginSubmitAction.php
final class LoginSubmitAction
{
    public function __invoke(
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        // Transform HTTP request to domain DTO
        $loginRequest = $this->createLoginRequestFromHttpRequest($request);

        // Delegate to domain service
        $loginResult = $this->authenticationDomainService->handleLogin($loginRequest);

        // Transform domain result to HTTP response
        return $loginResult->isSuccess()
            ? $this->handleSuccessfulLogin($loginResult, $response)
            : $this->handleFailedLogin($loginResult, $response);
    }
}
```

### 4. Infrastructure Layer (External Concerns)
- Database repositories
- External service integrations
- File system operations
- Third-party APIs

```php
// modules/Core/User/Repository/SqliteUserRepository.php
final class SqliteUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
```

## Data Flow

### Inbound Flow (Request → Response)

```
HTTP Request
     ↓
Application Layer (Action)
     ↓ (creates DTO)
Domain Layer (Domain Service)
     ↓ (uses interface)
Infrastructure Layer (Repository)
     ↓ (returns data)
Domain Layer (creates result DTO)
     ↓ (returns result)
Application Layer (transforms to HTTP response)
     ↓
HTTP Response
```

### Example Flow

```php
// 1. HTTP Request arrives
POST /login { email: "user@example.com", password: "secret" }

// 2. Application Layer creates DTO
$loginRequest = LoginRequest::fromArray([
    'email' => 'user@example.com',
    'password' => 'secret',
    'client_ip' => '127.0.0.1'
]);

// 3. Domain Layer processes business logic
$loginResult = $authenticationDomainService->handleLogin($loginRequest);

// 4. Infrastructure Layer (via UserService → Repository)
$userData = $userRepository->findByEmail('user@example.com');

// 5. Domain Layer returns result
return LoginResult::success($userData);

// 6. Application Layer transforms to HTTP response
return $response->withStatus(302)->withHeader('Location', '/dashboard');
```

## Interface Segregation

### Repository Interfaces

```php
// Domain defines what it needs
interface UserRepositoryInterface
{
    public function findById(string $id): ?array;
    public function findByEmail(string $email): ?array;
    public function save(array $userData): array;
}

// Infrastructure implements the interface
class SqliteUserRepository implements UserRepositoryInterface
{
    // Implementation details
}
```

### Service Interfaces

```php
// Domain service interface
interface AuthenticationServiceInterface
{
    public function authenticate(string $email, string $password): ?array;
}

// Application service implements interface
class UserService implements AuthenticationServiceInterface
{
    // Implementation with repository dependency
}
```

## Dependency Injection

### Container Configuration

```php
// config/services/interfaces.php
return [
    // Repository interfaces
    UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
        return new SqliteUserRepository($container->get(\PDO::class));
    },

    // Domain services (auto-wired)
    AuthenticationDomainService::class => \DI\autowire(),
    UserDomainService::class => \DI\autowire(),

    // Application actions (auto-wired)
    LoginSubmitAction::class => \DI\autowire(),
];
```

## Testing Strategy

### 1. Unit Tests (Domain Layer)

Test business logic without external dependencies:

```php
class AuthenticationDomainServiceTest extends TestCase
{
    public function testSuccessfulLogin(): void
    {
        // Mock dependencies
        $mockUserService = $this->createMock(UserService::class);
        $mockSecurityChecker = $this->createMock(SecurityLoginChecker::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        // Create service with mocks
        $service = new AuthenticationDomainService(
            $mockUserService,
            $mockSecurityChecker,
            $mockLogger
        );

        // Test business logic
        $loginRequest = LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => 'password123',
            'client_ip' => '127.0.0.1'
        ]);

        $mockUserService->expects($this->once())
            ->method('authenticate')
            ->willReturn(['id' => '123', 'email' => 'test@example.com']);

        $result = $service->handleLogin($loginRequest);

        $this->assertTrue($result->isSuccess());
    }
}
```

### 2. Integration Tests (Application Layer)

Test HTTP request/response flow:

```php
class LoginSubmitActionTest extends TestCase
{
    public function testSuccessfulLogin(): void
    {
        // Create HTTP request
        $request = $this->createRequest('POST', '/login')
            ->withParsedBody([
                'email' => 'test@example.com',
                'password' => 'password123',
                'csrf_token' => 'valid-token'
            ]);

        // Execute action
        $response = $this->action->__invoke($request, $this->createResponse());

        // Assert HTTP response
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }
}
```

### 3. Repository Tests (Infrastructure Layer)

Test data access layer:

```php
class SqliteUserRepositoryTest extends TestCase
{
    public function testFindById(): void
    {
        // Setup test database
        $pdo = $this->createTestDatabase();
        $repository = new SqliteUserRepository($pdo);

        // Insert test data
        $this->insertTestUser($pdo, ['id' => '123', 'email' => 'test@example.com']);

        // Test repository
        $user = $repository->findById('123');

        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user['email']);
    }
}
```

## Benefits

### 1. Independence

- **Framework Independence:** Business logic doesn't depend on web framework
- **Database Independence:** Can switch databases without changing business logic
- **UI Independence:** Same business logic works for web, API, CLI
- **External Service Independence:** Easy to mock and replace external services

### 2. Testability

- **Unit Tests:** Test business logic in isolation
- **Integration Tests:** Test layer interactions
- **Mock External Dependencies:** Easy to mock repositories and services

### 3. Maintainability

- **Clear Boundaries:** Each layer has specific responsibilities
- **Dependency Direction:** Changes in outer layers don't affect inner layers
- **Single Responsibility:** Each class has one reason to change

### 4. Flexibility

- **Multiple Interfaces:** Same business logic for web, API, CLI
- **Easy Extensions:** Add new features without changing existing code
- **Technology Changes:** Swap implementations without affecting business logic

## Common Patterns

### 1. Repository Pattern

```php
// Domain interface
interface UserRepositoryInterface
{
    public function findById(string $id): ?array;
}

// Infrastructure implementation
class SqliteUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?array
    {
        // Database-specific implementation
    }
}
```

### 2. DTO Pattern

```php
// Input DTO
final readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $clientIp
    ) {}
}

// Output DTO
final readonly class LoginResult
{
    public function __construct(
        public bool $success,
        public ?array $user = null,
        public ?string $errorMessage = null
    ) {}
}
```

### 3. Adapter Pattern

```php
// HTTP Adapter
class LoginSubmitAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // Transform HTTP → Domain
        $loginRequest = $this->createDTOFromRequest($request);
        
        // Execute business logic
        $result = $this->domainService->handleLogin($loginRequest);
        
        // Transform Domain → HTTP
        return $this->createResponseFromResult($result);
    }
}
```

## Migration Checklist

### Phase 1: Create Structure
- [ ] Create Domain directories (DTOs, Services, Models, Exceptions)
- [ ] Create Application directories (Actions)
- [ ] Update container configuration

### Phase 2: Extract Business Logic
- [ ] Identify business logic in existing actions
- [ ] Create domain services with pure business logic
- [ ] Create DTOs for input/output
- [ ] Create domain models with behavior

### Phase 3: Create Adapters
- [ ] Update actions to delegate to domain services
- [ ] Transform HTTP requests to DTOs
- [ ] Transform domain results to HTTP responses

### Phase 4: Add Tests
- [ ] Unit tests for domain services
- [ ] Integration tests for application adapters
- [ ] Repository tests for infrastructure

### Phase 5: Validate
- [ ] Verify dependency direction
- [ ] Ensure no framework dependencies in domain layer
- [ ] Test business logic reusability across contexts

## Conclusion

Clean Architecture implementation provides:

- **Separation of Concerns:** Clear layer boundaries and responsibilities
- **Dependency Inversion:** Inner layers independent of outer layers
- **Testability:** Easy unit testing of business logic
- **Flexibility:** Same business logic across multiple interfaces
- **Maintainability:** Changes in one layer don't affect others

This architecture enables long-term maintainability, testability, and flexibility while following industry best practices for enterprise application development.
