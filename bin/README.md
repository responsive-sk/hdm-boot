# HDM Boot Scripts Documentation

Prehľad všetkých dostupných scriptov v HDM Boot aplikácii.

## 📁 Scripts Overview

HDM Boot obsahuje **22 scriptov** všetky umiestnené v `bin/` adresári:

- **Setup & Initialization** (5 scriptov)
- **Security & Audit** (4 skripty)
- **Maintenance & Cleanup** (5 scriptov)
- **Build & Deployment** (2 skripty)
- **Monitoring & Health** (2 skripty)
- **Utilities** (3 skripty)

## 🚀 Setup & Initialization Scripts

### `init-all-databases.php`
Inicializuje všetky databázy (app, mark, system).

```bash
# Základné použitie
php bin/init-all-databases.php

# S konkrétnym prostredím
php bin/init-all-databases.php --env=production
```

### `init-user-db.php`
Inicializuje user databázu s tabuľkami pre používateľov.

```bash
php bin/init-user-db.php
```

### `init-mark-db.php`
Inicializuje Mark admin databázu.

```bash
php bin/init-mark-db.php
```

### `init-system-db.php`
Inicializuje systémovú databázu pre konfiguráciu.

```bash
php bin/init-system-db.php
```

### `generate-keys.php`
Generuje bezpečnostné kľúče pre JWT a encryption.

```bash
# Generuj kľúče pre aktuálne prostredie
php bin/generate-keys.php

# Generuj kľúče pre konkrétne prostredie
php bin/generate-keys.php --env=production
```

## 🔒 Security & Audit Scripts

### `audit-paths.php`
Vykonáva bezpečnostný audit path handling.

```bash
php bin/audit-paths.php
```

### `check-paths.sh`
Bash verzia path security check.

```bash
./bin/check-paths.sh
```

### `cleanup-paths.php`
Čistí nebezpečné paths a sanitizuje vstupy.

```bash
php bin/cleanup-paths.php
```

### `validate-env.php`
Validuje environment konfiguráciu.

```bash
# Validuj aktuálne prostredie
php bin/validate-env.php

# Validuj konkrétne prostredie
php bin/validate-env.php production
```

## 🧹 Maintenance & Cleanup Scripts

### `cache-clear.php` ⭐ **NEW**
Vyčistí rôzne typy cache.

```bash
# Vyčisti všetky cache
php bin/cache-clear.php

# Vyčisti konkrétny typ
php bin/cache-clear.php app
php bin/cache-clear.php template
php bin/cache-clear.php opcache
php bin/cache-clear.php session
php bin/cache-clear.php logs

# S možnosťami
php bin/cache-clear.php all --verbose
php bin/cache-clear.php all --dry-run
php bin/cache-clear.php all --force
```

### `fix-permissions.php`
Opravuje file permissions pre web server.

```bash
php bin/fix-permissions.php
```

### `fix-permissions.sh`
Bash verzia permissions fix.

```bash
./bin/fix-permissions.sh
```

### `log-cleanup.php`
Čistí staré log súbory.

```bash
php bin/log-cleanup.php
```

### `log-rotation.sh`
Rotuje log súbory.

```bash
./bin/log-rotation.sh
```

## 🏗️ Build & Deployment Scripts

### `build-production.php`
Vytvorí production build aplikácie.

```bash
php bin/build-production.php
```

### `deploy-ftps.php` ⭐ **NEW**
Nasadí aplikáciu na shared hosting cez FTPS.

```bash
# Production deployment
php bin/deploy-ftps.php production

# Staging deployment
php bin/deploy-ftps.php staging

# S možnosťami
php bin/deploy-ftps.php production --backup
php bin/deploy-ftps.php staging --dry-run
```

## 🏥 Monitoring & Health Scripts

### `health-check.php` ⭐ **NEW**
Vykonáva komplexný health check aplikácie.

```bash
# Základný health check
php bin/health-check.php

# S možnosťami
php bin/health-check.php --verbose
php bin/health-check.php --format=json
php bin/health-check.php --critical-only
php bin/health-check.php --exit-code
```

### `check-protocol-compliance.php`
Kontroluje protocol compliance.

