# Security Hardening Guide

## 🔒 Bezpečnostné Vrstvy

### 1. Server Hardening
```bash
# PHP Configuration
expose_php = Off
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
allow_url_fopen = Off
allow_url_include = Off
max_input_time = 30
memory_limit = 256M
post_max_size = 20M
upload_max_filesize = 10M
```

### 2. Web Server Security
```nginx
# Nginx security configuration
server {
    # SSL configuration
    listen 443 ssl http2;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    
    # Security headers
    add_header X-Frame-Options "DENY";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';";
    
    # Disable directory listing
    autoindex off;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=one:10m rate=1r/s;
    limit_req zone=one burst=10 nodelay;
}
```

## 🛡️ Aplikačná Bezpečnosť

### 1. Autentifikácia
```php
// config/security.php
return [
    'auth' => [
        'password_min_length' => 12,
        'password_requires_special' => true,
        'password_requires_number' => true,
        'password_requires_mixed_case' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minút
    ],
    'session' => [
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
        'lifetime' => 7200,
    ],
    'jwt' => [
        'algorithm' => 'HS256',
        'secret_rotation_interval' => '30 days',
        'blacklist_enabled' => true,
    ]
];
```

### 2. Rate Limiting
```php
// Konfigurácia rate limitingu
'rate_limits' => [
    'api' => [
        'window' => 300,     // 5 minút
        'max_requests' => 100
    ],
    'login' => [
        'window' => 300,     // 5 minút
        'max_attempts' => 5
    ],
    'register' => [
        'window' => 3600,    // 1 hodina
        'max_attempts' => 3
    ]
]
```

## 🔍 Security Monitoring

### 1. Audit Logging
```php
// Security event logging
$auditLogger->info('Password changed', [
    'user_id' => $userId,
    'ip_address' => $request->getClientIp(),
    'user_agent' => $request->getHeaderLine('User-Agent'),
    'timestamp' => time(),
    'event_type' => 'security.password_change'
]);
```

### 2. Intrusion Detection
```php
// Detekcia podozrivej aktivity
$detector->addRule('multiple_failed_logins', function($events) {
    return $events->filter('failed_login')
        ->last('5 minutes')
        ->count() >= 5;
});

$detector->addRule('unusual_location', function($request, $user) {
    return !$this->isKnownLocation($request->getClientIp(), $user);
});
```

## 🚫 Input Validation

### 1. Request Validation
```php
// Validácia všetkých vstupov
$validator->addRule('email', function($value) {
    return filter_var($value, FILTER_VALIDATE_EMAIL) 
        && strlen($value) <= 255;
});

$validator->addRule('username', function($value) {
    return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $value);
});
```

### 2. File Upload Security
```php
// Bezpečné nahrávanie súborov
$uploadConfig = [
    'allowed_types' => ['jpg', 'png', 'pdf'],
    'max_size' => 5 * 1024 * 1024,  // 5MB
    'sanitize_filename' => true,
    'check_mime_type' => true,
];

$uploader->setConfig($uploadConfig);
```

## 🔐 Data Protection

### 1. Encryption Configuration
```php
// Encryption settings
'encryption' => [
    'algo' => 'aes-256-gcm',
    'key_rotation' => true,
    'key_rotation_interval' => '90 days',
    'at_rest' => [
        'enabled' => true,
        'algorithm' => 'AES-256-CBC',
    ]
]
```

### 2. Sensitive Data Handling
```php
// PII Data masking
$dataMasker->addRule('email', function($value) {
    return preg_replace('/(?<=.{3}).(?=.*@)/u', '*', $value);
});

$dataMasker->addRule('phone', function($value) {
    return substr($value, 0, -4) . '****';
});
```

## 🌐 API Security

### 1. API Authentication
```php
// API Security configuration
'api_security' => [
    'token_lifetime' => 3600,
    'refresh_token_lifetime' => 604800,
    'token_refresh_ttl' => 7200,
    'rate_limit_enabled' => true,
    'require_https' => true
]
```

### 2. CORS Configuration
```php
// CORS settings
'cors' => [
    'allowed_origins' => ['https://app.example.com'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => ['X-RateLimit-Limit'],
    'max_age' => 3600,
    'supports_credentials' => true
]
```

## ⚡ Security Best Practices

### 1. Password Security
```php
// Password policies
'password_policy' => [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_number' => true,
    'require_special' => true,
    'prevent_common' => true,
    'history_size' => 5
]
```

### 2. Session Security
```php
// Session hardening
'session' => [
    'name' => 'MVASESSID',
    'lifetime' => 7200,
    'path' => '/',
    'domain' => null,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]
```

## 🔄 Security Update Process

### 1. Dependency Updates
```bash
# Pravidelná kontrola závislostí
composer audit
composer outdated

# Automatické security aktualizácie
composer update --dry-run
```

### 2. Security Patching
```bash
# Aplikácia security patchov
php bin/console security:check
php bin/console security:patch

# Reštart služieb
sudo systemctl restart php-fpm
sudo systemctl restart nginx
```

## 📝 Security Checklist

### Pre-deployment
- [ ] Security headers nakonfigurované
- [ ] SSL/TLS správne nastavený
- [ ] Rate limiting aktívny
- [ ] Input validation implementovaná
- [ ] Password policies nastavené
- [ ] Session security nastavená
- [ ] CORS nakonfigurované
- [ ] Logging aktívny
- [ ] Monitoring pripravený
- [ ] Dependency audit vykonaný

### Post-deployment
- [ ] SSL certificate valid
- [ ] Security headers prítomné
- [ ] Rate limiting funguje
- [ ] Monitoring aktívny
- [ ] Logy sa generujú
- [ ] Backup systém funguje
- [ ] Emergency kontakty aktuálne
