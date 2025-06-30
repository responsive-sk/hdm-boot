# Authentication Integration Guide

Komplexný sprievodca integráciou autentifikácie v HDM Boot aplikácii.

## 🔐 Authentication Overview

HDM Boot používa **JWT-based authentication** s týmito komponentmi:

- **JWT Tokens** - Stateless authentication
- **Role-based Authorization** - Hierarchické oprávnenia
- **Session Management** - Hybrid session/token approach
- **Multi-database Support** - Oddelené user stores
- **Rate Limiting** - Ochrana proti brute force

## 🏗️ Authentication Architecture

```
Authentication Flow:
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│   Client    │───▶│ Auth Service │───▶│ JWT Service │
└─────────────┘    └──────────────┘    └─────────────┘
                           │                    │
                           ▼                    ▼
                   ┌──────────────┐    ┌─────────────┐
                   │ User Service │    │ Token Store │
                   └──────────────┘    └─────────────┘
                           │
                           ▼
                   ┌──────────────┐
                   │  Database    │
                   └──────────────┘
```

## 🚀 Basic Integration

### 1. Service Container Setup

```php
<?php
// config/container.php

use HdmBoot\Modules\Core\Security\Application\Service\{AuthService, JwtService};
use HdmBoot\Modules\Core\User\Infrastructure\Repository\UserRepository;

return [
    // JWT Configuration
    JwtService::class => function (ContainerInterface $container) {
        return new JwtService(
            secretKey: $_ENV['JWT_SECRET'],
            issuer: $_ENV['APP_NAME'],
            audience: $_ENV['APP_URL'],
            tokenLifetime: (int) $_ENV['JWT_EXPIRY']
        );
    },

    // Authentication Service
    AuthService::class => function (ContainerInterface $container) {
        return new AuthService(
            userRepository: $container->get(UserRepository::class),
            jwtService: $container->get(JwtService::class),
            rateLimiter: $container->get(RateLimiterInterface::class),
            eventDispatcher: $container->get(EventDispatcherInterface::class)
        );
    },

    // User Repository
    UserRepository::class => function (ContainerInterface $container) {
        return new UserRepository(
            pdo: $container->get('user.database'),
            paths: $container->get(Paths::class)
        );
    },
];
```

### 2. Middleware Registration

```php
<?php
// config/middleware.php

use HdmBoot\SharedKernel\Presentation\Middleware\{
    AuthenticationMiddleware,
    AuthorizationMiddleware,
    RateLimitMiddleware
};

return [
    // Global middleware
    'global' => [
        RateLimitMiddleware::class,
    ],

    // Route-specific middleware
    'auth' => AuthenticationMiddleware::class,
    'auth.admin' => [
        AuthenticationMiddleware::class,
        AuthorizationMiddleware::class . ':admin'
    ],
    'auth.user' => [
        AuthenticationMiddleware::class,
        AuthorizationMiddleware::class . ':user'
    ],
];
```

## 🔑 Authentication Implementation

### Core Authentication Service

```php
<?php
// src/Modules/Core/Security/Application/Service/AuthService.php

namespace HdmBoot\Modules\Core\Security\Application\Service;

use HdmBoot\Modules\Core\User\Domain\Entity\User;
use HdmBoot\Modules\Core\User\Domain\Repository\UserRepositoryInterface;
use HdmBoot\SharedKernel\Event\EventDispatcherInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtService $jwtService,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function authenticate(string $email, string $password): AuthResult
    {
        // Rate limiting check
        if ($this->rateLimiter->isLimited("auth:{$email}")) {
            throw new TooManyAttemptsException('Too many login attempts');
        }

        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !$this->verifyPassword($password, $user->getPasswordHash())) {
            // Simulate password verification time to prevent timing attacks
            $this->simulatePasswordVerification();
            $this->rateLimiter->increment("auth:{$email}");
            
            $this->eventDispatcher->dispatch(new LoginFailedEvent($email));
            throw new InvalidCredentialsException('Invalid credentials');
        }

        if (!$user->isActive()) {
            throw new UserInactiveException('User account is inactive');
        }

        // Reset rate limiter on successful auth
        $this->rateLimiter->reset("auth:{$email}");

        // Generate tokens
        $accessToken = $this->jwtService->createAccessToken($user);
        $refreshToken = $this->jwtService->createRefreshToken($user);

        // Dispatch success event
        $this->eventDispatcher->dispatch(new LoginSuccessEvent($user));

        return new AuthResult(
            user: $user,
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresIn: $this->jwtService->getTokenLifetime()
        );
    }

    public function refreshToken(string $refreshToken): AuthResult
    {
        $payload = $this->jwtService->validateRefreshToken($refreshToken);
        
        if (!$payload) {
            throw new InvalidTokenException('Invalid refresh token');
        }

        $user = $this->userRepository->findById($payload['sub']);
        
        if (!$user || !$user->isActive()) {
            throw new UserNotFoundException('User not found or inactive');
        }

        // Generate new access token
        $newAccessToken = $this->jwtService->createAccessToken($user);

        return new AuthResult(
            user: $user,
            accessToken: $newAccessToken,
            refreshToken: $refreshToken, // Keep same refresh token
            expiresIn: $this->jwtService->getTokenLifetime()
        );
    }

    public function logout(string $token): void
    {
        // Add token to blacklist
        $this->jwtService->blacklistToken($token);
        
        // Dispatch logout event
        $payload = $this->jwtService->validateToken($token);
        if ($payload) {
            $this->eventDispatcher->dispatch(new LogoutEvent($payload['sub']));
        }
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function simulatePasswordVerification(): void
    {
        // Prevent timing attacks by simulating password verification
        password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attacks');
    }
}
```

