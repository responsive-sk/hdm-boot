# Environment Setup Guide

Komplexný sprievodca nastavením vývojového prostredia pre HDM Boot aplikáciu.

## 🎯 Prehľad prostredí

HDM Boot podporuje tri hlavné prostredia:

- **Development** - Lokálny vývoj s debuggingom
- **Staging** - Pre-produkčné testovanie
- **Production** - Živé produkčné prostredie

## 💻 Development Environment

### 1. Systémové požiadavky

```bash
# Minimálne požiadavky
PHP >= 8.3
Composer >= 2.0
Node.js >= 18.0 (pre frontend assets)
Git >= 2.30

# Odporúčané
PHP 8.3+ s extensions: pdo, sqlite3, mbstring, json, openssl
Docker & Docker Compose (voliteľné)
```

### 2. Lokálna inštalácia

```bash
# 1. Klonuj repository
git clone https://github.com/your-org/hdm-boot.git
cd hdm-boot

# 2. Nainštaluj PHP závislosti
composer install

# 3. Vytvor .env súbor
cp .env.example .env.dev

# 4. Vygeneruj bezpečnostné kľúče
php bin/generate-keys.php

# 5. Nastav permissions
chmod -R 755 var/
chmod -R 755 public/storage/

# 6. Inicializuj databázy
php bin/init-all-databases.php

# 7. Spusti development server
php -S localhost:8001 -t public/
```

### 3. Development .env konfigurácia

```bash
# .env.dev
APP_NAME="HDM Boot Dev"
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=Europe/Bratislava

# Database
DATABASE_URL="sqlite:var/storage/app_dev.db"
MARK_DATABASE_URL="sqlite:var/storage/mark_dev.db"
SYSTEM_DATABASE_URL="sqlite:var/storage/system_dev.db"

# Security
JWT_SECRET="dev-jwt-secret-change-in-production"
JWT_EXPIRY=3600
SECURITY_KEY="dev-security-key"

# Sessions
SESSION_NAME="hdm_boot_dev_session"
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTPONLY=true

# Logging
LOG_LEVEL=debug
LOG_CHANNEL=file

# Development specific
ENABLED_MODULES="Blog,CMS"
CACHE_ENABLED=false
RATE_LIMITING_ENABLED=false

# Frontend
ASSET_VERSION=dev
WEBPACK_DEV_SERVER=true
```

### 4. Docker Development Setup

```yaml
# docker-compose.dev.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile.dev
    ports:
      - "8001:8001"
    volumes:
      - .:/var/www/html
      - ./var:/var/www/html/var
    environment:
      - APP_ENV=development
      - APP_DEBUG=true
    command: php -S 0.0.0.0:8001 -t public/

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/dev.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    command: redis-server --requirepass dev-password

volumes:
  app_data:
```

```bash
# Spustenie Docker development
docker-compose -f docker-compose.dev.yml up -d

# Logs
docker-compose -f docker-compose.dev.yml logs -f app

# Shell do kontajnera
docker-compose -f docker-compose.dev.yml exec app bash
```

## 🧪 Staging Environment

### 1. Staging konfigurácia

```bash
# .env.staging
APP_NAME="HDM Boot Staging"
APP_ENV=staging
APP_DEBUG=false
APP_TIMEZONE=Europe/Bratislava

# Database
DATABASE_URL="sqlite:var/storage/app_staging.db"
MARK_DATABASE_URL="sqlite:var/storage/mark_staging.db"
SYSTEM_DATABASE_URL="sqlite:var/storage/system_staging.db"

# Security
JWT_SECRET="staging-jwt-secret-generated-key"
JWT_EXPIRY=3600
SECURITY_KEY="staging-security-key"

# Sessions
SESSION_NAME="hdm_boot_staging_session"
SESSION_LIFETIME=3600
SESSION_SECURE=true
SESSION_HTTPONLY=true

# Logging
LOG_LEVEL=info
LOG_CHANNEL=file

# Staging specific
ENABLED_MODULES="Blog,CMS"
CACHE_ENABLED=true
RATE_LIMITING_ENABLED=true

# Monitoring
HEALTH_CHECK_ENABLED=true
METRICS_ENABLED=true
```

