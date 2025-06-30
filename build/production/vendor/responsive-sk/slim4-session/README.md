# 🔐 ResponsiveSk Slim4 Session

Complete session management for Slim 4 with zero external dependencies and full type safety.

## ✨ **Features**

- ✅ **Zero Dependencies** - No external session libraries required
- ✅ **Type Safety** - Full PHPStan level max compatibility with @throws annotations
- ✅ **Flash Messages** - Built-in flash message support with dedicated interface
- ✅ **Factory Pattern** - Easy configuration for different environments
- ✅ **Native PHP Sessions** - Direct PHP session management with enhanced interface
- ✅ **Custom Storage Engines** - Redis, Database, and custom storage support
- ✅ **PSR-15 Middleware** - Ready-to-use middleware for Slim 4
- ✅ **Auto-Refresh** - Automatic session lifetime extension on user activity
- ✅ **Production Ready** - Secure defaults and best practices

## 📦 **Installation**

```bash
composer require responsive-sk/slim4-session
```

## 🚀 **Quick Start**

### **Basic Usage**

```php
use ResponsiveSk\Slim4Session\SessionFactory;

// Create session manager
$session = SessionFactory::create();

// Start session
$session->start();

// Set data
$session->set('user_id', 123);
$session->set('username', 'john_doe');

// Get data
$userId = $session->get('user_id');
$username = $session->get('username', 'guest');

// Check if key exists
if ($session->has('user_id')) {
    echo 'User is logged in';
}

// Flash messages
$session->flash('success', 'Login successful!');
$message = $session->getFlashMessage('success');

// Advanced flash usage
$flash = $session->getFlash();
$flash->add('error', 'Something went wrong');
$errors = $flash->consume('error'); // Get and clear

// Session management
$sessionId = $session->getId();
$session->regenerateId();
$session->destroy();
```

### **Environment-Specific Configuration**

```php
// Production (secure settings)
$session = SessionFactory::createForProduction([
    'name' => 'my_app_session',
    'cache_expire' => 180,
]);

// Development (relaxed settings)
$session = SessionFactory::createForDevelopment([
    'name' => 'dev_session',
]);

// Testing (no cookies)
$session = SessionFactory::createForTesting();
```

### **Custom Configuration**

```php
$session = SessionFactory::create([
    'name' => 'custom_session',
    'cookie_lifetime' => 3600,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);
```

## 🔧 **Complete Interface**

Our `SessionInterface` provides complete session management:

```php
interface SessionInterface extends \Countable, \IteratorAggregate
{
    // Core session methods
    public function set(string $key, mixed $value): void;
    public function get(string $key, mixed $default = null): mixed;
    public function remove(string $key): void;
    public function has(string $key): bool;
    public function all(): array;
    public function clear(): void;

    // Session lifecycle
    public function isStarted(): bool;
    public function start(): bool;
    public function destroy(): bool;

    // Session ID management
    public function getId(): ?string;
    public function regenerateId(): bool;
    public function getName(): string;
    public function setName(string $name): void;

    // Flash messages
    public function flash(string $key, mixed $value): void;
    public function getFlash(): FlashInterface;
    public function getFlashMessage(string $key, mixed $default = null): mixed;
    public function hasFlash(string $key): bool;
}
```

## 🏗️ **Architecture**

### **Native Implementation**

The package provides a complete native PHP session implementation:

```
ResponsiveSk\Slim4Session\SessionManager
    ↓ implements
ResponsiveSk\Slim4Session\SessionInterface
    ↓ uses
Native PHP $_SESSION + FlashManager
```

### **Factory Pattern**

```php
SessionFactory::create()                    // Default config
SessionFactory::createForProduction()      // Production config
SessionFactory::createForDevelopment()     // Development config
SessionFactory::createForTesting()         // Testing config
SessionFactory::createWithConfig()         // Custom config
```

## 🔒 **Security Features**

### **Secure Defaults**

- ✅ **HttpOnly cookies** - Prevents XSS attacks
- ✅ **Secure cookies** - HTTPS only in production
- ✅ **SameSite protection** - CSRF protection
- ✅ **Strict mode** - Prevents session fixation
- ✅ **Session regeneration** - Built-in ID regeneration

### **Production Configuration**

```php
$session = SessionFactory::createForProduction([
    'cookie_secure' => true,        // HTTPS only
    'cookie_httponly' => true,      // No JavaScript access
    'cookie_samesite' => 'Strict',  // CSRF protection
    'use_strict_mode' => true,      // Session fixation protection
]);
```

## 🧪 **Testing**

```php
// Create test session (no cookies)
$session = SessionFactory::createForTesting();

// Test session functionality
$session->set('test_key', 'test_value');
$this->assertEquals('test_value', $session->get('test_key'));
```

## 🎯 **Integration with Slim 4**

### **DI Container Setup**

```php
use ResponsiveSk\Slim4Session\SessionFactory;
use ResponsiveSk\Slim4Session\SessionInterface;

return [
    SessionInterface::class => function () {
        return SessionFactory::createForProduction();
    },
];
```

### **Middleware Usage**

```php
use ResponsiveSk\Slim4Session\SessionInterface;

class SessionMiddleware
{
    public function __construct(
        private readonly SessionInterface $session
    ) {}
    
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
        
        return $handler->handle($request);
    }
}
```

## 📊 **Type Safety**

Full PHPStan level max compatibility:

```php
/** @var SessionInterface $session */
$session = SessionFactory::create();

// Type-safe operations
$userId = $session->get('user_id', 0);        // mixed
$username = $session->get('username', '');     // mixed
$data = $session->all();                       // array<string, mixed>
$hasKey = $session->has('key');                // bool
```

## 🤝 **Compatibility**

- ✅ **PHP 8.3+**
- ✅ **Slim 4**
- ✅ **Zero external dependencies**
- ✅ **PHPStan level max**

## 📄 **License**

MIT License. See [LICENSE](LICENSE) for details.

---

**Made with ❤️ by [Responsive SK](https://responsive.sk)**