```bash
php bin/check-protocol-compliance.php
```

## 🔧 Utility Scripts

### `route-list.php`
Zobrazí zoznam všetkých routes.

```bash
php bin/route-list.php
```

### `cleanup-project.sh`
Vyčistí projekt od zbytočných súborov.

```bash
./bin/cleanup-project.sh
```

## 🎯 Common Usage Patterns

### Development Setup
```bash
# 1. Generuj kľúče
php bin/generate-keys.php --env=development

# 2. Inicializuj databázy
php bin/init-all-databases.php --env=development

# 3. Nastav permissions
php bin/fix-permissions.php

# 4. Validuj environment
php bin/validate-env.php development
```

### Production Deployment
```bash
# 1. Vyčisti projekt
./scripts/cleanup-project.sh

# 2. Vytvor production build
php bin/build-production.php

# 3. Validuj environment
php bin/validate-env.php production

# 4. Nasaď cez FTPS
php bin/deploy-ftps.php production --backup

# 5. Skontroluj health
php bin/health-check.php --exit-code
```

### Maintenance Tasks
```bash
# Denná údržba
php bin/cache-clear.php all
php bin/log-cleanup.php
php bin/health-check.php

# Týždenná údržba
./bin/log-rotation.sh
php bin/audit-paths.php
./scripts/cleanup-project.sh
```

### Security Audit
```bash
# Kompletný security audit
php bin/audit-paths.php
php bin/cleanup-paths.php
php bin/validate-env.php production
php bin/health-check.php --critical-only
```

## ⚙️ Script Configuration

### Environment Variables
Skripty používajú tieto environment variables:

```bash
# Deployment
DEPLOY_HOST=your-server.com
DEPLOY_USERNAME=username
DEPLOY_PASSWORD=password
DEPLOY_REMOTE_PATH=/public_html

# Health Check
DEPLOY_VERIFY_URL=https://your-domain.com/health

# Notifications
DEPLOY_NOTIFY_EMAIL=admin@your-domain.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

### Configuration Files
```bash
config/deploy/production.php    # Production deployment config
config/deploy/staging.php       # Staging deployment config
.env.production                 # Production environment
.env.staging                    # Staging environment
```

## 🔍 Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix permissions
chmod +x bin/*.php
chmod +x bin/*.sh
chmod +x scripts/*.sh

# Fix directory permissions
php bin/fix-permissions.php
```

#### Database Connection Issues
```bash
# Check database connectivity
php bin/health-check.php --critical-only

# Reinitialize databases
php bin/init-all-databases.php --env=production
```

#### Cache Issues
```bash
# Clear all cache
php bin/cache-clear.php all --force

# Check cache status
php bin/health-check.php --verbose
```

#### Deployment Issues
```bash
# Test deployment (dry run)
php bin/deploy-ftps.php production --dry-run

# Check environment
php bin/validate-env.php production

# Verify health after deployment
php bin/health-check.php --exit-code
```

## 📋 Scripts Checklist

### Daily Operations
- [ ] `php bin/health-check.php` - Health monitoring
- [ ] `php bin/cache-clear.php logs` - Clear old logs

### Weekly Operations
- [ ] `php bin/cache-clear.php all` - Full cache clear
- [ ] `./bin/log-rotation.sh` - Rotate logs
- [ ] `php bin/audit-paths.php` - Security audit

### Monthly Operations
- [ ] `./scripts/cleanup-project.sh` - Project cleanup
- [ ] `php bin/validate-env.php production` - Environment audit

### Before Deployment
- [ ] `php bin/validate-env.php production` - Validate config
- [ ] `php bin/build-production.php` - Create build
- [ ] `php bin/deploy-ftps.php production --dry-run` - Test deployment
- [ ] `php bin/deploy-ftps.php production --backup` - Deploy
- [ ] `php bin/health-check.php --exit-code` - Verify deployment

## 🔗 Related Documentation

- [Scripts Audit Guide](../docs/guides/scripts-audit.md)
- [Deployment Guide](../docs/DEPLOYMENT.md)
- [Environment Setup](../docs/guides/environment-setup.md)
- [Security Best Practices](../docs/guides/security-practices.md)