### 2. Staging deployment script

```bash
#!/bin/bash
# scripts/deploy-staging.sh

set -e

echo "🚀 Deploying to staging..."

# 1. Pull latest code
git pull origin develop

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Clear caches
php bin/clear-cache.php

# 4. Run migrations
php bin/migrate.php

# 5. Validate environment
php bin/validate-env.php staging

# 6. Run health checks
php bin/health-check.php

# 7. Restart services
sudo systemctl reload nginx
sudo systemctl reload php8.3-fpm

echo "✅ Staging deployment complete!"
```

## 🚀 Production Environment

### 1. Production konfigurácia

```bash
# .env.production
APP_NAME="HDM Boot"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Bratislava

# Database
DATABASE_URL="sqlite:/var/www/hdm-boot/var/storage/app.db"
MARK_DATABASE_URL="sqlite:/var/www/hdm-boot/var/storage/mark.db"
SYSTEM_DATABASE_URL="sqlite:/var/www/hdm-boot/var/storage/system.db"

# Security (generated with bin/generate-keys.php)
JWT_SECRET="production-jwt-secret-64-chars-long"
JWT_EXPIRY=1800
SECURITY_KEY="production-security-key-64-chars-long"

# Sessions
SESSION_NAME="hdm_boot_session"
SESSION_LIFETIME=1800
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=strict

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=file
LOG_ROTATION=daily

# Production optimizations
ENABLED_MODULES="Blog"
CACHE_ENABLED=true
RATE_LIMITING_ENABLED=true
OPCACHE_ENABLED=true

# Monitoring
HEALTH_CHECK_ENABLED=true
METRICS_ENABLED=true
ERROR_REPORTING=false
```

### 2. Production server setup

```bash
#!/bin/bash
# scripts/setup-production.sh

# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install PHP 8.3
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.3 php8.3-fpm php8.3-cli php8.3-pdo php8.3-sqlite3 \
                 php8.3-mbstring php8.3-json php8.3-openssl php8.3-opcache

# 3. Install Nginx
sudo apt install nginx

# 4. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 5. Create application user
sudo useradd -m -s /bin/bash hdm-boot
sudo usermod -aG www-data hdm-boot

# 6. Setup directory structure
sudo mkdir -p /var/www/hdm-boot
sudo chown hdm-boot:www-data /var/www/hdm-boot
sudo chmod 755 /var/www/hdm-boot

# 7. Configure Nginx
sudo cp config/nginx/production.conf /etc/nginx/sites-available/hdm-boot
sudo ln -s /etc/nginx/sites-available/hdm-boot /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# 8. Configure PHP-FPM
sudo cp config/php/production.ini /etc/php/8.3/fpm/conf.d/99-hdm-boot.ini
sudo cp config/php/www.conf /etc/php/8.3/fpm/pool.d/hdm-boot.conf

# 9. Setup SSL (Let's Encrypt)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com

# 10. Configure firewall
sudo ufw allow 'Nginx Full'
sudo ufw allow ssh
sudo ufw enable

# 11. Setup log rotation
sudo cp config/logrotate/hdm-boot /etc/logrotate.d/

# 12. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo systemctl enable nginx
sudo systemctl enable php8.3-fpm
```

### 3. Production deployment

