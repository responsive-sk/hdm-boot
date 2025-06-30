# Scripts Audit Guide

Komplexný audit všetkých scriptov v HDM Boot aplikácii.

## 🎯 Scripts Overview

HDM Boot obsahuje **18 scriptov** rozdelených do kategórií:

- **Setup Scripts** - Inicializácia a konfigurácia
- **Maintenance Scripts** - Údržba a cleanup
- **Security Scripts** - Bezpečnostné kontroly
- **Build Scripts** - Production build a deployment
- **Utility Scripts** - Pomocné nástroje

## 📁 Scripts Structure

```
bin/                           # Všetky executable skripty
├── audit-paths.php           ✅ Security audit
├── build-production.php      ✅ Production build
├── cache-clear.php           ✅ Cache management
├── check-paths.sh            ✅ Path security check
├── check-protocol-compliance.php ✅ Protocol check
├── cleanup-paths.php         ✅ Path cleanup
├── cleanup-project.sh        ✅ Project cleanup
├── deploy-ftps.php           ✅ FTPS deployment
├── fix-permissions.php       ✅ Permissions fix
├── fix-permissions.sh        ✅ Permissions script
├── generate-keys.php         ✅ Key generation
├── health-check.php          ✅ Health monitoring
├── init-all-databases.php    ✅ DB initialization
├── init-mark-db.php          ✅ Mark DB init
├── init-system-db.php        ✅ System DB init
├── init-user-db.php          ✅ User DB init
├── log-cleanup.php           ✅ Log cleanup
├── log-rotation.sh           ✅ Log rotation
├── reorganize-scripts.sh     ✅ Scripts reorganization
├── route-list.php            ✅ Route listing
└── validate-env.php          ✅ Environment validation
```

## 🔍 Detailed Scripts Audit

### **1. Setup & Initialization Scripts**

#### **init-all-databases.php** ✅ **GOOD**
```bash
# Účel: Inicializuje všetky databázy
php bin/init-all-databases.php [--env=environment]
```
**Status:** ✅ Funkčný, dobre dokumentovaný

#### **init-mark-db.php** ✅ **GOOD**
```bash
# Účel: Inicializuje Mark admin databázu
php bin/init-mark-db.php
```
**Status:** ✅ Špecializovaný pre Mark systém

#### **init-system-db.php** ✅ **GOOD**
```bash
# Účel: Inicializuje systémovú databázu
php bin/init-system-db.php
```
**Status:** ✅ Systémová konfigurácia

#### **init-user-db.php** ✅ **GOOD**
```bash
# Účel: Inicializuje user databázu
php bin/init-user-db.php
```
**Status:** ✅ User management

#### **generate-keys.php** ✅ **EXCELLENT**
```bash
# Účel: Generuje bezpečnostné kľúče
php bin/generate-keys.php [--env=environment]
```
**Status:** ✅ Kritický pre bezpečnosť

### **2. Security Scripts**

#### **audit-paths.php** ✅ **EXCELLENT**
```bash
# Účel: Audituje path security
php bin/audit-paths.php
```
**Status:** ✅ Bezpečnostný audit

#### **check-paths.sh** ✅ **GOOD**
```bash
# Účel: Bash verzia path check
./bin/scripts/check-paths.sh
```
**Status:** ✅ Shell implementácia

#### **cleanup-paths.php** ✅ **GOOD**
```bash
# Účel: Čistí nebezpečné paths
php bin/cleanup-paths.php
```
**Status:** ✅ Path sanitization

#### **validate-env.php** ✅ **EXCELLENT**
```bash
# Účel: Validuje environment konfiguráciu
php bin/validate-env.php [environment]
```
**Status:** ✅ Environment validation

### **3. Maintenance Scripts**

#### **fix-permissions.php** ✅ **GOOD**
```bash
# Účel: Opravuje file permissions
php bin/fix-permissions.php
```
**Status:** ✅ PHP implementácia

#### **fix-permissions.sh** ✅ **GOOD**
```bash
# Účel: Bash verzia permissions fix
./bin/fix-permissions.sh
```
**Status:** ✅ Shell implementácia

#### **log-cleanup** ⚠️ **NEEDS REVIEW**
```bash
# Účel: Cleanup starých logov
./bin/log-cleanup
```
**Issues:** 
- ❌ Žiadna file extension
- ❌ Neznámy formát (PHP/Bash?)
- ⚠️ Potrebuje audit

#### **log-rotation** ⚠️ **NEEDS REVIEW**
```bash
# Účel: Rotácia log súborov
./bin/log-rotation
```
**Issues:**
- ❌ Žiadna file extension
- ❌ Neznámy formát
- ⚠️ Potrebuje audit

#### **cleanup-project.sh** ✅ **EXCELLENT**
```bash
# Účel: Vyčistí projekt od zbytočných súborov
./scripts/cleanup-project.sh
```
**Status:** ✅ Novo vytvorený, kompletný

### **4. Build & Deployment Scripts**

#### **build-production.php** ✅ **GOOD**
```bash
# Účel: Vytvorí production build
php bin/build-production.php
```
**Status:** ✅ Production ready

### **5. Utility Scripts**

#### **route-list.php** ✅ **GOOD**
```bash
# Účel: Zobrazí zoznam routes
php bin/route-list.php
```
**Status:** ✅ Development utility

