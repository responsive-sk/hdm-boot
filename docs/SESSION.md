# 🍪 Session Management System

**Enterprise-grade session handling for HDM Boot Application**

Based on [samuelgfeller's session pattern](https://samuel-gfeller.ch/docs/Session-and-Flash-messages) with enterprise enhancements.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Usage](#usage)
- [Flash Messages](#flash-messages)
- [Security](#security)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

The Session Management system provides comprehensive session handling with:

- **Automatic session start** via SessionStartMiddleware
- **Enterprise configuration** via environment variables
- **Flash message support** for user notifications
- **Security features** with CSRF protection
- **Cookie management** with proper security settings
- **Session persistence** across requests

## ✨ Features

### 🔧 Core Features

- ✅ **SessionStartMiddleware** - Automatic session initialization
- ✅ **Environment Configuration** - Flexible deployment options
- ✅ **Flash Messages** - User notification system
- ✅ **CSRF Protection** - Cross-site request forgery prevention
- ✅ **Cookie Security** - HttpOnly, Secure, SameSite settings
- ✅ **Session Persistence** - Reliable data storage across requests
- ✅ **Enterprise Logging** - Comprehensive session operation tracking
- ✅ **Memory Session** - Testing support

### 🛡️ Security Features

- **CSRF Token Validation** - Prevents cross-site attacks
- **Session Regeneration** - ID regeneration on login/logout
- **Secure Cookies** - HttpOnly and Secure flags
- **SameSite Protection** - CSRF mitigation
- **Session Timeout** - Configurable lifetime
- **IP Validation** - Optional IP-based security

## 🏗️ Architecture

### Core Components

```
Session System
├── SessionStartMiddleware     # Automatic session initialization
├── SessionInterface          # Session data management
├── SessionManagerInterface   # Session lifecycle management
├── CsrfService              # CSRF token handling
├── FlashInterface           # Flash message system
└── Configuration            # Environment-driven config
```

### Session Flow

1. **SessionStartMiddleware** - Starts session automatically
2. **Session Data** - Stores user data, CSRF tokens, flash messages
3. **CSRF Validation** - Validates tokens on form submissions
4. **Flash Messages** - Displays temporary user notifications
5. **Session Cleanup** - Destroys session on logout

## ⚙️ Configuration

### Environment Variables

Add to your `.env` file:

```bash
# Session Configuration
SESSION_NAME="hdm_boot_session"
SESSION_LIFETIME=7200
SESSION_COOKIE_SECURE=false
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax

# CSRF Configuration
CSRF_TOKEN_NAME="csrf_token"
CSRF_TOKEN_LIFETIME=3600

# Flash Message Configuration
FLASH_MESSAGE_ENABLED=true
FLASH_MESSAGE_CATEGORIES="success,error,warning,info"

# Session Security
SESSION_REGENERATE_ON_LOGIN=true
SESSION_VALIDATE_IP=false
SESSION_STRICT_MODE=true
```

### Session Options

The system uses these session configuration options:

```php
$sessionOptions = [
    'name' => $_ENV['SESSION_NAME'] ?? 'hdm_boot_session',
    'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 hours
    'cookie_secure' => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
    'cookie_httponly' => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
    'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
];
```

### Middleware Configuration

SessionStartMiddleware is registered in the middleware stack:

```php
// boot/App.php
$this->slimApp->add(\Odan\Session\Middleware\SessionStartMiddleware::class);
$this->slimApp->addRoutingMiddleware();
```

## 🚀 Usage

### Basic Session Operations

```php
use Odan\Session\SessionInterface;

class ExampleAction
{
    public function __construct(
        private readonly SessionInterface $session
    ) {}

    public function __invoke($request, $response): ResponseInterface
    {
        // Store data
        $this->session->set('user_id', '123');
        $this->session->set('user_data', [
            'email' => 'user@example.com',
            'role' => 'admin'
        ]);

        // Get data
        $userId = $this->session->get('user_id');
        $userData = $this->session->get('user_data', []);

        // Check if exists
        if ($this->session->has('user_id')) {
            // User is logged in
        }

        // Delete data
        $this->session->delete('temp_data');

        // Clear all data
        $this->session->clear();

        return $response;
    }
}
```

### Session Manager Operations

```php
use Odan\Session\SessionManagerInterface;

class AuthAction
{
    public function __construct(
        private readonly SessionManagerInterface $sessionManager
    ) {}

    public function login($request, $response): ResponseInterface
    {
        // Regenerate session ID for security
        $this->sessionManager->regenerateId();

        // Store user data
        $this->session->set('user_id', $userId);

        return $response;
    }

    public function logout($request, $response): ResponseInterface
    {
        // Destroy session
        $this->sessionManager->destroy();

        // Start new session for flash messages
        $this->sessionManager->start();

        return $response;
    }
}
```

## 💬 Flash Messages

### Adding Flash Messages

```php
// Success message
$this->session->getFlash()->add('success', 'Login successful!');

// Error message
$this->session->getFlash()->add('error', 'Invalid credentials.');

// Warning message
$this->session->getFlash()->add('warning', 'Session will expire soon.');

// Info message
$this->session->getFlash()->add('info', 'New features available.');
```

### Displaying Flash Messages

Flash messages are automatically available in templates:

```php
// In template (e.g., layout.php)
<?php
$flash = $this->session->getFlash();
foreach ($flash->all() as $category => $messages) {
    foreach ($messages as $message) {
        echo "<div class='alert alert-{$category}'>{$message}</div>";
    }
}
?>
```

### Flash Message Categories

- **success** - Green alerts for successful operations
- **error** - Red alerts for errors and failures
- **warning** - Yellow alerts for warnings
- **info** - Blue alerts for informational messages

## 🛡️ Security

### CSRF Protection

```php
use HdmBoot\Modules\Core\Security\Services\CsrfService;

class FormAction
{
    public function __construct(
        private readonly CsrfService $csrfService
    ) {}

    public function showForm($request, $response): ResponseInterface
    {
        // Generate CSRF token for form
        $csrfToken = $this->csrfService->generateToken('contact');

        return $this->templateRenderer->render($response, 'contact.php', [
            'csrf_token' => $csrfToken
        ]);
    }

    public function submitForm($request, $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        // Validate CSRF token
        $this->csrfService->validateFromRequest($data, 'contact');

        // Process form...
        return $response;
    }
}
```

### CSRF Token in Templates

```html
<!-- In form template -->
<form method="POST" action="/contact">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <!-- Other form fields -->
    <button type="submit">Submit</button>
</form>
```

### Session Security Best Practices

1. **Regenerate ID on Login**
   ```php
   $this->sessionManager->regenerateId();
   ```

2. **Destroy Session on Logout**
   ```php
   $this->sessionManager->destroy();
   ```

3. **Use Secure Cookies in Production**
   ```bash
   SESSION_COOKIE_SECURE=true  # HTTPS only
   ```

4. **Enable HttpOnly Cookies**
   ```bash
   SESSION_COOKIE_HTTPONLY=true  # Prevent XSS
   ```

5. **Set SameSite Protection**
   ```bash
   SESSION_COOKIE_SAMESITE=Lax  # CSRF protection
   ```

## 🧪 Development

### Testing with Memory Session

For testing, use MemorySession instead of PhpSession:

```php
// In test setup
use Odan\Session\MemorySession;

protected function setUp(): void
{
    // Replace session with memory session
    $this->container->set(SessionInterface::class, new MemorySession());
}

public function testSessionData(): void
{
    $session = $this->container->get(SessionInterface::class);
    
    // Test session operations
    $session->set('test_key', 'test_value');
    $this->assertEquals('test_value', $session->get('test_key'));
}
```

### Debug Session Data

```php
// Debug current session data
$allData = $this->session->all();
error_log('Session data: ' . json_encode($allData));

// Check session status
$isStarted = $this->session->isStarted();
error_log('Session started: ' . ($isStarted ? 'YES' : 'NO'));
```

### Session Logging

Enable session debugging in development:

```bash
# In .env
SESSION_DEBUG=true
LOG_SESSION_OPERATIONS=true
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Session Not Starting

**Cause:** SessionStartMiddleware not registered or wrong order

**Solution:**
```php
// Ensure SessionStartMiddleware is registered before routing
$app->add(\Odan\Session\Middleware\SessionStartMiddleware::class);
$app->addRoutingMiddleware();
```

#### 2. CSRF Token Validation Fails

**Cause:** Session not persisting between requests

**Debug:**
```php
// Check if session is started
if (!$this->session->isStarted()) {
    error_log('Session not started during CSRF validation');
}

// Check stored tokens
$tokens = $this->session->get('csrf_tokens', []);
error_log('Stored CSRF tokens: ' . json_encode($tokens));
```

**Solution:**
- Verify session cookies are being sent
- Check session configuration
- Ensure SessionStartMiddleware is active

#### 3. Flash Messages Not Displaying

**Cause:** Flash messages consumed before template rendering

**Solution:**
```php
// Get flash messages without consuming them
$flash = $this->session->getFlash();
$messages = $flash->all(); // This consumes messages

// Or check if messages exist first
if ($flash->has('success')) {
    $successMessages = $flash->get('success');
}
```

#### 4. Flash Messages Persisting After Logout/Login

**Problem:** After logout and login, old logout message still appears alongside login message.

**Root Cause:**
- Logout action sets flash message AFTER `session->destroy()`
- Flash message gets stored in new session
- Login `clearAll()` operates on different session than logout message
- TemplateRenderer consumes flash messages on every render

**Incorrect Implementation:**
```php
// ❌ WRONG - LogoutAction
$this->session->destroy();
$this->session->start();
$this->session->flash('success', 'Logged out successfully'); // New session!

// ❌ WRONG - LoginSubmitAction
$this->session->getFlash()->clearAll(); // Different session!
$this->session->flash('success', 'Login successful');
```

**Correct Implementation:**
```php
// ✅ CORRECT - LogoutAction
$this->session->flash('success', 'You have been logged out successfully');
$this->session->destroy(); // Flash message persists to next session

// ✅ CORRECT - LoginSubmitAction
$this->session->getFlash()->clearAll(); // Clears logout message
$this->session->flash('success', 'Login successful! Welcome back.');
```

**Key Points:**
- Set flash messages **BEFORE** `session->destroy()`
- Flash messages automatically persist across session destroy/recreate
- Use `clearAll()` in login to ensure clean slate
- Proper session lifecycle: flash → destroy → clearAll → new flash

#### 4. Session Data Lost

**Cause:** Session cookies not configured properly

**Check:**
```bash
# Verify session configuration
SESSION_NAME="hdm_boot_session"
SESSION_LIFETIME=7200
SESSION_COOKIE_HTTPONLY=true
```

**Debug:**
```php
// Check session ID
$sessionId = session_id();
error_log('Session ID: ' . $sessionId);

// Check session save path
$savePath = session_save_path();
error_log('Session save path: ' . $savePath);
```

#### 5. Login Page Shows Even When Logged In

**Cause:** Missing `last_activity` session key in login process

**Symptoms:**
- User can login successfully
- Profile page is accessible
- But `/login` page still shows login form instead of redirecting to profile
- SessionService.isLoggedIn() returns false due to session expiration

**Debug:**
```php
// Check session data after login
$userId = $this->session->get('user_id');
$lastActivity = $this->session->get('last_activity');
error_log("Session check: user_id=$userId, last_activity=$lastActivity");
```

**Root Cause:**
LoginSubmitAction sets `user_id` but forgets to set `last_activity`, causing SessionService.isSessionExpired() to return true.

**Solution:**
```php
// In LoginSubmitAction, ensure all required session keys are set
$currentTime = time();
$this->session->set('user_id', $user->getId()->toString());
$this->session->set('login_time', $currentTime);
$this->session->set('last_activity', $currentTime);  // This was missing!
$this->session->set('user_data', [
    'email' => $user->getEmail(),
    'name' => $user->getName(),
    'role' => $user->getRole(),
    'status' => $user->getStatus(),
]);
```

**Prevention:**
- Always use SessionService.loginUser() method instead of setting session data manually
- Ensure consistent session key naming across all components
- Add debug logging to track session state changes

#### 6. Multiple Session Cookies in Browser

**Cause:** Multiple session handlers or incorrect cookie configuration

**Debug:**
```bash
# Check browser Developer Tools > Application > Cookies
# Should see only one session cookie (e.g., "boot_session")
# Plus any language/preference cookies (e.g., "app_language")
```

**Common Mistakes:**
- Having both file sessions and database sessions active
- Different session names in different parts of application
- Optional modules creating their own sessions

**Solution:**
- Use single SessionInterface registration in DI container
- Ensure consistent session configuration across all modules
- Verify odan/session options are correct for your environment

### Debug Information

```php
// Get comprehensive session info
$sessionInfo = [
    'started' => $this->session->isStarted(),
    'id' => $this->session->getId(),
    'name' => session_name(),
    'save_path' => session_save_path(),
    'cookie_params' => session_get_cookie_params(),
    'data' => $this->session->all(),
];

error_log('Session debug: ' . json_encode($sessionInfo, JSON_PRETTY_PRINT));
```

### Session State Debugging

```php
// Debug session state in middleware/actions
error_log(sprintf(
    'Session state: id=%s, user_id=%s, last_activity=%s, started=%s',
    $this->session->getId(),
    $this->session->get('user_id', 'NULL'),
    $this->session->get('last_activity', 'NULL'),
    $this->session->isStarted() ? 'YES' : 'NO'
));
```

### Login Process Debugging

```php
// Debug login process step by step
public function loginUser(User $user): void
{
    $currentTime = time();

    error_log('SessionService.loginUser() START: session_id=' . $this->session->getId());

    // Regenerate session ID for security
    $this->session->regenerateId();

    error_log('SessionService.loginUser() AFTER regenerateId: session_id=' . $this->session->getId());

    $this->session->set('user_id', $user->getId()->toString());
    $this->session->set('login_time', $currentTime);
    $this->session->set('last_activity', $currentTime);

    // Verify data was set
    $verifyUserId = $this->session->get('user_id');
    $verifyLastActivity = $this->session->get('last_activity');

    error_log(sprintf(
        'SessionService.loginUser() END: user_id=%s, last_activity=%s, current_time=%s',
        $verifyUserId,
        $verifyLastActivity,
        $currentTime
    ));
}
```

### Session Expiration Debugging

```php
// Debug session expiration logic
private function isSessionExpired(): bool
{
    $lastActivity = $this->session->get('last_activity', 0);
    $currentTime = time();
    $timeDiff = $currentTime - (int)$lastActivity;
    $isExpired = $timeDiff > self::SESSION_TIMEOUT;

    error_log(sprintf(
        'Session expiration check: last_activity=%s, current_time=%s, time_diff=%s, timeout=%s, expired=%s',
        $lastActivity,
        $currentTime,
        $timeDiff,
        self::SESSION_TIMEOUT,
        $isExpired ? 'YES' : 'NO'
    ));

    return $isExpired;
}
```

## 🎯 Best Practices

### Session Key Consistency

Always use consistent session keys across your application:

```php
// Define session keys as constants
class SessionKeys
{
    public const USER_ID = 'user_id';
    public const LOGIN_TIME = 'login_time';
    public const LAST_ACTIVITY = 'last_activity';
    public const USER_DATA = 'user_data';
}

// Use constants instead of magic strings
$this->session->set(SessionKeys::USER_ID, $userId);
$this->session->set(SessionKeys::LAST_ACTIVITY, time());
```

### Centralized Session Management

Use dedicated service for session operations:

```php
// Good: Use SessionService for all session operations
$this->sessionService->loginUser($user);
$this->sessionService->logoutUser();
$isLoggedIn = $this->sessionService->isLoggedIn();

// Bad: Direct session manipulation in actions
$this->session->set('user_id', $userId);  // Missing last_activity!
```

### Session Security Checklist

- ✅ Use SessionStartMiddleware for automatic session initialization
- ✅ Regenerate session ID on login/logout
- ✅ Set HttpOnly and Secure cookie flags
- ✅ Implement session timeout with last_activity tracking
- ✅ Clear session data on logout
- ✅ Use CSRF protection for forms
- ✅ Validate session data integrity

### Production Configuration

```bash
# Production .env settings
SESSION_NAME="your_app_session"
SESSION_LIFETIME=7200
SESSION_COOKIE_SECURE=true      # HTTPS only
SESSION_COOKIE_HTTPONLY=true    # Prevent XSS
SESSION_COOKIE_SAMESITE=Strict  # CSRF protection
```

### Monitoring and Logging

```php
// Log important session events
$this->logger->info('User login successful', [
    'user_id' => $userId,
    'session_id' => $this->session->getId(),
    'ip' => $clientIp,
    'user_agent' => $userAgent,
]);

$this->logger->warning('Session expired', [
    'user_id' => $userId,
    'last_activity' => $lastActivity,
    'session_age' => time() - $lastActivity,
]);
```

## 📚 References

- [samuelgfeller Session Documentation](https://samuel-gfeller.ch/docs/Session-and-Flash-messages)
- [odan/session Library](https://github.com/odan/session)
- [PHP Session Documentation](https://www.php.net/manual/en/book.session.php)
- [OWASP Session Management](https://owasp.org/www-community/controls/Session_Management_Cheat_Sheet)

---

**🍪 Enterprise session management for secure PHP applications** ✨
