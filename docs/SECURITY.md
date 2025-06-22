# Security Guide

This document outlines the security features and best practices implemented in the HDM Boot Application.

## 🔒 Security Overview

The HDM Boot Application implements a **security-first** approach with multiple layers of protection against common web vulnerabilities.

### Security Principles

1. **Defense in Depth** - Multiple security layers
2. **Least Privilege** - Minimal access rights
3. **Fail Secure** - Secure defaults and error handling
4. **Input Validation** - All inputs validated and sanitized
5. **Output Encoding** - XSS prevention
6. **Secure by Default** - Security built into the foundation

## 🛡 Implemented Security Features

### 1. Path Traversal Protection

**Package**: `responsive-sk/slim4-paths`

#### Features
- **Path Validation** - All file paths validated before access
- **Directory Whitelisting** - Only allowed directories accessible
- **Traversal Detection** - Blocks `../` and similar patterns
- **Encoding Protection** - Handles URL-encoded traversal attempts

#### Implementation

```php
// Example of secure path handling
use ResponsiveSk\Slim4Paths\Paths;

$paths = new Paths('/base/path', [
    'allowed' => ['var/logs', 'var/cache', 'uploads'],
    'forbidden' => ['config', '.env', '*.php']
]);

// Automatically validates and secures paths
$securePath = $paths->getSecurePath('uploads/image.jpg');
```

#### Configuration

```php
// config/paths.php
'security' => [
    'allowed_directories' => [
        'var', 'logs', 'cache', 'uploads', 'storage', 'sessions'
    ],
    'forbidden_paths' => [
        '.env', 'config', 'src', 'bootstrap', 'modules', 'vendor'
    ],
]
```

### 2. Authentication System

#### JWT Authentication
- Secure token generation and validation
- Configurable expiration times
- Protection against token tampering
- Refresh token rotation

```php
// JWT Configuration
'security' => [
    'jwt_secret' => $_ENV['JWT_SECRET'],
    'jwt_expiry' => 3600,  // 1 hour
    'refresh_token_expiry' => 604800,  // 1 week
    'token_algorithms' => ['HS256']
],
```

#### Session Security
- Secure session handling
- CSRF protection
- Session fixation prevention
- Automatic cleanup of expired sessions

```php
// Session Configuration
'session' => [
    'name' => 'MVA_SESSION',
    'lifetime' => 7200,
    'path' => '/',
    'domain' => null,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
],
```

### 3. Password Security

#### Password Hashing
- Argon2id hashing algorithm
- Automatic password rehashing
- Configurable memory and time cost

```php
// Password Configuration
'password' => [
    'algorithm' => PASSWORD_ARGON2ID,
    'options' => [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ],
    'min_length' => 12
],
```

### 4. Rate Limiting

Protection against brute force attacks and DoS attempts:

```php
// Rate Limit Configuration
'rate_limit' => [
    'enabled' => true,
    'storage' => 'redis',
    'window' => 300,  // 5 minutes
    'max_requests' => [
        'api' => 100,
        'login' => 5,
        'register' => 3
    ]
],
```

### 5. Security Headers

Automatically configured security headers:

```php
return function (ResponseInterface $response): ResponseInterface {
    return $response
        ->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('X-XSS-Protection', '1; mode=block')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->withHeader('Content-Security-Policy', $cspPolicy)
        ->withHeader('Permissions-Policy', $permissionsPolicy);
};
```

## 🔍 Security Best Practices

### 1. Input Validation

All user input is validated:
```php
// Validation rules
$rules = [
    'email' => ['required', 'email'],
    'password' => ['required', 'min:12', 'complexity'],
    'name' => ['required', 'string', 'max:255'],
];

// Custom validation
$validator = new Validator($data, $rules);
if (!$validator->validate()) {
    throw new ValidationException($validator->getErrors());
}
```

### 2. SQL Injection Prevention

Using prepared statements and query builders:
```php
// Safe database queries
$query = $this->queryFactory
    ->newSelect('users')
    ->where(['email' => $email])
    ->andWhere(['status' => 'active']);

$user = $query->execute()->fetch('assoc');
```

### 3. XSS Prevention

Automatic output encoding:
```php
// In templates
<?= $this->e($userInput) ?>

// In JSON responses
$data = $this->sanitizer->sanitizeArray($rawData);
```

### 4. CSRF Protection

Form and API protection:
```php
// Generate CSRF token
<input type="hidden" name="csrf" value="<?= $csrf->generate() ?>">

// Verify token
$csrf->verify($_POST['csrf']);
```

## 🚨 Security Monitoring

### 1. Security Logging

```php
// Log security events
$securityLogger->warning('Failed login attempt', [
    'email' => $email,
    'ip' => $request->getClientIp(),
    'user_agent' => $request->getHeaderLine('User-Agent')
]);
```

### 2. Audit Trail

```php
// Log sensitive operations
$auditLogger->info('User permission changed', [
    'user_id' => $userId,
    'changed_by' => $adminId,
    'old_permissions' => $oldPerms,
    'new_permissions' => $newPerms
]);
```

## 🔒 Security Configuration Guide

### 1. Production Settings

```env
# .env.production
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=your-secure-secret
SESSION_SECURE=true
COOKIE_SECURE=true
```

### 2. Security Checklist

- [ ] Strong JWT secret configured
- [ ] HTTPS enforced
- [ ] Secure session settings
- [ ] Rate limiting enabled
- [ ] Security headers configured
- [ ] Logging enabled
- [ ] Audit trail configured
- [ ] Input validation implemented
- [ ] XSS protection active
- [ ] CSRF protection enabled
- [ ] SQL injection protection
- [ ] Password policy enforced

## 🛠 Security Testing

### 1. Built-in Security Tests

```bash
# Run security tests
composer test:security

# Test specific features
composer test:security:jwt
composer test:security:xss
composer test:security:csrf
```

### 2. Penetration Testing

Regular security testing procedures:
1. Automated vulnerability scanning
2. Manual penetration testing
3. Code security reviews
4. Dependency vulnerability checks

## 📋 Security Response Plan

### 1. Vulnerability Reporting

Report security issues to:
- Email: security@example.com
- Bug Bounty Program: https://example.com/security

### 2. Security Updates

- Regular security patches
- Automatic dependency updates
- Security advisory notifications
- Hot-fix deployment procedures

## 🔄 Regular Security Tasks

### Daily
- Monitor security logs
- Check failed login attempts
- Review system alerts

### Weekly
- Update dependencies
- Review access logs
- Check security headers

### Monthly
- Full security audit
- Password policy review
- Access control review

## 📚 Additional Resources

1. [OWASP Security Guidelines](https://owasp.org/www-project-web-security-testing-guide/)
2. [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
3. [JWT Security](https://auth0.com/blog/a-look-at-the-latest-draft-for-jwt-bcp/)
4. [Argon2 Password Hashing](https://wiki.php.net/rfc/argon2_password_hash)

## Conclusion

The HDM Boot Application implements comprehensive security measures following industry best practices. Regular updates and security reviews ensure the application remains secure against evolving threats.
