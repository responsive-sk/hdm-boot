# Blog Module Deployment Guide

**Production deployment guide for HDM Boot Blog Module**

## 🚀 Deployment Overview

The Blog module supports both **standalone deployment** and **integrated deployment** as part of HDM Boot framework. This guide covers both scenarios.

## 📦 Deployment Options

### **1. Integrated Deployment (Recommended)**
Deploy as part of HDM Boot framework with shared infrastructure.

### **2. Standalone Deployment**
Deploy Blog module independently with its own infrastructure.

### **3. Microservice Deployment**
Deploy as a separate microservice with API-only interface.

## 🏗️ Integrated Deployment

### **Prerequisites**
- PHP 8.2+ with required extensions
- Web server (Nginx/Apache)
- Database (SQLite/MySQL/PostgreSQL)
- Composer
- Git

### **Step 1: Framework Deployment**
```bash
# Deploy HDM Boot framework
git clone https://github.com/responsive-sk/hdm-boot.git
cd hdm-boot

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
# Edit .env with production values
```

### **Step 2: Enable Blog Module**
```bash
# Enable Blog module in .env
echo "ENABLED_MODULES=Blog" >> .env

# Verify module is loaded
php bin/route-list.php | grep blog
```

### **Step 3: Database Setup**
```bash
# Create database tables
php bin/setup-database.php

# Run Blog module migrations
php bin/migrate.php --module=Blog

# Seed sample data (optional)
php bin/seed.php --module=Blog
```

### **Step 4: Web Server Configuration**

