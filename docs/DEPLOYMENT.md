# 🚀 HDM Boot Production Deployment Guide

This guide covers deployment strategies and configurations for the HDM Boot Framework in various environments.

## 🚀 Deployment Overview

The HDM Boot Framework is designed for easy deployment across different environments:
- **Development** - Local development with debugging enabled
- **Staging** - Pre-production testing environment
- **Production** - Live production environment

## 🔧 Environment Configuration

### Environment Variables

#### Generate Secure Keys
```bash
# Use the built-in key generator
php bin/generate-keys.php

# Or generate individual keys manually:
JWT_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
SECURITY_KEY=$(php -r 'echo bin2hex(random_bytes(32));')
REDIS_PASSWORD=$(php -r 'echo bin2hex(random_bytes(16));')
DB_PASSWORD=$(php -r 'echo bin2hex(random_bytes(12));')

# Generate in JSON format for automation
php bin/generate-keys.php --format=json
```

Create appropriate `.env` files for each environment:

#### Development (.env.dev)
```bash
APP_NAME="HDM Boot Dev"
APP_ENV=dev
APP_DEBUG=true
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app_dev.db"

JWT_SECRET="dev-secret-key-change-in-production"
JWT_EXPIRY=3600

ENABLED_MODULES="Blog"

SESSION_NAME="hdm_boot_dev_session"
SESSION_LIFETIME=7200

# Development-specific
LOG_LEVEL=debug
CACHE_ENABLED=false
```

#### Staging (.env.staging)
```bash
APP_NAME="HDM Boot Staging"
APP_ENV=staging
APP_DEBUG=false
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app_staging.db"

JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')"
JWT_EXPIRY=3600

ENABLED_MODULES="Blog"

SESSION_NAME="hdm_boot_staging_session"
SESSION_LIFETIME=7200

# Staging-specific
LOG_LEVEL=info
CACHE_ENABLED=true
```

#### Production (.env.prod)
```bash
APP_NAME="HDM Boot"
APP_ENV=prod
APP_DEBUG=false
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app.db"
# Or for MySQL/PostgreSQL:
# DATABASE_URL="mysql://user:password@localhost/database"
# DATABASE_URL="pgsql://user:password@localhost/database"

JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')"
JWT_EXPIRY=3600

ENABLED_MODULES="Blog"

SESSION_NAME="hdm_boot_session"
SESSION_LIFETIME=7200

# Production-specific
LOG_LEVEL=warning
CACHE_ENABLED=true
```

## 🚀 Production Server Deployment

### Step-by-Step Production Setup

#### 1. **Server Preparation**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3+ and required extensions
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-sqlite3 php8.3-curl php8.3-gd \
    php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath \
    php8.3-intl php8.3-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install web server (Nginx recommended)
sudo apt install -y nginx
```

#### 2. **Application Deployment**
```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/responsive-sk/hdm-boot.git
cd hdm-boot

# Install production dependencies ONLY (no dev packages)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Alternative for updates (faster)
composer install --no-dev --optimize-autoloader --no-scripts
composer dump-autoload --optimize --classmap-authoritative

# Generate secure keys (CRITICAL!)
php bin/generate-keys.php > .env.keys
cat .env.keys  # Copy these to .env

# Configure environment
cp .env.example .env

# IMPORTANT: Set JWT_SECRET in .env
echo "JWT_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')" >> .env

# Edit other production values
nano .env  # Set APP_ENV=prod, APP_DEBUG=false, etc.

# Set proper permissions
sudo chown -R www-data:www-data /var/www/hdm-boot
sudo chmod -R 755 var/
sudo chmod 600 .env
```

#### 3. **Production Composer Commands**
```bash
# Initial production install (recommended)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# For updates (faster, skips scripts)
composer install --no-dev --optimize-autoloader --no-scripts

# Force clean install (if issues)
rm -rf vendor/ composer.lock
composer install --no-dev --optimize-autoloader

