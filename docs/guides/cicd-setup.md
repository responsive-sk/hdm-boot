# CI/CD Setup Guide

Komplexný sprievodca nastavením CI/CD pipeline pre HDM Boot aplikáciu.

## 🚀 CI/CD Overview

HDM Boot podporuje **multi-stage CI/CD pipeline** s týmito fázami:

- **Build** - Inštalácia závislostí a build
- **Test** - Unit, Integration a Feature testy
- **Quality** - Code style, PHPStan, Security scan
- **Deploy** - Automatické nasadenie do staging/production

## 🏗️ Pipeline Architecture

```
CI/CD Pipeline:
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│    Build    │───▶│    Test     │───▶│   Quality   │───▶│   Deploy    │
│             │    │             │    │             │    │             │
│ • Composer  │    │ • PHPUnit   │    │ • PHPStan   │    │ • Staging   │
│ • NPM       │    │ • Coverage  │    │ • CS Fixer  │    │ • Production│
│ • Assets    │    │ • Security  │    │ • Security  │    │ • Rollback  │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

## 🔧 GitHub Actions Setup

### Main Workflow

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

env:
  PHP_VERSION: '8.3'
  NODE_VERSION: '18'

jobs:
  build:
    name: Build & Install Dependencies
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: pdo, sqlite3, mbstring, json, openssl
        coverage: xdebug
        
    - name: Cache Composer dependencies
      uses: actions/cache@v3
      with:
        path: vendor
        key: composer-${{ hashFiles('composer.lock') }}
        restore-keys: composer-
        
    - name: Install Composer dependencies
      run: composer install --prefer-dist --no-progress --no-interaction
      
    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: ${{ env.NODE_VERSION }}
        cache: 'npm'
        
    - name: Install NPM dependencies
      run: npm ci
      
    - name: Build assets
      run: npm run build
      
    - name: Create required directories
      run: |
        mkdir -p var/logs var/cache var/storage var/sessions
        chmod -R 755 var/
        
    - name: Upload build artifacts
      uses: actions/upload-artifact@v3
      with:
        name: build-artifacts
        path: |
          vendor/
          node_modules/
          public/assets/
          var/

  test:
    name: Run Tests
    runs-on: ubuntu-latest
    needs: build
    
    strategy:
      matrix:
        test-suite: [unit, integration, feature]
        
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: pdo, sqlite3, mbstring, json, openssl
        coverage: xdebug
        
    - name: Download build artifacts
      uses: actions/download-artifact@v3
      with:
        name: build-artifacts
        
    - name: Setup test environment
      run: |
        cp .env.example .env.testing
        php bin/generate-keys.php --env=testing
        php bin/init-all-databases.php --env=testing
        
    - name: Run ${{ matrix.test-suite }} tests
      run: |
        case "${{ matrix.test-suite }}" in
          "unit")
            composer test:unit
            ;;
          "integration")
            composer test:integration
            ;;
          "feature")
            composer test:feature
            ;;
        esac
        
    - name: Generate coverage report
      if: matrix.test-suite == 'unit'
      run: composer test:coverage
      
    - name: Upload coverage to Codecov
      if: matrix.test-suite == 'unit'
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage/clover.xml
        flags: unittests

  quality:
    name: Code Quality Checks
    runs-on: ubuntu-latest
    needs: build
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: pdo, sqlite3, mbstring, json, openssl
        
    - name: Download build artifacts
      uses: actions/download-artifact@v3
      with:
        name: build-artifacts
        
    - name: Run PHPStan
      run: composer stan
      
    - name: Run PHP CS Fixer
      run: composer cs-check
      
    - name: Run Security Audit
      run: composer audit
      
    - name: Run Path Security Check
      run: ./scripts/check-paths.sh
      
    - name: Validate Composer
      run: composer validate --strict

  security:
    name: Security Scan
    runs-on: ubuntu-latest
    needs: build
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        
    - name: Download build artifacts
      uses: actions/download-artifact@v3
      with:
        name: build-artifacts
        
    - name: Run Composer Security Audit
      run: composer audit
      
    - name: Run Custom Security Checks
      run: |
        # Check for hardcoded secrets
        ./scripts/security-scan.sh
        
        # Validate environment configuration
        php bin/validate-env.php testing
        
        # Check file permissions
        ./scripts/check-permissions.sh

  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: [test, quality, security]
    if: github.ref == 'refs/heads/develop' && github.event_name == 'push'
    
    environment:
      name: staging
      url: https://staging.your-domain.com
      
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        
    - name: Download build artifacts
      uses: actions/download-artifact@v3
      with:
        name: build-artifacts
        
    - name: Deploy to staging
      run: |
        # Create deployment package
        tar -czf deployment.tar.gz \
          --exclude='.git' \
          --exclude='tests' \
          --exclude='node_modules' \
          .
          
        # Deploy via SSH
        scp deployment.tar.gz ${{ secrets.STAGING_USER }}@${{ secrets.STAGING_HOST }}:/tmp/
        
        ssh ${{ secrets.STAGING_USER }}@${{ secrets.STAGING_HOST }} << 'EOF'
          cd /var/www/staging
          
          # Backup current version
          sudo tar -czf /var/backups/staging-$(date +%Y%m%d_%H%M%S).tar.gz .
          
          # Extract new version
          sudo tar -xzf /tmp/deployment.tar.gz
          
          # Set permissions
          sudo chown -R hdm-boot:www-data .
          sudo chmod -R 755 var/
          
          # Run deployment tasks
          sudo -u hdm-boot composer install --no-dev --optimize-autoloader
          sudo -u hdm-boot php bin/migrate.php
          sudo -u hdm-boot php bin/clear-cache.php
          
          # Restart services
          sudo systemctl reload nginx
          sudo systemctl reload php8.3-fpm
          
          # Health check
          sleep 5
          sudo -u hdm-boot php bin/health-check.php
        EOF
        
    - name: Notify deployment
      if: always()
      uses: 8398a7/action-slack@v3
      with:
        status: ${{ job.status }}
        channel: '#deployments'
        webhook_url: ${{ secrets.SLACK_WEBHOOK }}

  deploy-production:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: [test, quality, security]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    
    environment:
      name: production
      url: https://your-domain.com
      
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        
    - name: Download build artifacts
      uses: actions/download-artifact@v3
      with:
        name: build-artifacts
        
    - name: Deploy to production
      run: |
        # Create deployment package
        tar -czf deployment.tar.gz \
          --exclude='.git' \
          --exclude='tests' \
          --exclude='node_modules' \
          --exclude='*.dev.*' \
          .
          
        # Deploy via SSH with blue-green deployment
        scp deployment.tar.gz ${{ secrets.PROD_USER }}@${{ secrets.PROD_HOST }}:/tmp/
        
        ssh ${{ secrets.PROD_USER }}@${{ secrets.PROD_HOST }} << 'EOF'
          # Blue-green deployment script
          /opt/scripts/blue-green-deploy.sh /tmp/deployment.tar.gz
        EOF
        
    - name: Run smoke tests
      run: |
        # Wait for deployment
        sleep 30
        
        # Run smoke tests
        curl -f https://your-domain.com/health || exit 1
        curl -f https://your-domain.com/api/status || exit 1
        
    - name: Notify deployment
      if: always()
      uses: 8398a7/action-slack@v3
      with:
        status: ${{ job.status }}
        channel: '#deployments'
        webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### Module-specific Workflows

```yaml
# .github/workflows/module-blog.yml
name: Blog Module CI

