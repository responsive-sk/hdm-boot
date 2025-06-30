# Security Best Practices

Komplexný sprievodca bezpečným kódovaním v HDM Boot aplikácii.

## 🔒 Základné bezpečnostné princípy

### 1. Defense in Depth
Implementujte viacero vrstiev bezpečnosti:

```php
// ✅ Správne - viacero kontrol
public function updateUser(string $userId, array $data): User
{
    // 1. Autentifikácia
    $this->authService->requireAuthentication();
    
    // 2. Autorizácia
    $this->authService->requirePermission('user.update', $userId);
    
    // 3. Validácia vstupu
    $validatedData = $this->validator->validate($data, UserUpdateSchema::class);
    
    // 4. Sanitizácia
    $sanitizedData = $this->sanitizer->sanitize($validatedData);
    
    // 5. Biznis logika s kontrolami
    return $this->userService->update($userId, $sanitizedData);
}
```

### 2. Fail Secure
Vždy zlyhajte bezpečne:

```php
// ✅ Správne - bezpečné zlyhanie
public function hasPermission(string $permission): bool
{
    try {
        return $this->permissionChecker->check($permission);
    } catch (Exception $e) {
        // Loguj chybu ale vráť false (bezpečné)
        $this->logger->error('Permission check failed', ['error' => $e->getMessage()]);
        return false; // Bezpečné zlyhanie
    }
}

// ❌ Nesprávne - nebezpečné zlyhanie
public function hasPermissionBad(string $permission): bool
{
    try {
        return $this->permissionChecker->check($permission);
    } catch (Exception $e) {
        return true; // NEBEZPEČNÉ!
    }
}
```

## 🛡️ Input Validation & Sanitization

### 1. Validácia všetkých vstupov

```php
use Respect\Validation\Validator as v;

class UserValidator
{
    public function validateRegistration(array $data): array
    {
        $validator = v::key('email', v::email())
                    ->key('password', v::length(8, 128)->regex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'))
                    ->key('name', v::stringType()->length(2, 50)->regex('/^[a-zA-ZáčďéíľňóšťúýžÁČĎÉÍĽŇÓŠŤÚÝŽ\s]+$/'));

        if (!$validator->validate($data)) {
            throw new ValidationException('Invalid input data');
        }

        return $data;
    }
}
```

### 2. Sanitizácia výstupov

```php
class OutputSanitizer
{
    public function sanitizeForHtml(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function sanitizeForJson(mixed $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    public function sanitizeForSql(string $input): string
    {
        // Používaj prepared statements namiesto tohto!
        return addslashes($input);
    }
}
```

## 🔐 Authentication & Authorization

### 1. Bezpečná autentifikácia

```php
class AuthService
{
    public function authenticate(string $email, string $password): ?User
    {
        // Rate limiting
        if ($this->rateLimiter->isLimited($email)) {
            throw new TooManyAttemptsException();
        }

        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !$this->verifyPassword($password, $user->getPasswordHash())) {
            // Rovnaký čas pre existujúceho aj neexistujúceho usera
            $this->simulatePasswordVerification();
            $this->rateLimiter->increment($email);
            return null;
        }

        $this->rateLimiter->reset($email);
        return $user;
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function simulatePasswordVerification(): void
    {
        // Simuluj čas potrebný na overenie hesla
        password_verify('dummy', '$2y$10$dummy.hash.to.prevent.timing.attacks');
    }
}
```

### 2. JWT Token Security

```php
class JwtService
{
    public function createToken(User $user): string
    {
        $payload = [
            'sub' => $user->getId(),
            'iat' => time(),
            'exp' => time() + $this->tokenLifetime,
            'jti' => $this->generateJti(), // Unique token ID
            'aud' => $this->audience,
            'iss' => $this->issuer
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // Kontrola blacklistu
            if ($this->tokenBlacklist->isBlacklisted($decoded->jti)) {
                return null;
            }

            return (array) $decoded;
        } catch (Exception $e) {
            $this->logger->warning('Invalid JWT token', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
```

