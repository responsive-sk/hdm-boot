# Production Build Guide

Komplexný sprievodca vytvorením a nasadením production buildu HDM Boot aplikácie.

## 🎯 Production Build Overview

HDM Boot poskytuje **automatizovaný production build** systém ktorý:

- ✅ **Optimalizuje kód** pre produkciu
- ✅ **Vyčistí development súbory**
- ✅ **Vytvorí deployment package**
- ✅ **Pripraví FTPS deployment**
- ✅ **Validuje konfiguráciu**

## 🏗️ Build Process

### **1. Príprava production buildu**

```bash
# 1. Vyčisti projekt
./bin/cleanup-project.sh

# 2. Validuj environment
php bin/validate-env.php production

# 3. Vygeneruj production kľúče
php bin/generate-keys.php --env=production

# 4. Vytvor production build
php bin/build-production.php
```

### **2. Výsledky buildu**

Po úspešnom builde máte:

```
build/
├── production/                    # Production directory
│   ├── DEPLOYMENT.md             # Deployment instructions
│   ├── .env.example              # Environment template
│   ├── bin/                      # Production scripts
│   ├── config/                   # Configuration files
│   ├── public/                   # Web root
│   ├── src/                      # Source code
│   ├── vendor/                   # Optimized dependencies
│   └── var/                      # Runtime directories
└── hdm-boot-production-*.zip     # Deployment package
```

## 📦 Build Specifications

### **Included in Production Build:**

#### **✅ Core Application:**
- `bin/` - Production scripts
- `config/` - Configuration files
- `public/` - Web accessible files
- `src/` - Application source code
- `vendor/` - Optimized Composer dependencies

#### **✅ Runtime Directories:**
- `var/logs/` - Log storage
- `var/cache/` - Cache storage
- `var/storage/` - File storage
- `var/sessions/` - Session storage

#### **✅ Configuration:**
- `.env.example` - Environment template
- `composer.json` - Dependency definition
- `DEPLOYMENT.md` - Deployment instructions

### **Excluded from Production Build:**

#### **❌ Development Files:**
- `tests/` - Test suite
- `docs/` - Documentation
- `.git/` - Version control
- `.github/` - CI/CD workflows

#### **❌ Development Dependencies:**
- PHPUnit testing framework
- PHPStan static analysis
- PHP CS Fixer code style
- Development tools

#### **❌ Environment Files:**
- `.env.dev` - Development environment
- `.env.testing` - Testing environment
- `.env.example` - Template (replaced)

#### **❌ Build Artifacts:**
- `node_modules/` - NPM dependencies
- `build/` - Previous builds
- `*.log` - Log files
- `*.cache` - Cache files

## 🔧 Build Configuration

### **Build Script Features:**

#### **📦 Composer Optimization:**
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

- ✅ **No dev dependencies** - Iba production packages
- ✅ **Optimized autoloader** - Rýchlejšie načítanie tried
- ✅ **Classmap authoritative** - Maximálna optimalizácia

#### **🗂️ File Structure Optimization:**
- ✅ **Proper permissions** - 755 pre directories, 644 pre files
- ✅ **Required directories** - Vytvorené všetky potrebné adresáre
- ✅ **Clean structure** - Žiadne zbytočné súbory

#### **📋 Documentation:**
- ✅ **DEPLOYMENT.md** - Podrobné deployment inštrukcie
- ✅ **.env.example** - Production environment template
- ✅ **File listing** - Kompletný zoznam súborov

## 🚀 FTPS Deployment

### **1. Konfigurácia FTPS:**

Aktualizuj `config/deploy/production.php`:

```php
return [
    'host' => 'your-hosting-server.com',
    'username' => 'your-username',
    'password' => 'your-password',
    'remote_path' => '/public_html',
    'verify_url' => 'https://your-domain.com/health',
];
```

### **2. Environment Variables:**

Nastav v `.env.production`:

```bash
# FTPS Deployment
DEPLOY_HOST=your-hosting-server.com
DEPLOY_USERNAME=your-username
DEPLOY_PASSWORD=your-password
DEPLOY_REMOTE_PATH=/public_html
DEPLOY_VERIFY_URL=https://your-domain.com/health
```

### **3. Deployment Commands:**

```bash
# Test deployment (dry run)
php bin/deploy-ftps.php production --dry-run

# Production deployment s backup
php bin/deploy-ftps.php production --backup

# Staging deployment
php bin/deploy-ftps.php staging --dry-run
```

