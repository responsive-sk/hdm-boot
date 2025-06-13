# Security Architecture

## Overview

MVA Bootstrap implementuje bezpečnostnú architektúru založenú na princípoch Clean Architecture a priemyselných štandardoch.

## Core Security Komponenty

### 1. Authentication Layer
```
src/Modules/Core/Security/
├── Actions/
│   ├── LoginAction.php
│   ├── LogoutAction.php
│   └── RefreshTokenAction.php
├── Domain/
│   ├── Services/
│   │   ├── AuthenticationService.php
│   │   └── TokenService.php
│   └── ValueObjects/
│       ├── Token.php
│       └── AuthenticatedUser.php
└── Infrastructure/
    ├── JWT/
    │   ├── JWTGenerator.php
    │   └── JWTValidator.php
    └── Services/
        └── ArgonPasswordHasher.php
```

## Bezpečnostná Pipeline

```php
// Security middleware stack
$app->add(RateLimitMiddleware::class);
$app->add(SecurityHeadersMiddleware::class);
$app->add(JWTAuthMiddleware::class);
$app->add(CsrfMiddleware::class);
```

## Vrstvy Ochrany

1. **Sieťová vrstva**
   - HTTPS vynútenie
   - Rate limiting
   - DDoS ochrana

2. **Aplikačná vrstva**
   - Validácia vstupov
   - CSRF ochrana
   - XSS prevencia
   - SQL injection prevencia

3. **Autentifikačná vrstva**
   - JWT autentifikácia
   - Hashovanie hesiel
   - Bezpečnosť sessions

4. **Autorizačná vrstva**
   - Role-based prístupové práva
   - Permission-based prístupové práva
   - Validácia vlastníctva zdrojov

## Security Services

### 1. Authentication Service
```php
interface AuthenticationServiceInterface
{
    public function authenticate(string $email, string $password): AuthResult;
    public function logout(string $token): void;
    public function refreshToken(string $refreshToken): TokenPair;
    public function validateToken(string $token): bool;
}
```

### 2. Password Service
```php
interface PasswordServiceInterface
{
    public function hash(string $password): string;
    public function verify(string $password, string $hash): bool;
    public function needsRehash(string $hash): bool;
}
```

### 3. Token Service
```php
interface TokenServiceInterface
{
    public function generate(array $claims): Token;
    public function validate(string $token): bool;
    public function decode(string $token): array;
    public function blacklist(string $token): void;
}
```

## Security Events

### 1. Authentication Events
```php
final class UserLoggedIn implements SecurityEventInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $ipAddress,
        public readonly string $userAgent
    ) {}
}
```

### 2. Authorization Events
```php
final class PermissionDenied implements SecurityEventInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $permission,
        public readonly string $resource
    ) {}
}
```

## Security Konfigurácia

```php
// config/security.php
return [
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'expiry' => 3600,               // 1 hodina
        'refresh_expiry' => 604800,     // 1 týždeň
        'algorithm' => 'HS256'
    ],
    'password' => [
        'algorithm' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,    // 64MB
            'time_cost' => 4,          // 4 iterácie
            'threads' => 3
        ]
    ],
    'session' => [
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];
```

## Security Monitoring

### 1. Bezpečnostné Logy
```php
// Logovanie bezpečnostných udalostí
$securityLogger->info('Prihlásenie užívateľa', [
    'user_id' => $user->getId(),
    'ip' => $request->getClientIp(),
    'timestamp' => time()
]);

// Logovanie podozrivej aktivity
$securityLogger->warning('Neúspešné prihlásenie', [
    'email' => $email,
    'ip' => $request->getClientIp(),
    'attempt_count' => $attempts
]);
```

### 2. Audit Trail
```php
// Logovanie zmien oprávnení
$auditLogger->info('Zmena oprávnení', [
    'user_id' => $userId,
    'permission' => $permission,
    'granted_by' => $adminId,
    'timestamp' => time()
]);

// Logovanie kritických operácií
$auditLogger->notice('Zmena hesla', [
    'user_id' => $userId,
    'changed_by' => $userId,
    'source_ip' => $request->getClientIp()
]);
```