## 🚫 Prevencia útokov

### 1. SQL Injection Prevention

```php
// ✅ Správne - Prepared statements
class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? User::fromArray($data) : null;
    }

    // ❌ NIKDY NEROBTE TOTO
    public function findByEmailBad(string $email): ?User
    {
        $sql = "SELECT * FROM users WHERE email = '$email'"; // VULNERABLE!
        return $this->pdo->query($sql)->fetch();
    }
}
```

### 2. XSS Prevention

```php
// Template rendering s automatickou sanitizáciou
class TemplateRenderer
{
    public function render(string $template, array $data): string
    {
        // Automaticky sanitizuj všetky premenné
        $sanitizedData = array_map([$this, 'sanitizeValue'], $data);
        
        return $this->twig->render($template, $sanitizedData);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }
        
        return $value;
    }
}
```

### 3. CSRF Protection

```php
class CsrfMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->getParsedBody()['csrf_token'] ?? '';
            
            if (!$this->csrfTokenManager->isValid($token)) {
                throw new CsrfTokenException('Invalid CSRF token');
            }
        }

        return $handler->handle($request);
    }
}
```

## 🔒 Path Security

### 1. Bezpečné práca s cestami

```php
use ResponsiveSk\Slim4Paths\Paths;

class FileService
{
    private Paths $paths;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;
    }

    public function readFile(string $filename): string
    {
        // ✅ Správne - použitie Paths service
        $securePath = $this->paths->getSecurePath("uploads/{$filename}");
        
        if (!$securePath || !file_exists($securePath)) {
            throw new FileNotFoundException();
        }

        return file_get_contents($securePath);
    }

    // ❌ NIKDY NEROBTE TOTO
    public function readFileBad(string $filename): string
    {
        $path = "/var/www/uploads/" . $filename; // VULNERABLE!
        return file_get_contents($path);
    }
}
```

## 📝 Security Checklist

### Pre každý nový feature:

- [ ] **Input Validation** - Všetky vstupy validované
- [ ] **Output Sanitization** - Všetky výstupy sanitizované  
- [ ] **Authentication** - Požaduje autentifikáciu ak treba
- [ ] **Authorization** - Kontroluje oprávnenia
- [ ] **Rate Limiting** - Implementované pre citlivé operácie
- [ ] **Logging** - Bezpečnostné udalosti logované
- [ ] **Error Handling** - Bezpečné spracovanie chýb
- [ ] **Path Security** - Používa Paths service
- [ ] **SQL Injection** - Používa prepared statements
- [ ] **XSS Prevention** - Sanitizuje výstupy
- [ ] **CSRF Protection** - Implementované pre formuláre

### Pre deployment:

- [ ] **HTTPS** - Vynútené v produkcii
- [ ] **Security Headers** - Nastavené správne
- [ ] **Environment Variables** - Citlivé údaje v .env
- [ ] **File Permissions** - Správne nastavené
- [ ] **Database Security** - Bezpečná konfigurácia
- [ ] **Backup Security** - Šifrované zálohy
- [ ] **Monitoring** - Bezpečnostné monitorovanie aktívne

## 🚨 Incident Response

### Pri bezpečnostnom incidente:

1. **Immediate Response**
   - Izoluj problém
   - Zmeň všetky credentials
   - Aktivuj incident response team

2. **Investigation**
   - Analyzuj logy
   - Identifikuj rozsah
   - Dokumentuj findings

3. **Recovery**
   - Oprav vulnerabilities
   - Obnov zo zálohy ak treba
   - Testuj opravy

4. **Post-Incident**
   - Aktualizuj security measures
   - Školenie tímu
   - Dokumentuj lessons learned

## 📚 Ďalšie zdroje

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsecurity.readthedocs.io/)
- [HDM Boot Security Documentation](../SECURITY.md)
- [Path Security Guide](../PATH_SECURITY.md)