#### **Nginx Configuration**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/hdm-boot/public;
    index index.php;

    # Blog module routes
    location /blog {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /api/blog {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

#### **Apache Configuration**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/hdm-boot/public
    
    <Directory /var/www/hdm-boot/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Blog module specific settings
    <Location /blog>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /index.php [QSA,L]
    </Location>
</VirtualHost>
```

## 🔧 Standalone Deployment

### **Step 1: Extract Blog Module**
```bash
# Create standalone project
mkdir blog-standalone
cd blog-standalone

# Copy Blog module
cp -r /path/to/hdm-boot/src/Modules/Optional/Blog/* .

# Install dependencies
composer install --no-dev
```

### **Step 2: Standalone Bootstrap**
```php
<?php
// public/index.php for standalone deployment

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

// Build container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

// Create app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add middleware
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

// Load Blog routes
require __DIR__ . '/../config/routes.php';

$app->run();
```

### **Step 3: Standalone Configuration**
```php
<?php
// config/dependencies.php

use HdmBoot\Modules\Optional\Blog\Infrastructure\Persistence\SqliteArticleRepository;
use HdmBoot\Modules\Optional\Blog\Domain\Repositories\ArticleRepositoryInterface;

return [
    // Database
    PDO::class => function () {
        return new PDO($_ENV['DATABASE_URL'] ?? 'sqlite:var/blog.db');
    },
    
    // Repositories
    ArticleRepositoryInterface::class => DI\autowire(SqliteArticleRepository::class),
    
    // Controllers
    BlogController::class => DI\autowire(),
    BlogApiController::class => DI\autowire(),
];
```

## 🐳 Docker Deployment

### **Dockerfile**
```dockerfile
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    nginx \
    sqlite \
    git \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Copy Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Expose port
EXPOSE 80

# Start services
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
```

### **Docker Compose**
```yaml
version: '3.8'

services:
  blog:
    build: .
    ports:
      - "80:80"
    environment:
      - APP_ENV=prod
      - DATABASE_URL=sqlite:/var/www/html/var/blog.db
    volumes:
      - blog_data:/var/www/html/var
    restart: unless-stopped

  blog_db:
    image: postgres:15-alpine
    environment:
      - POSTGRES_DB=blog
      - POSTGRES_USER=blog
      - POSTGRES_PASSWORD=secure_password
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped

volumes:
  blog_data:
  postgres_data:
```

## ☁️ Cloud Deployment

### **AWS Deployment**

#### **Elastic Beanstalk**
```bash
# Install EB CLI
pip install awsebcli

# Initialize EB application
eb init blog-module

# Create environment
eb create production

# Deploy
eb deploy
```

#### **Lambda Deployment**
```php
<?php
// lambda.php for serverless deployment

require_once 'vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;

return function (HttpRequestEvent $event, Context $context): HttpResponse {
    // Initialize Blog module
    $app = require 'bootstrap/app.php';
    
    // Handle request
    $response = $app->handle($event->toRequest());
    
    return new HttpResponse(
        $response->getBody()->getContents(),
        $response->getHeaders(),
        $response->getStatusCode()
    );
};
```

### **Google Cloud Deployment**

#### **App Engine**
```yaml
# app.yaml
runtime: php82

env_variables:
  APP_ENV: prod
  DATABASE_URL: sqlite:var/blog.db

automatic_scaling:
  min_instances: 1
  max_instances: 10
  target_cpu_utilization: 0.6
```

### **DigitalOcean Deployment**

#### **App Platform**
```yaml
# .do/app.yaml
name: blog-module
services:
- name: web
  source_dir: /
  github:
    repo: your-username/blog-module
    branch: main
  run_command: |
    composer install --no-dev
    php -S 0.0.0.0:8080 -t public
  environment_slug: php
  instance_count: 1
  instance_size_slug: basic-xxs
  http_port: 8080
  routes:
  - path: /
```

## 🔒 Security Configuration

### **Production Security Checklist**
- ✅ **HTTPS enabled** with valid SSL certificate
- ✅ **Environment variables** properly configured
- ✅ **Database credentials** secured
- ✅ **File permissions** set correctly (644 for files, 755 for directories)
- ✅ **Error reporting** disabled in production
- ✅ **Debug mode** disabled
- ✅ **Security headers** configured

### **Environment Variables**
```env
# Production environment
APP_ENV=prod
APP_DEBUG=false

# Security
JWT_SECRET="your-super-secure-64-character-secret-key-here"
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true

# Database
DATABASE_URL="mysql://user:pass@localhost/blog_prod"

# Logging
LOG_LEVEL=warning
LOG_CHANNEL=file
```

## 📊 Monitoring & Logging

### **Health Checks**
```bash
# Blog module health check
curl https://yourdomain.com/api/blog/health

# Expected response
{
  "status": "healthy",
  "module": "Blog",
  "version": "2.0.0",
  "database": "connected",
  "timestamp": "2025-06-22T12:00:00Z"
}
```

### **Logging Configuration**
```php
// config/logging.php
return [
    'blog' => [
        'driver' => 'daily',
        'path' => storage_path('logs/blog.log'),
        'level' => env('LOG_LEVEL', 'warning'),
        'days' => 14,
    ],
];
```

### **Performance Monitoring**
```bash
# Monitor response times
curl -w "@curl-format.txt" -o /dev/null -s https://yourdomain.com/blog

# Monitor database queries
tail -f var/logs/blog.log | grep "SLOW QUERY"
```

## 🚀 Deployment Automation

### **GitHub Actions**
```yaml
name: Deploy Blog Module

on:
  push:
    branches: [main]
    paths: ['src/Modules/Optional/Blog/**']

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Run tests
        run: |
          cd src/Modules/Optional/Blog
          composer test
      
      - name: Deploy to production
        run: |
          rsync -avz --delete \
            --exclude='.git' \
            --exclude='tests' \
            ./ user@server:/var/www/blog/
      
      - name: Restart services
        run: |
          ssh user@server 'sudo systemctl reload php8.2-fpm'
          ssh user@server 'sudo systemctl reload nginx'
```

## 📋 Post-Deployment Checklist

### **Verification Steps**
- ✅ **Homepage loads** - `curl https://yourdomain.com/blog`
- ✅ **API responds** - `curl https://yourdomain.com/api/blog/articles`
- ✅ **Database connected** - Check health endpoint
- ✅ **SSL certificate** valid and not expired
- ✅ **Performance** - Response times < 500ms
- ✅ **Logs** - No critical errors in logs
- ✅ **Monitoring** - Alerts configured and working

### **Rollback Plan**
```bash
# Quick rollback to previous version
git checkout previous-stable-tag
composer install --no-dev
# Restart services
```

---

**Blog Module Deployment** - Production-ready deployment with security and monitoring
