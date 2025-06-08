# Deployment Guide

This guide covers deployment strategies and configurations for the MVA Bootstrap Application in various environments.

## 🚀 Deployment Overview

The MVA Bootstrap Application is designed for easy deployment across different environments:
- **Development** - Local development with debugging enabled
- **Staging** - Pre-production testing environment
- **Production** - Live production environment

## 🔧 Environment Configuration

### Environment Variables

Create appropriate `.env` files for each environment:

#### Development (.env.dev)
```bash
APP_NAME="MVA Bootstrap Dev"
APP_ENV=dev
APP_DEBUG=true
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app_dev.db"

JWT_SECRET="dev-secret-key-change-in-production"
JWT_EXPIRY=3600

ENABLED_MODULES="Article"

SESSION_NAME="mva_bootstrap_dev_session"
SESSION_LIFETIME=7200

# Development-specific
LOG_LEVEL=debug
CACHE_ENABLED=false
```

#### Staging (.env.staging)
```bash
APP_NAME="MVA Bootstrap Staging"
APP_ENV=staging
APP_DEBUG=false
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app_staging.db"

JWT_SECRET="staging-secret-key-32-characters-long"
JWT_EXPIRY=3600

ENABLED_MODULES="Article"

SESSION_NAME="mva_bootstrap_staging_session"
SESSION_LIFETIME=7200

# Staging-specific
LOG_LEVEL=info
CACHE_ENABLED=true
```

#### Production (.env.prod)
```bash
APP_NAME="MVA Bootstrap"
APP_ENV=prod
APP_DEBUG=false
APP_TIMEZONE=UTC

DATABASE_URL="sqlite:var/storage/app.db"
# Or for MySQL/PostgreSQL:
# DATABASE_URL="mysql://user:password@localhost/database"
# DATABASE_URL="pgsql://user:password@localhost/database"

JWT_SECRET="production-secret-key-must-be-32-chars-minimum"
JWT_EXPIRY=3600

ENABLED_MODULES="Article"

SESSION_NAME="mva_bootstrap_session"
SESSION_LIFETIME=7200

# Production-specific
LOG_LEVEL=warning
CACHE_ENABLED=true
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

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

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
    container_name: mva-bootstrap-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./var:/var/www/var
    networks:
      - mva-network

  nginx:
    image: nginx:alpine
    container_name: mva-bootstrap-nginx
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
    container_name: mva-bootstrap-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: mva_bootstrap
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
    root /var/www/html/mva-bootstrap/public;
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
    // $logger->pushHandler(new SlackHandler($token, $channel, 'MVA Bootstrap', true, null, Logger::CRITICAL));
    
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

# Install/update dependencies
composer install --no-dev --optimize-autoloader

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

DEPLOY_DIR="/var/www/mva-bootstrap"
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Starting zero-downtime deployment..."

# Create backup
cp -r $DEPLOY_DIR $BACKUP_DIR/mva-bootstrap_$TIMESTAMP

# Deploy to temporary directory
git clone https://github.com/your-repo/mva-bootstrap.git /tmp/mva-bootstrap-new
cd /tmp/mva-bootstrap-new

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp $DEPLOY_DIR/.env .env

# Copy persistent data
cp -r $DEPLOY_DIR/var ./

# Atomic switch
mv $DEPLOY_DIR $DEPLOY_DIR.old
mv /tmp/mva-bootstrap-new $DEPLOY_DIR

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

This deployment guide provides comprehensive instructions for deploying the MVA Bootstrap Application in various environments with proper security and performance considerations.