# Verify no dev packages
composer show --installed | grep -E "(phpunit|phpstan|php-cs-fixer)"
# Should return empty (no dev packages)
```

#### 4. **Performance Optimization**
```bash
# Enable OPcache (add to php.ini)
echo "opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1" | sudo tee -a /etc/php/8.3/fpm/conf.d/10-opcache.ini

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm
```

## 🐳 Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader --classmap-authoritative

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/var

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    container_name: hdm-boot-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./var:/var/www/var
    networks:
      - mva-network

  nginx:
    image: nginx:alpine
    container_name: hdm-boot-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - mva-network

  database:
    image: mysql:8.0
    container_name: hdm-boot-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: hdm_boot
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_USER: mva_user
      MYSQL_PASSWORD: mva_password
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - mva-network

networks:
  mva-network:
    driver: bridge

volumes:
  db_data:
```

### Nginx Configuration

```nginx
# docker/nginx/default.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(config|src|bootstrap|modules|vendor|var|tests|docs)/ {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 🌐 Traditional Web Server Deployment

### Apache Configuration

```apache
# .htaccess
RewriteEngine On

# Redirect to public directory
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Deny access to sensitive directories
<DirectoryMatch "/(config|src|bootstrap|modules|vendor|var|tests|docs)">
    Require all denied
</DirectoryMatch>

# Deny access to sensitive files
<FilesMatch "\.(env|md|json|lock|log)$">
    Require all denied
</FilesMatch>
```

```apache
# public/.htaccess
RewriteEngine On

