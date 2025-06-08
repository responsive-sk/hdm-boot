# Security Guide

This document outlines the security features and best practices implemented in the MVA Bootstrap Application.

## 🔒 Security Overview

The MVA Bootstrap Application implements a **security-first** approach with multiple layers of protection against common web vulnerabilities.

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
// SecurePathHelper usage
$pathHelper = $container->get(SecurePathHelper::class);

// Safe file access
$safePath = $pathHelper->securePath('user-file.txt', 'uploads');
$content = $pathHelper->readFile('log.txt', 'logs');

// Automatic protection against:
// - ../../../etc/passwd
// - ..%2F..%2F..%2Fetc%2Fpasswd
// - config/container.php (forbidden path)
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

### 2. File Upload Security

#### Restrictions
- **File Size Limit** - Maximum 5MB per file
- **Extension Whitelist** - Only safe file types allowed
- **Extension Blacklist** - Dangerous file types blocked
- **Filename Sanitization** - Safe filename generation

#### Allowed Extensions
```
Images: jpg, jpeg, png, gif, webp
Documents: pdf, doc, docx, txt, md
Data: zip, csv, json, xml
```

#### Forbidden Extensions
```
Executables: php, phtml, exe, bat, cmd, com, scr
Scripts: js, html, htm, asp, aspx
```

### 3. Environment Security

#### Environment Isolation
- **Sensitive Data** - Stored in `.env` file
- **Production Safety** - Debug features disabled in production
- **Secret Management** - JWT secrets and API keys protected

#### Environment Variables
```bash
# Security-related environment variables
APP_ENV=prod                    # Environment (dev/prod)
APP_DEBUG=false                 # Debug mode (never true in prod)
JWT_SECRET=your-secret-key      # JWT signing secret
DATABASE_URL=sqlite:var/storage/app.db  # Database connection
```

### 4. Database Security

#### Connection Security
- **Prepared Statements** - SQL injection prevention
- **Parameter Binding** - Safe data insertion
- **Connection Options** - Secure PDO configuration

```php
// Secure PDO configuration
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // Real prepared statements
];
```

#### File Location
- **Protected Directory** - Database files in `var/storage/`
- **Access Control** - Not accessible via web
- **Backup Security** - Secure backup procedures

## 🔐 Planned Security Features

### 1. Authentication System

#### JWT Implementation
- **Stateless Tokens** - No server-side session storage
- **Secure Signing** - HMAC-SHA256 or RS256 algorithms
- **Token Expiration** - Configurable expiry times
- **Refresh Tokens** - Secure token renewal

```php
// Planned JWT usage
$token = $jwtService->generateToken($user);
$payload = $jwtService->validateToken($token);
```

### 2. Authorization System

#### Role-Based Access Control (RBAC)
- **User Roles** - Admin, User, Guest
- **Permissions** - Granular permission system
- **Resource Protection** - Route and method-level protection

```php
// Planned authorization
$authService->requireRole('admin');
$authService->requirePermission('article.create');
```

### 3. Input Validation

#### Validation Rules
- **Data Types** - Strong type validation
- **Length Limits** - String and array length limits
- **Format Validation** - Email, URL, date formats
- **Sanitization** - HTML and SQL injection prevention

### 4. CSRF Protection

#### Token-Based Protection
- **CSRF Tokens** - Unique tokens per form
- **Token Validation** - Server-side token verification
- **SameSite Cookies** - Additional CSRF protection

### 5. Session Security

#### Secure Session Configuration
- **Secure Cookies** - HTTPS-only cookies
- **HttpOnly Flags** - JavaScript access prevention
- **Session Regeneration** - ID regeneration on login
- **Session Timeout** - Automatic session expiry

## 🧪 Security Testing

### Automated Tests

#### Path Security Tests
```bash
# Test path traversal protection
curl http://localhost:8001/test/paths

# Expected results:
# ✅ Valid path access
# ✅ Path traversal blocked
# ✅ Forbidden path blocked
```

#### Security Test Suite
```php
// tests/Security/PathTraversalTest.php
public function testPathTraversalPrevention()
{
    $this->expectException(InvalidArgumentException::class);
    $this->pathHelper->securePath('../../../etc/passwd', 'var');
}

public function testForbiddenPathAccess()
{
    $this->expectException(InvalidArgumentException::class);
    $this->pathHelper->securePath('config/container.php', 'public');
}
```

### Manual Security Testing

#### Path Traversal Testing
```bash
# Test various traversal patterns
curl "http://localhost:8001/api/file?path=../../../etc/passwd"
curl "http://localhost:8001/api/file?path=..%2F..%2F..%2Fetc%2Fpasswd"
curl "http://localhost:8001/api/file?path=....//....//....//etc/passwd"
```

#### File Upload Testing
```bash
# Test malicious file uploads
curl -X POST -F "file=@malicious.php" http://localhost:8001/api/upload
curl -X POST -F "file=@script.js" http://localhost:8001/api/upload
```

## 🚨 Security Incident Response

### Monitoring

#### Log Analysis
- **Security Events** - Failed authentication attempts
- **Path Violations** - Blocked path traversal attempts
- **Upload Violations** - Rejected file uploads
- **Error Patterns** - Suspicious error patterns

#### Alert Triggers
- **Multiple Failed Logins** - Potential brute force
- **Path Traversal Attempts** - Security scanning
- **Unusual File Access** - Potential breach
- **Error Rate Spikes** - Potential attack

### Response Procedures

1. **Detection** - Automated monitoring and alerts
2. **Assessment** - Evaluate threat severity
3. **Containment** - Block malicious requests
4. **Investigation** - Analyze attack vectors
5. **Recovery** - Restore normal operations
6. **Lessons Learned** - Improve security measures

## 🔧 Security Configuration

### Production Security Checklist

#### Environment
- [ ] `APP_ENV=prod`
- [ ] `APP_DEBUG=false`
- [ ] Strong JWT secret (32+ characters)
- [ ] Secure database credentials
- [ ] HTTPS enabled
- [ ] Security headers configured

#### File System
- [ ] Proper file permissions (755 for directories, 644 for files)
- [ ] Web server configuration blocks access to sensitive files
- [ ] Upload directory outside web root
- [ ] Log files protected

#### Database
- [ ] Database files in protected directory
- [ ] Regular backups with encryption
- [ ] Connection encryption (if remote database)
- [ ] Minimal database user privileges

### Security Headers

```apache
# .htaccess (Apache)
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Content-Security-Policy "default-src 'self'"
```

```nginx
# nginx.conf
add_header X-Content-Type-Options nosniff;
add_header X-Frame-Options DENY;
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
add_header Content-Security-Policy "default-src 'self'";
```

## 📚 Security Resources

### Documentation
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://phpsecurity.readthedocs.io/)
- [Slim Framework Security](https://www.slimframework.com/docs/v4/concepts/security.html)

### Tools
- **Static Analysis** - PHPStan for code analysis
- **Dependency Scanning** - Composer audit for vulnerabilities
- **Security Testing** - Custom security test suite

### Best Practices
1. **Regular Updates** - Keep dependencies updated
2. **Security Reviews** - Regular code security reviews
3. **Penetration Testing** - Professional security testing
4. **Security Training** - Team security awareness
5. **Incident Planning** - Prepared response procedures