```bash
#!/bin/bash
# scripts/deploy-production.sh

set -e

DEPLOY_DIR="/var/www/hdm-boot"
BACKUP_DIR="/var/backups/hdm-boot"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🚀 Starting production deployment..."

# 1. Create backup
echo "📦 Creating backup..."
sudo mkdir -p $BACKUP_DIR
sudo tar -czf $BACKUP_DIR/backup_$TIMESTAMP.tar.gz -C $DEPLOY_DIR .

# 2. Put site in maintenance mode
echo "🔧 Enabling maintenance mode..."
sudo touch $DEPLOY_DIR/public/.maintenance

# 3. Pull latest code
echo "📥 Pulling latest code..."
cd $DEPLOY_DIR
sudo -u hdm-boot git pull origin main

# 4. Install dependencies
echo "📦 Installing dependencies..."
sudo -u hdm-boot composer install --no-dev --optimize-autoloader --classmap-authoritative

# 5. Clear caches
echo "🧹 Clearing caches..."
sudo -u hdm-boot php bin/clear-cache.php

# 6. Run migrations
echo "🗄️ Running migrations..."
sudo -u hdm-boot php bin/migrate.php

# 7. Validate environment
echo "✅ Validating environment..."
sudo -u hdm-boot php bin/validate-env.php production

# 8. Set permissions
echo "🔐 Setting permissions..."
sudo chown -R hdm-boot:www-data $DEPLOY_DIR
sudo chmod -R 755 $DEPLOY_DIR/var
sudo chmod -R 755 $DEPLOY_DIR/public/storage

# 9. Reload services
echo "🔄 Reloading services..."
sudo systemctl reload nginx
sudo systemctl reload php8.3-fpm

# 10. Run health checks
echo "🏥 Running health checks..."
sleep 5
sudo -u hdm-boot php bin/health-check.php

# 11. Disable maintenance mode
echo "✅ Disabling maintenance mode..."
sudo rm -f $DEPLOY_DIR/public/.maintenance

echo "🎉 Production deployment complete!"
echo "📊 Backup created: $BACKUP_DIR/backup_$TIMESTAMP.tar.gz"
```

## 🔧 Development Tools

### 1. Code Quality Tools

```bash
# PHPStan - Static analysis
composer stan

# PHP CS Fixer - Code style
composer cs-check
composer cs-fix

# PHPUnit - Testing
composer test
composer test-coverage

# All quality checks
composer quality
```

### 2. Development Scripts

```bash
# scripts/dev-setup.sh
#!/bin/bash

echo "🛠️ Setting up development environment..."

# Install git hooks
cp scripts/git-hooks/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit

# Setup IDE configuration
cp config/ide/phpstorm.xml .idea/
cp config/ide/vscode.json .vscode/settings.json

# Create development databases
php bin/init-all-databases.php

# Seed test data
php bin/seed-dev-data.php

echo "✅ Development environment ready!"
```

### 3. Git Hooks

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "🔍 Running pre-commit checks..."

# Run PHPStan
composer stan
if [ $? -ne 0 ]; then
    echo "❌ PHPStan failed"
    exit 1
fi

# Run code style check
composer cs-check
if [ $? -ne 0 ]; then
    echo "❌ Code style check failed"
    exit 1
fi

# Run tests
composer test
if [ $? -ne 0 ]; then
    echo "❌ Tests failed"
    exit 1
fi

echo "✅ All checks passed!"
```

## 📋 Environment Checklist

### Development:
- [ ] PHP 8.3+ nainštalované
- [ ] Composer dependencies nainštalované
- [ ] .env.dev súbor nakonfigurovaný
- [ ] Databázy inicializované
- [ ] Development server spustený
- [ ] Git hooks nastavené

### Staging:
- [ ] Staging server nakonfigurovaný
- [ ] .env.staging súbor nakonfigurovaný
- [ ] SSL certifikáty nastavené
- [ ] Deployment script otestovaný
- [ ] Health checks fungujú

### Production:
- [ ] Production server zabezpečený
- [ ] .env.production s production keys
- [ ] SSL certifikáty platné
- [ ] Backup stratégia implementovaná
- [ ] Monitoring nastavené
- [ ] Deployment pipeline otestovaný

## 🔗 Ďalšie zdroje

- [Deployment Guide](../DEPLOYMENT.md)
- [Production Checklist](production-checklist.md)
- [Security Hardening](security-hardening.md)
- [Troubleshooting](../TROUBLESHOOTING.md)
