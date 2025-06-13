# Production Checklist

## 🔍 Pred Nasadením

### 1. Security Checklist
- [ ] Security audit dokončený
- [ ] Všetky security hlavičky nakonfigurované
- [ ] SSL/TLS certifikáty pripravené
- [ ] JWT secret nastavený a bezpečne uložený
- [ ] Rate limiting aktívny
- [ ] CORS správne nakonfigurovaný
- [ ] Error reporting vypnutý
- [ ] Debug mode vypnutý

### 2. Performance
- [ ] Composer optimalizácie
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] OPcache nakonfigurovaný
- [ ] Redis/cache systém pripravený
- [ ] Database indexy skontrolované
- [ ] Query monitoring aktívny

### 3. Monitoring
- [ ] Logging nakonfigurovaný
- [ ] Health check endpointy aktívne
- [ ] Monitoring systém pripravený
- [ ] Alert systém nastavený

### 4. Konfigurácia
- [ ] .env súbor pre produkciu
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://your-domain.com
  
  DB_HOST=production-db
  REDIS_HOST=production-redis
  
  JWT_SECRET=secure-secret
  ```
- [ ] Cache vyčistená
- [ ] Permissions na súboroch správne nastavené
- [ ] Session konfigurácia zabezpečená

## 🚀 Deployment Process

### 1. Príprava
```bash
# Backup
mysqldump -u user -p database > backup.sql

# Composer
composer install --no-dev --optimize-autoloader

# Cache
php bin/console cache:clear
```

### 2. Deployment
```bash
# Maintenance mode
php bin/console down

# Update code
git pull origin main

# Migrations
php bin/console migrate

# Cache & optimizations
php bin/console optimize
php bin/console route:cache
php bin/console config:cache

# Back online
php bin/console up
```

### 3. Verifikácia
- [ ] Health check endpoint (`/_status`)
- [ ] Základné API endpointy
- [ ] Autentifikácia
- [ ] Monitoring metrics

## 📊 Monitoring Setup

### 1. Logging
```php
// Production logging config
'logging' => [
    'default' => 'stack',
    'channels' => [
        'file' => [
            'driver' => 'daily',
            'path' => 'var/logs/app.log',
            'level' => 'warning',
            'days' => 30,
        ],
        'error' => [
            'driver' => 'daily',
            'path' => 'var/logs/error.log',
            'level' => 'error',
            'days' => 90,
        ]
    ]
]
```

### 2. Metrics
- Response times
- Error rates
- Memory usage
- Database performance

### 3. Alerts
- Critical errors
- High latency
- Disk space
- Memory usage

## 🔒 Security Verifikácia

### 1. Headers
```php
Header                    Expected Value
X-Frame-Options          DENY
X-XSS-Protection        1; mode=block
X-Content-Type-Options  nosniff
Referrer-Policy        strict-origin-when-cross-origin
```

### 2. SSL/TLS
- Minimálne TLS 1.2
- Silné ciphers
- HSTS aktívny

### 3. Access Control
- Rate limiting
- IP whitelist pre admin
- Firewall rules

## 📈 Performance Optimalizácia

### 1. PHP Settings
```ini
; php.ini
memory_limit = 256M
max_execution_time = 30
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 0
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

### 2. Database
```sql
-- Verify indexes
SHOW INDEX FROM users;
SHOW INDEX FROM sessions;

-- Analyze queries
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
```

### 3. Caching
- Route cache
- Config cache
- Database query cache
- API response cache

## 🔄 Rollback Plan

### 1. Database
```bash
# Backup pred deploymentom
mysqldump -u user -p database > pre_deploy_backup.sql

# Rollback script
mysql -u user -p database < pre_deploy_backup.sql
```

### 2. Application Code
```bash
# Git rollback
git reset --hard HEAD^
git checkout previous-stable-tag
```

### 3. Cache & Config
```bash
# Clear all caches
php bin/console cache:clear
php bin/console config:clear
php bin/console route:clear
```

## 📋 Post-Deployment

### 1. Monitoring
- [ ] Error rates normálne
- [ ] Response times v norme
- [ ] Resource usage OK
- [ ] Database performance OK

### 2. Funkcionalita
- [ ] Critical paths otestované
- [ ] User flows fungujú
- [ ] APIs odpovedajú správne
- [ ] Autentifikácia funguje

### 3. Dokumentácia
- [ ] Deployment dokumentovaný
- [ ] Changes zaznamenané
- [ ] API docs aktuálne
- [ ] Monitoring dashboard aktívny