# Handle Angular/React routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/hdm-boot/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files and directories
    location ~ /\. {
        deny all;
    }

    location ~ /(config|src|bootstrap|modules|vendor|var|tests|docs)/ {
        deny all;
    }

    location ~ \.(env|md|json|lock|log)$ {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

## 🔒 Production Security Checklist

### File Permissions

```bash
# Set proper permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make var directory writable
chmod -R 755 var/
chown -R www-data:www-data var/

# Protect sensitive files
chmod 600 .env
chmod 600 config/*.php
```

### Environment Security

- [ ] `APP_ENV=prod`
- [ ] `APP_DEBUG=false`
- [ ] Strong JWT secret (32+ characters)
- [ ] Secure database credentials
- [ ] HTTPS enabled with valid SSL certificate
- [ ] Security headers configured
- [ ] Error reporting disabled
- [ ] Log files protected

### Database Security

```bash
# For SQLite
chmod 600 var/storage/*.db
chown www-data:www-data var/storage/*.db

# Ensure database directory is not web accessible
# (should be outside document root or protected by web server config)
```

### File System Security

```bash
# Remove development files in production
rm -rf tests/
rm -rf docs/
rm .env.example
rm docker-compose.yml
rm Dockerfile

# Or use .gitignore and deploy only necessary files
```

## 📊 Monitoring and Logging

### Log Configuration

```php
// config/container.php - Production logging
LoggerInterface::class => function (Container $c): LoggerInterface {
    $pathHelper = $c->get(SecurePathHelper::class);
    $logPath = $pathHelper->securePath('app.log', 'logs');
    
    $logger = new Logger('app');
    
    // Production: Only log warnings and errors
    $logger->pushHandler(new StreamHandler($logPath, Logger::WARNING));
    
    // Optional: Send critical errors to external service
    // $logger->pushHandler(new SlackHandler($token, $channel, 'HDM Boot', true, null, Logger::CRITICAL));
    
    return $logger;
},
```

### Health Check Endpoint

```php
// Add to routes
$app->get('/health', function (ServerRequestInterface $request, ResponseInterface $response) {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'checks' => [
            'database' => 'ok',
            'filesystem' => 'ok',
            'memory' => memory_get_usage(true),
        ]
    ];
    
    $response->getBody()->write(json_encode($health));
    return $response->withHeader('Content-Type', 'application/json');
});
```

## 🚀 Deployment Scripts

### Deployment Script

```bash
#!/bin/bash
# deploy.sh

set -e

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install/update production dependencies only
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Clear cache
rm -rf var/cache/*

# Set permissions
chmod -R 755 var/
chown -R www-data:www-data var/

# Run database migrations (when implemented)
# php bin/console db:migrate

# Restart services
sudo systemctl reload nginx
sudo systemctl reload php8.1-fpm

echo "Deployment completed successfully!"
```

### Zero-Downtime Deployment

```bash
#!/bin/bash
# zero-downtime-deploy.sh

set -e

DEPLOY_DIR="/var/www/hdm-boot"
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Starting zero-downtime deployment..."

# Create backup
cp -r $DEPLOY_DIR $BACKUP_DIR/hdm-boot_$TIMESTAMP

# Deploy to temporary directory
git clone https://github.com/your-repo/hdm-boot.git /tmp/hdm-boot-new
cd /tmp/hdm-boot-new

# Install production dependencies only
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Copy environment file
cp $DEPLOY_DIR/.env .env

# Copy persistent data
cp -r $DEPLOY_DIR/var ./

# Atomic switch
mv $DEPLOY_DIR $DEPLOY_DIR.old
mv /tmp/hdm-boot-new $DEPLOY_DIR

# Restart services
sudo systemctl reload nginx
sudo systemctl reload php8.1-fpm

# Cleanup
rm -rf $DEPLOY_DIR.old

echo "Zero-downtime deployment completed!"
```

## 📈 Performance Optimization

### Production Optimizations

```bash
# Composer optimizations
composer install --no-dev --optimize-autoloader --classmap-authoritative

# PHP OPcache configuration (php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### Caching Strategy

```php
// Enable container compilation in production
if ($_ENV['APP_ENV'] === 'prod') {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}
```

## 🚨 Troubleshooting

### Common Production Issues

#### JWT Secret Missing/Invalid
```bash
# Check if JWT_SECRET is set
grep JWT_SECRET .env

# If missing, generate and add to .env
echo "JWT_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')" >> .env

# Verify JWT secret length (must be 32+ characters)
php -r "
\$secret = getenv('JWT_SECRET') ?: file_get_contents('.env') | grep JWT_SECRET;
echo 'JWT Secret length: ' . strlen(\$secret) . PHP_EOL;
if (strlen(\$secret) < 32) {
    echo 'ERROR: JWT secret too short!' . PHP_EOL;
} else {
    echo 'JWT secret is valid!' . PHP_EOL;
}
"

# Test JWT service manually
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$secret = \$_ENV['JWT_SECRET'] ?? 'not-set';
echo 'JWT Secret: ' . \$secret . PHP_EOL;
echo 'Length: ' . strlen(\$secret) . PHP_EOL;
if (strlen(\$secret) < 32) {
    echo 'ERROR: JWT secret too short for production!' . PHP_EOL;
    exit(1);
} else {
    echo 'JWT secret is production-ready!' . PHP_EOL;
}
"
```

#### DI Container Compilation Issues
```bash
# Clear container cache
rm -rf var/cache/*

# Regenerate autoloader
composer dump-autoload --optimize

# Test container compilation
php -r "
require 'vendor/autoload.php';
try {
    \$container = require 'config/container.php';
    echo 'Container compiled successfully!' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Container error: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"
```

#### Environment Variable Issues
```bash
# Check all environment variables
php -r "
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
echo 'APP_ENV: ' . (\$_ENV['APP_ENV'] ?? 'not-set') . PHP_EOL;
echo 'APP_DEBUG: ' . (\$_ENV['APP_DEBUG'] ?? 'not-set') . PHP_EOL;
echo 'JWT_SECRET length: ' . strlen(\$_ENV['JWT_SECRET'] ?? '') . PHP_EOL;
echo 'DATABASE_URL: ' . (\$_ENV['DATABASE_URL'] ?? 'not-set') . PHP_EOL;
"

# Validate production environment
php bin/validate-env.php  # Create this script if needed
```

This deployment guide provides comprehensive instructions for deploying the HDM Boot Framework in various environments with proper security and performance considerations.
