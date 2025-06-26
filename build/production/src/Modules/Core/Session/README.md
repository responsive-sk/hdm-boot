# Session Module

Session Module poskytuje kompletnú správu sessions, CSRF ochranu a session persistence pre MVA Bootstrap aplikáciu.

## 🎯 Účel

SessionModule oddeľuje session-related funkcionalitu od Security a Core modulov do samostatného, znovupoužiteľného modulu.

## 🏗️ Architektúra

### Poskytované služby

- **SessionService** - Správa user sessions s bezpečnostnými funkciami
- **CsrfService** - Cross-Site Request Forgery ochrana
- **SessionStartMiddleware** - Automatické spustenie sessions

### Závislosti

- **Žiadne** - SessionModule je base module bez závislostí
- Ostatné moduly môžu závisieť od SessionModule

## 📦 Komponenty

### Services

#### SessionService
```php
// Login user a vytvorenie session
$sessionService->loginUser($user);

// Kontrola prihlásenia
if ($sessionService->isLoggedIn()) {
    $userId = $sessionService->getUserId();
    $userData = $sessionService->getUserData();
}

// Logout
$sessionService->logoutUser();

// Flash messages
$sessionService->setFlash('success', 'Operation completed');
$message = $sessionService->getFlash('success');
```

#### CsrfService
```php
// Generovanie CSRF tokenu
$token = $csrfService->generateToken('form_action');

// HTML input pre CSRF token
echo $csrfService->getHiddenInput('form_action');

// Validácia z request dát
$csrfService->validateFromRequest($_POST, 'form_action');

// Manuálna validácia
if ($csrfService->validateToken($token, 'form_action')) {
    // Token je platný
}
```

### Middleware

#### SessionStartMiddleware
Automaticky spúšťa sessions pre všetky requesty.

```php
// Automaticky sa aplikuje cez DI container
// Žiadna manuálna konfigurácia nie je potrebná
```

### Exceptions

#### SecurityException
```php
try {
    $csrfService->validateFromRequest($_POST);
} catch (SecurityException $e) {
    $errorData = $e->toArray();
    // Handle CSRF validation error
}
```

## ⚙️ Konfigurácia

### Environment Variables

```env
SESSION_NAME=boot_session
SESSION_LIFETIME=7200
SESSION_COOKIE_SECURE=false
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

### Module Settings

```php
'settings' => [
    'session' => [
        'name'            => 'boot_session',
        'lifetime'        => 7200, // 2 hours
        'cookie_secure'   => false,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'csrf' => [
        'token_length'    => 32,
        'max_tokens'      => 10,
        'session_key'     => 'csrf_tokens',
    ],
]
```

## 🔗 Integrácia s inými modulmi

### Security Module
```php
// Security module teraz závisí od Session module
'dependencies' => ['User', 'Session']

// Používa SessionService a CsrfService zo Session module
use MvaBootstrap\Modules\Core\Session\Services\SessionService;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
```

### Template Module
```php
// Template module závisí od Session module pre CSRF tokeny
'dependencies' => ['Session']

// Používa CsrfService zo Session module
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
```

## 🛡️ Bezpečnosť

### Session Security
- **Session ID regeneration** pri login/logout
- **Session timeout** kontrola
- **Secure cookies** konfigurácia
- **HttpOnly** a **SameSite** protection

### CSRF Protection
- **Secure token generation** pomocou `random_bytes()`
- **Hash-based validation** s `hash_equals()`
- **One-time use tokens** (token sa zmaže po validácii)
- **Token limit** (max 10 tokenov v session)

## 📊 Status

### Implementované
- ✅ Session management s PhpSession
- ✅ CSRF token generation a validation
- ✅ Session persistence a security
- ✅ Session start middleware
- ✅ Configurable session options
- ✅ Environment-driven configuration
- ✅ Security exceptions handling

### Plánované
- 🔄 Session storage backends (Redis, Database)
- 🔄 Session encryption
- 🔄 Session analytics a monitoring
- 🔄 Advanced session security features