on:
  push:
    paths: 
      - 'src/Modules/Optional/Blog/**'
      - 'tests/Modules/Blog/**'
  pull_request:
    paths:
      - 'src/Modules/Optional/Blog/**'
      - 'tests/Modules/Blog/**'

jobs:
  test-blog-module:
    name: Test Blog Module
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout code
      uses: actions/checkout@v4
      
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: pdo, sqlite3, mbstring, json
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Run Blog module tests
      run: |
        composer test:module:blog
        composer test:module:blog:coverage
        
    - name: Upload module coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage/blog-module.xml
        flags: blog-module
```

## 🔧 GitLab CI Setup

### GitLab CI Configuration

```yaml
# .gitlab-ci.yml
stages:
  - build
  - test
  - quality
  - security
  - deploy

variables:
  PHP_VERSION: "8.3"
  COMPOSER_CACHE_DIR: "$CI_PROJECT_DIR/.composer-cache"
  
cache:
  key: "$CI_COMMIT_REF_SLUG"
  paths:
    - vendor/
    - .composer-cache/
    - node_modules/

build:
  stage: build
  image: php:8.3-cli
  before_script:
    - apt-get update -qq && apt-get install -y -qq git unzip
    - curl -sS https://getcomposer.org/installer | php
    - mv composer.phar /usr/local/bin/composer
  script:
    - composer install --prefer-dist --no-progress --no-interaction
    - mkdir -p var/logs var/cache var/storage
    - chmod -R 755 var/
  artifacts:
    paths:
      - vendor/
      - var/
    expire_in: 1 hour

test:unit:
  stage: test
  image: php:8.3-cli
  dependencies:
    - build
  script:
    - cp .env.example .env.testing
    - php bin/generate-keys.php --env=testing
    - composer test:unit
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage/cobertura.xml

test:integration:
  stage: test
  image: php:8.3-cli
  dependencies:
    - build
  script:
    - cp .env.example .env.testing
    - php bin/init-all-databases.php --env=testing
    - composer test:integration

quality:phpstan:
  stage: quality
  image: php:8.3-cli
  dependencies:
    - build
  script:
    - composer stan

quality:cs:
  stage: quality
  image: php:8.3-cli
  dependencies:
    - build
  script:
    - composer cs-check