## Bezpečnostné Best Practices

### 1. Správa Hesiel
```php
// SPRÁVNE: Používame bezpečné hashovanie
$hash = password_hash($password, PASSWORD_ARGON2ID, $options);

// ZLE: Používame slabé hashovanie
$hash = md5($password); // Nikdy nepoužívať!
```

### 2. Práca s Tokenmi
```php
// SPRÁVNE: Bezpečné generovanie tokenov
$token = $this->tokenService->generate([
    'user_id' => $user->getId(),
    'exp' => time() + 3600
]);

// ZLE: Slabé generovanie tokenov
$token = base64_encode($userId . time()); // Nikdy nepoužívať!
```

### 3. SQL Query Bezpečnosť
```php
// SPRÁVNE: Používame query builder s parametrami
$query = $this->queryFactory
    ->newSelect('users')
    ->where(['email' => $email]);

// ZLE: Priame spájanie reťazcov
$query = "SELECT * FROM users WHERE email = '" . $email . "'"; // Nikdy nepoužívať!
```

## Testovanie Bezpečnosti

### 1. Unit Testy
```php
class AuthenticationServiceTest extends TestCase
{
    public function testSuccessfulAuthentication(): void
    {
        $result = $this->authService->authenticate('user@example.com', 'password');
        $this->assertTrue($result->isSuccessful());
    }

    public function testFailedAuthentication(): void
    {
        $result = $this->authService->authenticate('user@example.com', 'wrong');
        $this->assertFalse($result->isSuccessful());
    }
}
```

### 2. Integration Testy
```php
class SecurityIntegrationTest extends TestCase
{
    public function testCompleteAuthFlow(): void
    {
        // Test login
        $response = $this->post('/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123'
        ]);
        
        $token = $response->getJson()['token'];
        
        // Test protected endpoint
        $response = $this->withToken($token)
            ->get('/api/protected');
        
        $this->assertTrue($response->isOk());
    }

    public function testInvalidTokenAccess(): void
    {
        $response = $this->withToken('invalid-token')
            ->get('/api/protected');
        
        $this->assertTrue($response->isUnauthorized());
    }
}
```

### 3. Security Smoke Tests
```php
class SecuritySmokeTest extends TestCase
{
    /**
     * @dataProvider securityHeadersProvider
     */
    public function testSecurityHeaders(string $header, string $expectedValue): void
    {
        $response = $this->get('/');
        
        $this->assertTrue(
            $response->hasHeader($header),
            "Missing security header: {$header}"
        );
        
        $this->assertEquals(
            $expectedValue,
            $response->getHeaderLine($header)
        );
    }

    public function securityHeadersProvider(): array
    {
        return [
            ['X-Frame-Options', 'DENY'],
            ['X-XSS-Protection', '1; mode=block'],
            ['X-Content-Type-Options', 'nosniff'],
            ['Referrer-Policy', 'strict-origin-when-cross-origin']
        ];
    }
}
```

## Bezpečnostný Roadmap

### Fáza 1: Základná Bezpečnosť ✅
- [x] Základná autentifikácia
- [x] Hashovanie hesiel
- [x] Bezpečnosť sessions
- [x] CSRF ochrana
- [x] Security headery

### Fáza 2: Rozšírená Bezpečnosť 🚧
- [ ] Dvojfaktorová autentifikácia (2FA)
- [ ] Správa API kľúčov
- [ ] Rate limiting
- [ ] IP blokovanie
- [ ] Automatická detekcia podozrivej aktivity

### Fáza 3: Enterprise Bezpečnosť 📅
- [ ] OAuth2 integrácia
- [ ] SSO (Single Sign-On)
- [ ] Hardware token podpora (FIDO2)
- [ ] Advanced audit logging
- [ ] Zero-trust architektúra