### JWT Service Implementation

```php
<?php
// src/Modules/Core/Security/Application/Service/JwtService.php

namespace HdmBoot\Modules\Core\Security\Application\Service;

use Firebase\JWT\{JWT, Key};
use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secretKey,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $tokenLifetime,
        private readonly TokenBlacklistInterface $tokenBlacklist
    ) {}

    public function createAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $user->getId()->toString(),
            'iat' => $now,
            'exp' => $now + $this->tokenLifetime,
            'jti' => $this->generateJti(),
            'type' => 'access',
            'user' => [
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'permissions' => $user->getPermissions()
            ]
        ];

        return JWT::encode($payload, $this->secretKey, self::ALGORITHM);
    }

    public function createRefreshToken(User $user): string
    {
        $now = time();
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $user->getId()->toString(),
            'iat' => $now,
            'exp' => $now + ($this->tokenLifetime * 24), // 24x longer than access token
            'jti' => $this->generateJti(),
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, self::ALGORITHM);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, self::ALGORITHM));
            $payload = (array) $decoded;

            // Check if token is blacklisted
            if ($this->tokenBlacklist->isBlacklisted($payload['jti'])) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validateRefreshToken(string $token): ?array
    {
        $payload = $this->validateToken($token);
        
        if (!$payload || $payload['type'] !== 'refresh') {
            return null;
        }

        return $payload;
    }

    public function blacklistToken(string $token): void
    {
        $payload = $this->validateToken($token);
        
        if ($payload) {
            $this->tokenBlacklist->add($payload['jti'], $payload['exp']);
        }
    }

    public function getTokenLifetime(): int
    {
        return $this->tokenLifetime;
    }

    private function generateJti(): string
    {
        return bin2hex(random_bytes(16));
    }
}
```

## 🛡️ Authorization Middleware

### Role-based Authorization

```php
<?php
// src/SharedKernel/Presentation/Middleware/AuthorizationMiddleware.php

namespace HdmBoot\SharedKernel\Presentation\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final class AuthorizationMiddleware implements MiddlewareInterface
{
    private const ROLE_HIERARCHY = [
        'admin' => ['admin', 'moderator', 'user'],
        'moderator' => ['moderator', 'user'],
        'user' => ['user']
    ];

    public function __construct(
        private readonly string $requiredRole
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        
        if (!$user) {
            return $this->forbiddenResponse('Authentication required');
        }

        $userRole = $user->getRole();
        $allowedRoles = self::ROLE_HIERARCHY[$userRole] ?? [];

        if (!in_array($this->requiredRole, $allowedRoles)) {
            return $this->forbiddenResponse('Insufficient permissions');
        }

        return $handler->handle($request);
    }

    private function forbiddenResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => 'FORBIDDEN'
            ]
        ]));
        
        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

## 🔄 Frontend Integration

### JavaScript/TypeScript Client

```typescript
// auth-client.ts

interface AuthResponse {
    success: boolean;
    data?: {
        token: string;
        refreshToken: string;
        expiresIn: number;
        user: User;
    };
    error?: {
        message: string;
        code: string;
    };
}

class AuthClient {
    private baseUrl: string;
    private accessToken: string | null = null;
    private refreshToken: string | null = null;