#### **check-protocol-compliance.php** ✅ **GOOD**
```bash
# Účel: Kontroluje protocol compliance
php bin/check-protocol-compliance.php
```
**Status:** ✅ Protocol validation

## ⚠️ Issues Found

### **1. Missing File Extensions**
```bash
# Problematické súbory:
bin/log-cleanup          # Neznámy typ
bin/log-rotation         # Neznámy typ
```

**Riešenie:** Pridať `.php` alebo `.sh` extension

### **2. Missing Scripts**
```bash
# Chýbajúce skripty pre production:
bin/deploy-ftps.php      # FTPS deployment
bin/backup-create.php    # Backup creation
bin/health-check.php     # Health monitoring
bin/cache-clear.php      # Cache management
```

### **3. Documentation Gaps**
- ❌ Žiadny centrálny scripts README
- ❌ Chýbajúce usage examples
- ❌ Žiadne error handling docs

## 🔧 Recommended Fixes

### **1. Fix File Extensions**
```bash
# Rename files with proper extensions
mv bin/log-cleanup bin/log-cleanup.sh
mv bin/log-rotation bin/log-rotation.sh

# Or if they are PHP:
mv bin/log-cleanup bin/log-cleanup.php
mv bin/log-rotation bin/log-rotation.php
```

### **2. Create Missing Scripts**
```bash
# Production deployment
bin/deploy-ftps.php       # FTPS deployment script
bin/backup-create.php     # Database backup
bin/health-check.php      # Application health check
bin/cache-clear.php       # Cache management

# Development utilities
bin/dev-setup.php         # Development environment setup
bin/test-runner.php       # Test execution wrapper
```

### **3. Create Scripts Documentation**
```bash
# Documentation files needed:
bin/README.md             # Scripts overview
docs/guides/scripts-usage.md  # Usage guide
```

## 📋 Scripts Audit Checklist

### **Existing Scripts Review:**
- [x] ✅ **audit-paths.php** - Security audit
- [x] ✅ **build-production.php** - Production build
- [x] ✅ **check-protocol-compliance.php** - Protocol check
- [x] ✅ **cleanup-paths.php** - Path cleanup
- [x] ✅ **fix-permissions.php** - Permissions fix
- [x] ✅ **fix-permissions.sh** - Permissions script
- [x] ✅ **generate-keys.php** - Key generation
- [x] ✅ **init-all-databases.php** - DB initialization
- [x] ✅ **init-mark-db.php** - Mark DB
- [x] ✅ **init-system-db.php** - System DB
- [x] ✅ **init-user-db.php** - User DB
- [ ] ⚠️ **log-cleanup** - Needs extension
- [ ] ⚠️ **log-rotation** - Needs extension
- [x] ✅ **route-list.php** - Route listing
- [x] ✅ **validate-env.php** - Environment validation
- [x] ✅ **check-paths.sh** - Path security
- [x] ✅ **cleanup-project.sh** - Project cleanup

### **Missing Scripts to Create:**
- [ ] ❌ **deploy-ftps.php** - FTPS deployment
- [ ] ❌ **backup-create.php** - Backup creation
- [ ] ❌ **health-check.php** - Health monitoring
- [ ] ❌ **cache-clear.php** - Cache management
- [ ] ❌ **dev-setup.php** - Dev environment
- [ ] ❌ **test-runner.php** - Test wrapper

### **Documentation to Create:**
- [ ] ❌ **bin/README.md** - Scripts overview
- [ ] ❌ **scripts-usage.md** - Usage guide

## 🎯 Priority Actions

### **Priority P0 (Critical)**
1. **Fix file extensions** - log-cleanup, log-rotation
2. **Create deploy-ftps.php** - Pre FTPS deployment
3. **Create health-check.php** - Pre monitoring

### **Priority P1 (Important)**
1. **Create backup-create.php** - Pre production
2. **Create cache-clear.php** - Pre maintenance
3. **Create bin/README.md** - Pre documentation

### **Priority P2 (Nice to have)**
1. **Create dev-setup.php** - Pre development
2. **Create test-runner.php** - Pre testing
3. **Create scripts-usage.md** - Pre documentation

## 📊 Scripts Quality Score

| Kategória | Hodnotenie | Status |
|-----------|------------|--------|
| **Setup Scripts** | 5/5 | ✅ Excellent |
| **Security Scripts** | 4/4 | ✅ Excellent |
| **Maintenance Scripts** | 3/5 | ⚠️ Good |
| **Build Scripts** | 1/1 | ✅ Good |
| **Utility Scripts** | 2/2 | ✅ Good |
| **Documentation** | 0/3 | ❌ Missing |
| **CELKOM** | **15/20** | **⚠️ 75%** |

## 🔗 Next Steps

1. **Oprav file extensions** pre log skripty
2. **Vytvor chýbajúce production skripty**
3. **Pridaj dokumentáciu** pre všetky skripty
4. **Testuj všetky skripty** v development prostredí
5. **Vytvor FTPS deployment script** pre shared hosting

## 🔗 Ďalšie zdroje

- [Deployment Guide](../DEPLOYMENT.md)
- [Environment Setup](environment-setup.md)
- [Security Best Practices](security-practices.md)
- [Production Checklist](production-checklist.md)