security:audit:
  stage: security
  image: php:8.3-cli
  dependencies:
    - build
  script:
    - composer audit
    - ./scripts/security-scan.sh

deploy:staging:
  stage: deploy
  image: alpine:latest
  only:
    - develop
  before_script:
    - apk add --no-cache openssh-client rsync
    - eval $(ssh-agent -s)
    - echo "$STAGING_SSH_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
  script:
    - rsync -avz --delete . $STAGING_USER@$STAGING_HOST:/var/www/staging/
    - ssh $STAGING_USER@$STAGING_HOST "cd /var/www/staging && ./scripts/deploy-staging.sh"

deploy:production:
  stage: deploy
  image: alpine:latest
  only:
    - main
  when: manual
  before_script:
    - apk add --no-cache openssh-client rsync
    - eval $(ssh-agent -s)
    - echo "$PROD_SSH_KEY" | tr -d '\r' | ssh-add -
  script:
    - rsync -avz --delete . $PROD_USER@$PROD_HOST:/var/www/production/
    - ssh $PROD_USER@$PROD_HOST "cd /var/www/production && ./scripts/deploy-production.sh"
```

## 🐳 Docker CI/CD

### Multi-stage Dockerfile

```dockerfile
# Dockerfile.ci
FROM php:8.3-cli as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Development stage
FROM base as development
RUN composer install --dev --no-interaction
COPY . .
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

# Testing stage
FROM development as testing
RUN mkdir -p var/logs var/cache var/storage
RUN chmod -R 755 var/
CMD ["composer", "test"]

# Production stage
FROM base as production
COPY . .
RUN mkdir -p var/logs var/cache var/storage && \
    chmod -R 755 var/ && \
    chown -R www-data:www-data var/

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
```

### Docker Compose for CI

```yaml
# docker-compose.ci.yml
version: '3.8'

services:
  app-test:
    build:
      context: .
      dockerfile: Dockerfile.ci
      target: testing
    volumes:
      - .:/app
      - /app/vendor
    environment:
      - APP_ENV=testing
      - DATABASE_URL=sqlite:var/storage/test.db
    command: composer test

  app-quality:
    build:
      context: .
      dockerfile: Dockerfile.ci
      target: development
    volumes:
      - .:/app
      - /app/vendor
    command: |
      sh -c "
        composer stan &&
        composer cs-check &&
        composer audit
      "

  app-security:
    build:
      context: .
      dockerfile: Dockerfile.ci
      target: development
    volumes:
      - .:/app
    command: ./scripts/security-scan.sh
```

## 📊 Monitoring & Notifications

### Slack Notifications

```yaml
# .github/workflows/notifications.yml
name: Notifications

on:
  workflow_run:
    workflows: ["CI/CD Pipeline"]
    types:
      - completed

jobs:
  notify:
    runs-on: ubuntu-latest
    steps:
    - name: Notify Slack on Success
      if: ${{ github.event.workflow_run.conclusion == 'success' }}
      uses: 8398a7/action-slack@v3
      with:
        status: success
        channel: '#ci-cd'
        message: |
          ✅ CI/CD Pipeline succeeded for ${{ github.event.workflow_run.head_branch }}
          Commit: ${{ github.event.workflow_run.head_sha }}
          Author: ${{ github.event.workflow_run.head_commit.author.name }}
        webhook_url: ${{ secrets.SLACK_WEBHOOK }}

    - name: Notify Slack on Failure
      if: ${{ github.event.workflow_run.conclusion == 'failure' }}
      uses: 8398a7/action-slack@v3
      with:
        status: failure
        channel: '#ci-cd'
        message: |
          ❌ CI/CD Pipeline failed for ${{ github.event.workflow_run.head_branch }}
          Commit: ${{ github.event.workflow_run.head_sha }}
          Author: ${{ github.event.workflow_run.head_commit.author.name }}
        webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

## 📋 CI/CD Setup Checklist

### Repository Setup:
- [ ] GitHub/GitLab repository vytvorený
- [ ] Branch protection rules nastavené
- [ ] Secrets nakonfigurované
- [ ] Webhooks nastavené

### Pipeline Configuration:
- [ ] CI/CD workflow súbory vytvorené
- [ ] Test stages nakonfigurované
- [ ] Quality gates implementované
- [ ] Security scans nastavené

### Deployment:
- [ ] Staging environment pripravený
- [ ] Production environment pripravený
- [ ] SSH keys nakonfigurované
- [ ] Deployment scripts vytvorené

### Monitoring:
- [ ] Notifications nastavené
- [ ] Metrics collection implementované
- [ ] Alerting nakonfigurované
- [ ] Dashboard vytvorený

## 🔗 Ďalšie zdroje

- [Environment Setup](environment-setup.md)
- [Deployment Guide](../DEPLOYMENT.md)
- [Testing Guide](testing-guide.md)
- [Security Best Practices](security-practices.md)
