# Domain-Driven Design (DDD) Implementation

## Overview

This document describes the Domain-Driven Design implementation in the MVA Bootstrap project. The architecture separates pure business logic from framework concerns, following Clean Architecture principles with clear layer boundaries.

## Architecture Layers

### 1. Domain Layer (Pure Business Logic)
The innermost layer containing business rules, entities, and domain services without any framework dependencies.

### 2. Application Layer (HTTP/CLI Adapters)
The outermost layer that handles framework-specific concerns and delegates to domain services.

### 3. Infrastructure Layer (Data Access)
Repository implementations and external service integrations.

## Directory Structure

```
modules/Core/
├── Security/
│   ├── Domain/                    ← Pure business logic
│   │   ├── DTOs/                  ← Data Transfer Objects
│   │   │   ├── LoginRequest.php
│   │   │   └── LoginResult.php
│   │   ├── Services/              ← Domain services
│   │   │   └── AuthenticationDomainService.php
│   │   ├── Models/                ← Domain entities
│   │   └── Exceptions/            ← Domain-specific exceptions
│   ├── Application/               ← HTTP/CLI adapters
│   │   └── Actions/
│   │       └── LoginSubmitAction.php
│   └── Services/                  ← Infrastructure services
└── User/
    ├── Domain/                    ← Pure business logic
    │   ├── Models/
    │   │   └── User.php
    │   ├── Services/
    │   │   └── UserDomainService.php
    │   ├── DTOs/
    │   └── Exceptions/
    ├── Application/               ← HTTP/CLI adapters
    └── Repository/                ← Infrastructure
```

## Domain Layer Implementation

### Data Transfer Objects (DTOs)

DTOs provide type-safe data contracts between layers:

```php
// LoginRequest DTO
final readonly class LoginRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $clientIp,
        public ?string $userAgent = null,
        public ?string $csrfToken = null,
        public bool $rememberMe = false
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            clientIp: (string) ($data['client_ip'] ?? '127.0.0.1'),
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null,
            csrfToken: isset($data['csrf_token']) ? (string) $data['csrf_token'] : null,
            rememberMe: (bool) ($data['remember_me'] ?? false)
        );
    }

    public function validate(): array
    {
        $errors = [];
        
        if (empty(trim($this->email))) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        return $errors;
    }
}
```

### Domain Services

Domain services contain pure business logic without framework dependencies:

```php
final class AuthenticationDomainService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly SecurityLoginChecker $securityChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    public function handleLogin(LoginRequest $loginRequest): LoginResult
    {
        try {
            // Validate input
            $validationErrors = $loginRequest->validate();
            if (!empty($validationErrors)) {
                return LoginResult::failure(
                    'Invalid input data',
                    'VALIDATION_ERROR',
                    ['validation_errors' => $validationErrors]
                );
            }

            // Security check
            $this->securityChecker->checkLoginSecurity(
                $loginRequest->email, 
                $loginRequest->clientIp
            );

            // Authenticate user
            $user = $this->userService->authenticate(
                $loginRequest->email, 
                $loginRequest->password
            );

            if (!$user) {
                $this->securityChecker->recordFailedAttempt(
                    $loginRequest->email, 
                    $loginRequest->clientIp
                );
                
                return LoginResult::failure(
                    'Invalid email or password',
                    'INVALID_CREDENTIALS'
                );
            }

            // Record success
            $this->securityChecker->recordSuccessfulAttempt(
                $loginRequest->email, 
                $loginRequest->clientIp
            );

            return LoginResult::success(
                user: $user,
                metadata: [
                    'login_time' => time(),
                    'client_ip' => $loginRequest->clientIp,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'error' => $e->getMessage(),
                'request' => $loginRequest->toLogArray(),
            ]);

            return LoginResult::failure(
                'An unexpected error occurred',
                'INTERNAL_ERROR'
            );
        }
    }
}
```

### Domain Models

Domain models encapsulate business entities with behavior and validation:

```php
final readonly class User
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $role,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt = null,
        public ?string $lastLoginAt = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            role: (string) ($data['role'] ?? 'user'),
            status: (string) ($data['status'] ?? 'active'),
            createdAt: (string) ($data['created_at'] ?? ''),
            updatedAt: isset($data['updated_at']) ? (string) $data['updated_at'] : null,
            lastLoginAt: isset($data['last_login_at']) ? (string) $data['last_login_at'] : null
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty(trim($this->email))) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (!in_array($this->role, ['admin', 'editor', 'user'], true)) {
            $errors[] = 'Invalid user role';
        }

        return $errors;
    }
}
```

## Application Layer Implementation

### HTTP Adapters

Application layer actions handle only HTTP concerns and delegate to domain services:

```php
final class LoginSubmitAction
{
    public function __construct(
        private readonly AuthenticationDomainService $authenticationDomainService,
        private readonly TemplateRenderer $templateRenderer,
        private readonly SessionInterface $session,
        private readonly CsrfService $csrfService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        try {
            // Extract and validate CSRF token
            $data = (array) $request->getParsedBody();
            $this->csrfService->validateFromRequest($data, 'login');

            // Create domain DTO from HTTP request
            $loginRequest = $this->createLoginRequestFromHttpRequest($request, $data);

            // Delegate to domain service (pure business logic)
            $loginResult = $this->authenticationDomainService->handleLogin($loginRequest);

            // Transform domain result to HTTP response
            if ($loginResult->isSuccess()) {
                return $this->handleSuccessfulLogin($loginResult, $request, $response);
            } else {
                return $this->handleFailedLogin($loginResult, $request, $response, $data);
            }
        } catch (\Exception $e) {
            // Handle HTTP-specific errors
            return $this->handleError($e, $request, $response);
        }
    }

    private function createLoginRequestFromHttpRequest(
        ServerRequestInterface $request,
        array $data
    ): LoginRequest {
        $serverParams = $request->getServerParams();

        return LoginRequest::fromArray([
            'email' => $data['email'] ?? '',
            'password' => $data['password'] ?? '',
            'client_ip' => $serverParams['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $serverParams['HTTP_USER_AGENT'] ?? null,
            'csrf_token' => $data['csrf_token'] ?? null,
            'remember_me' => isset($data['remember_me']),
        ]);
    }

    private function handleSuccessfulLogin(
        LoginResult $loginResult,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Handle HTTP session management
        $user = $loginResult->getUser();
        $this->session->set('user_id', $user['id']);
        
        // HTTP redirect
        $redirectUrl = $request->getQueryParams()['redirect'] ?? '/';
        return $response->withStatus(302)->withHeader('Location', $redirectUrl);
    }
}
```