    constructor(baseUrl: string) {
        this.baseUrl = baseUrl;
        this.loadTokensFromStorage();
    }

    async login(email: string, password: string): Promise<AuthResponse> {
        const response = await fetch(`${this.baseUrl}/api/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password }),
        });

        const data: AuthResponse = await response.json();

        if (data.success && data.data) {
            this.setTokens(data.data.token, data.data.refreshToken);
        }

        return data;
    }

    async logout(): Promise<void> {
        if (this.accessToken) {
            await fetch(`${this.baseUrl}/api/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.accessToken}`,
                },
            });
        }

        this.clearTokens();
    }

    async refreshAccessToken(): Promise<boolean> {
        if (!this.refreshToken) {
            return false;
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.refreshToken}`,
                },
            });

            const data: AuthResponse = await response.json();

            if (data.success && data.data) {
                this.setTokens(data.data.token, this.refreshToken);
                return true;
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
        }

        this.clearTokens();
        return false;
    }

    async authenticatedRequest(url: string, options: RequestInit = {}): Promise<Response> {
        const headers = {
            ...options.headers,
            'Authorization': `Bearer ${this.accessToken}`,
        };

        let response = await fetch(url, { ...options, headers });

        // If token expired, try to refresh
        if (response.status === 401 && this.refreshToken) {
            const refreshed = await this.refreshAccessToken();
            
            if (refreshed) {
                headers['Authorization'] = `Bearer ${this.accessToken}`;
                response = await fetch(url, { ...options, headers });
            }
        }

        return response;
    }

    private setTokens(accessToken: string, refreshToken: string): void {
        this.accessToken = accessToken;
        this.refreshToken = refreshToken;
        
        localStorage.setItem('access_token', accessToken);
        localStorage.setItem('refresh_token', refreshToken);
    }

    private clearTokens(): void {
        this.accessToken = null;
        this.refreshToken = null;
        
        localStorage.removeItem('access_token');
        localStorage.removeItem('refresh_token');
    }

    private loadTokensFromStorage(): void {
        this.accessToken = localStorage.getItem('access_token');
        this.refreshToken = localStorage.getItem('refresh_token');
    }

    isAuthenticated(): boolean {
        return this.accessToken !== null;
    }

    getAccessToken(): string | null {
        return this.accessToken;
    }
}

export default AuthClient;
```

## 🧪 Testing Authentication

### Unit Tests

```php
<?php
// tests/Unit/Security/AuthServiceTest.php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use HdmBoot\Modules\Core\Security\Application\Service\AuthService;

final class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private UserRepositoryInterface $userRepository;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->jwtService = $this->createMock(JwtService::class);
        $this->rateLimiter = $this->createMock(RateLimiterInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->jwtService,
            $this->rateLimiter,
            $this->eventDispatcher
        );
    }

    public function testAuthenticateSuccess(): void
    {
        $user = $this->createUser();
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->jwtService
            ->expects($this->once())
            ->method('createAccessToken')
            ->willReturn('access-token');

        $result = $this->authService->authenticate('test@example.com', 'password');

        $this->assertInstanceOf(AuthResult::class, $result);
        $this->assertEquals('access-token', $result->getAccessToken());
    }

    public function testAuthenticateInvalidCredentials(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);
        
        $this->authService->authenticate('test@example.com', 'wrong-password');
    }

    private function createUser(): User
    {
        return User::create(
            'test@example.com',
            password_hash('password', PASSWORD_DEFAULT),
            'Test User'
        );
    }
}
```

## 📋 Integration Checklist

### Setup:
- [ ] Konfigurovať JWT secret a nastavenia
- [ ] Registrovať authentication services v DI container
- [ ] Nastaviť middleware pre routes
- [ ] Konfigurovať rate limiting

### Implementation:
- [ ] Implementovať AuthService s proper error handling
- [ ] Vytvoriť JWT service s token management
- [ ] Pridať authorization middleware
- [ ] Implementovať token blacklisting

### Security:
- [ ] Validovať všetky inputs
- [ ] Implementovať rate limiting
- [ ] Použiť secure password hashing
- [ ] Pridať CSRF protection pre web routes

### Testing:
- [ ] Unit testy pre auth services
- [ ] Integration testy pre API endpoints
- [ ] Security testy (brute force, token validation)
- [ ] Frontend integration testy

## 🔗 Ďalšie zdroje

- [Authentication API](../api/auth-api.md)
- [Security Best Practices](security-practices.md)
- [JWT Documentation](https://jwt.io/)
- [Security Guide](../SECURITY.md)