## 📊 Build Statistics

### **Aktuálny production build:**

```
📦 Package: hdm-boot-production-2025-06-30-15-04-13.zip
📏 Size: ~12MB (optimized)
📁 Files: 1,247 files
⏱️ Build time: ~30 seconds
🗜️ Compression: ~60% size reduction
```

### **Optimalizácie:**

#### **🔥 Performance:**
- ✅ **Composer autoloader** optimalizovaný
- ✅ **OPcache ready** - Pripravené pre OPcache
- ✅ **Minimal file count** - Iba potrebné súbory
- ✅ **Compressed assets** - Optimalizované assets

#### **🔒 Security:**
- ✅ **No debug files** - Žiadne debug súbory
- ✅ **No test data** - Žiadne test dáta
- ✅ **Production keys** - Silné security kľúče
- ✅ **Secure permissions** - Správne file permissions

## 🏥 Health Check

### **Pre-deployment validation:**

```bash
# Validuj production environment
php bin/validate-env.php production

# Kompletný health check
php bin/health-check.php --exit-code

# JSON output pre monitoring
php bin/health-check.php --format=json
```

### **Post-deployment verification:**

```bash
# Verify deployment
curl -f https://your-domain.com/health

# Check application status
curl -f https://your-domain.com/api/status
```

## 📋 Production Build Checklist

### **Pre-build:**
- [ ] Projekt vyčistený (`./bin/cleanup-project.sh`)
- [ ] Environment validovaný (`php bin/validate-env.php production`)
- [ ] Security kľúče vygenerované (`php bin/generate-keys.php --env=production`)
- [ ] Tests prešli (`composer test`)

### **Build Process:**
- [ ] Production build vytvorený (`php bin/build-production.php`)
- [ ] ZIP package existuje
- [ ] DEPLOYMENT.md súbor prítomný
- [ ] File permissions správne

### **Pre-deployment:**
- [ ] FTPS konfigurácia nastavená
- [ ] .env.production nakonfigurovaný
- [ ] Deployment test (dry-run) úspešný
- [ ] Backup stratégia pripravená

### **Deployment:**
- [ ] FTPS deployment úspešný
- [ ] Health check prešiel
- [ ] Site accessibility overená
- [ ] Performance monitoring nastavené

### **Post-deployment:**
- [ ] Application logs monitorované
- [ ] Error rates sledované
- [ ] Performance metriky zbierané
- [ ] Backup schedule nastavený

## 🔧 Troubleshooting

### **Build Issues:**

#### **Composer Errors:**
```bash
# Clear composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor/
composer install
```

#### **Permission Errors:**
```bash
# Fix permissions
php bin/fix-permissions.php

# Manual fix
chmod -R 755 var/
chmod -R 644 config/
```

### **Deployment Issues:**

#### **FTPS Connection:**
```bash
# Test FTPS connection
php bin/deploy-ftps.php production --dry-run

# Check credentials
php bin/validate-env.php production
```

#### **File Upload Errors:**
```bash
# Check file permissions
ls -la build/production/

# Verify package integrity
unzip -t hdm-boot-production-*.zip
```

## 🎯 Best Practices

### **Build Management:**
- ✅ **Version builds** - Používaj timestamp v názvoch
- ✅ **Test builds** - Vždy testuj pred deployment
- ✅ **Clean builds** - Vyčisti pred každým buildom
- ✅ **Backup builds** - Uchovávaj predchádzajúce verzie

### **Security:**
- ✅ **Unique keys** - Rôzne kľúče pre každé prostredie
- ✅ **Secure storage** - Kľúče v password manageri
- ✅ **Regular rotation** - Pravidelná rotácia kľúčov
- ✅ **Access control** - Obmedzený prístup k production

### **Monitoring:**
- ✅ **Health checks** - Pravidelné health monitoring
- ✅ **Error tracking** - Sledovanie chýb
- ✅ **Performance monitoring** - Metriky výkonu
- ✅ **Log analysis** - Analýza logov

## 🔗 Ďalšie zdroje

- [FTPS Deployment Script](../bin/deploy-ftps.php)
- [Environment Setup](environment-setup.md)
- [Production Checklist](production-checklist.md)
- [Security Best Practices](security-practices.md)