## Benefits

### 1. Separation of Concerns

**Before DDD:**
```php
// ❌ Business logic mixed with HTTP concerns
final class LoginSubmitAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // HTTP parsing + business logic + session management all mixed
        $data = $request->getParsedBody();
        $user = $this->userService->authenticate($data['email'], $data['password']);
        if ($user) {
            $this->session->set('user_id', $user['id']);
            return $response->withStatus(302);
        }
        return $this->templateRenderer->render($response, 'login.php', ['error' => '...']);
    }
}
```

**After DDD:**
```php
// ✅ Clear separation
final class LoginSubmitAction  // HTTP Adapter
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $loginRequest = $this->createDTOFromRequest($request);  // HTTP → DTO
        $result = $this->domainService->handleLogin($loginRequest);  // Domain logic
        return $this->transformToResponse($result, $response);  // DTO → HTTP
    }
}

final class AuthenticationDomainService  // Pure Business Logic
{
    public function handleLogin(LoginRequest $request): LoginResult
    {
        // Pure business logic - no HTTP dependencies
    }
}
```

### 2. Testability

**Domain Service Testing:**
```php
class AuthenticationDomainServiceTest extends TestCase
{
    public function testSuccessfulLogin(): void
    {
        // No HTTP mocking needed - pure business logic testing
        $loginRequest = LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => 'password123',
            'client_ip' => '127.0.0.1'
        ]);

        $result = $this->authenticationDomainService->handleLogin($loginRequest);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->getUser());
    }

    public function testInvalidCredentials(): void
    {
        $loginRequest = LoginRequest::fromArray([
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'client_ip' => '127.0.0.1'
        ]);

        $result = $this->authenticationDomainService->handleLogin($loginRequest);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('INVALID_CREDENTIALS', $result->getErrorCode());
    }
}
```

### 3. Reusability

Domain services can be used from multiple contexts:

```php
// HTTP Context
class WebLoginAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $loginRequest = $this->createFromHttpRequest($request);
        $result = $this->authDomainService->handleLogin($loginRequest);
        return $this->createHttpResponse($result);
    }
}

// API Context
class ApiLoginAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $loginRequest = $this->createFromApiRequest($request);
        $result = $this->authDomainService->handleLogin($loginRequest);
        return $this->createJsonResponse($result->toArray());
    }
}

// CLI Context
class ConsoleLoginCommand
{
    public function execute(InputInterface $input): int
    {
        $loginRequest = $this->createFromConsoleInput($input);
        $result = $this->authDomainService->handleLogin($loginRequest);
        $this->outputResult($result);
        return $result->isSuccess() ? 0 : 1;
    }
}
```

## Best Practices

### 1. Domain Layer Rules

- **No framework dependencies** - Domain services should not import HTTP, database, or framework classes
- **Pure business logic** - Focus only on business rules and validation
- **Immutable DTOs** - Use readonly DTOs for data transfer
- **Rich domain models** - Include business behavior in domain entities

### 2. Application Layer Rules

- **Thin adapters** - Actions should only transform data and delegate to domain services
- **Framework-specific** - Handle HTTP, CLI, or API concerns
- **Error transformation** - Convert domain errors to appropriate HTTP responses

### 3. DTO Design

- **Immutable** - Use readonly properties
- **Validation** - Include validation methods
- **Factory methods** - Provide fromArray() methods for easy creation
- **Logging-safe** - Exclude sensitive data from logging methods

### 4. Testing Strategy

- **Domain services** - Test business logic without framework mocking
- **Application adapters** - Test HTTP transformation and error handling
- **Integration tests** - Test full request/response flow

## Migration Guide

### Step 1: Create Domain Structure
```bash
mkdir -p modules/Core/ModuleName/Domain/{DTOs,Services,Models,Exceptions}
mkdir -p modules/Core/ModuleName/Application/Actions
```

### Step 2: Extract Business Logic
1. Identify business logic in existing actions
2. Create domain services with pure business logic
3. Create DTOs for input/output data
4. Update actions to delegate to domain services

### Step 3: Update Container
```php
// Add domain services to container
\ModuleName\Domain\Services\DomainService::class => \DI\autowire(),
```

### Step 4: Write Tests
1. Test domain services with unit tests
2. Test application adapters with integration tests
3. Verify business logic works across different contexts

## Conclusion

The Domain-Driven Design implementation provides:

- **Clean separation** between business logic and framework concerns
- **Better testability** through pure domain services
- **Improved reusability** across different contexts (HTTP, CLI, API)
- **Type safety** through DTOs and domain models
- **Maintainability** through clear architectural boundaries

This foundation enables scalable, testable, and maintainable application development while following industry best practices for domain modeling and clean architecture.
