# Path Security Fix Guide

Kritická oprava bezpečnostného problému s `public/storage` adresárom.

## 🚨 Bezpečnostný problém

**KRITICKÉ:** `public/storage` adresár je **verejne prístupný** cez web!

### **Problém:**
```
public/storage/           ❌ BEZPEČNOSTNÉ RIZIKO!
├── database.db          ❌ Verejne prístupné!
├── user-data.json       ❌ Verejne prístupné!
└── sensitive-files/     ❌ Verejne prístupné!
```

### **Riziká:**
- ❌ **Databázy prístupné** cez `https://domain.com/storage/database.db`
- ❌ **Používateľské dáta** verejne dostupné
- ❌ **Konfiguračné súbory** exponované
- ❌ **Citlivé informácie** prístupné bez autentifikácie

## 🎯 Správna štruktúra

### **Bezpečná konfigurácia:**
```
var/storage/             ✅ BEZPEČNÉ (mimo public/)
├── database.db          ✅ Chránené
├── user-data.json       ✅ Chránené  
└── sensitive-files/     ✅ Chránené

public/                  ✅ Iba verejné súbory
├── index.php           ✅ Entry point
├── assets/             ✅ CSS, JS, obrázky
└── uploads/            ✅ Verejné uploads (ak potrebné)
```

## 🔧 Oprava problému

### **1. Identifikácia problémových miest:**

#### **A) Deployment konfigurácia:**
```php
// config/deploy/production.php - RIADOK 73
'create_directories' => [
    'var/logs',
    'var/cache', 
    'var/storage',
    'var/sessions',
    'public/storage',  // ❌ PROBLÉM!
],
```

#### **B) Environment setup:**
```bash
# docs/guides/environment-setup.md - RIADOK 374
sudo chmod -R 755 $DEPLOY_DIR/public/storage  # ❌ PROBLÉM!
```

#### **C) Cleanup script:**
```php
// bin/cleanup-paths.php - RIADOK 70
'public/storage',  // ❌ Označené na odstránenie, ale stále sa vytvára
```

### **2. Oprava deployment konfigurácie:**

```php
// config/deploy/production.php
'create_directories' => [
    'var/logs',
    'var/cache',
    'var/storage',        // ✅ Správne umiestnenie
    'var/sessions',
    // 'public/storage',  // ❌ ODSTRÁNENÉ!
],
```

### **3. Oprava environment setup:**

```bash
# Namiesto:
sudo chmod -R 755 $DEPLOY_DIR/public/storage  # ❌

# Použiť:
sudo chmod -R 755 $DEPLOY_DIR/var/storage     # ✅
```

### **4. Odstránenie existujúceho public/storage:**

```bash
# Bezpečné odstránenie
rm -rf public/storage
```

## 🛠️ Automatická oprava

### **Path Security Fix Script:**

```bash
#!/bin/bash
# bin/fix-path-security.sh

echo "🔒 HDM Boot Path Security Fix"
echo "============================"
echo ""

# 1. Backup public/storage if exists
if [ -d "public/storage" ]; then
    echo "📦 Backing up public/storage..."
    BACKUP_DIR="var/backups/public-storage-$(date +%Y%m%d_%H%M%S)"
    mkdir -p "var/backups"
    mv "public/storage" "$BACKUP_DIR"
    echo "  ✅ Backed up to: $BACKUP_DIR"
fi

# 2. Ensure var/storage exists
echo "📁 Creating secure var/storage..."
mkdir -p var/storage
chmod 755 var/storage
echo "  ✅ Created var/storage with secure permissions"

# 3. Fix deployment config
echo "🔧 Fixing deployment configuration..."
sed -i "s/'public\/storage',/\/\/ 'public\/storage', \/\/ REMOVED - Security fix/" config/deploy/production.php
sed -i "s/'public\/storage',/\/\/ 'public\/storage', \/\/ REMOVED - Security fix/" config/deploy/staging.php
echo "  ✅ Updated deployment configs"

# 4. Create .htaccess protection for var/
echo "🛡️ Creating .htaccess protection..."
cat > var/.htaccess << 'EOF'
# Deny all access to var/ directory
Deny from all
<Files ~ "^\.ht">
    Order allow,deny
    Deny from all
</Files>
EOF
echo "  ✅ Created var/.htaccess protection"

# 5. Verify security
echo "🔍 Verifying security..."
if [ ! -d "public/storage" ]; then
    echo "  ✅ public/storage removed"
else
    echo "  ❌ public/storage still exists!"
fi

if [ -d "var/storage" ]; then
    echo "  ✅ var/storage exists"
else
    echo "  ❌ var/storage missing!"
fi

echo ""
echo "✅ Path security fix completed!"
echo ""
echo "📋 Summary:"
echo "  • public/storage removed (backed up)"
echo "  • var/storage secured"
echo "  • Deployment configs updated"
echo "  • .htaccess protection added"
```

## 🔍 Verifikácia bezpečnosti

### **Security Check Script:**

```bash
#!/bin/bash
# bin/check-path-security.sh

echo "🔍 HDM Boot Path Security Check"
echo "==============================="
echo ""

ISSUES=0

# Check 1: public/storage should not exist
if [ -d "public/storage" ]; then
    echo "❌ CRITICAL: public/storage exists (security risk!)"
    ((ISSUES++))
else
    echo "✅ public/storage does not exist"
fi

# Check 2: var/storage should exist
if [ -d "var/storage" ]; then
    echo "✅ var/storage exists"
else
    echo "❌ WARNING: var/storage missing"
    ((ISSUES++))
fi

# Check 3: var/.htaccess should exist
if [ -f "var/.htaccess" ]; then
    echo "✅ var/.htaccess protection exists"
else
    echo "⚠️  WARNING: var/.htaccess missing"
    ((ISSUES++))
fi

# Check 4: Deployment configs
if grep -q "public/storage" config/deploy/*.php; then
    echo "❌ WARNING: public/storage found in deployment configs"
    ((ISSUES++))
else
    echo "✅ Deployment configs clean"
fi

# Check 5: Web accessibility test
if command -v curl &> /dev/null; then
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost/storage/" | grep -q "403\|404"; then
        echo "✅ Storage not web accessible"
    else
        echo "❌ CRITICAL: Storage may be web accessible!"
        ((ISSUES++))
    fi
fi

echo ""
if [ $ISSUES -eq 0 ]; then
    echo "✅ All path security checks passed!"
    exit 0
else
    echo "❌ Found $ISSUES security issues!"
    echo "💡 Run: ./bin/fix-path-security.sh"
    exit 1
fi
```

## 📋 Oprava checklist

### **Immediate Actions:**
- [ ] Spustiť `./bin/fix-path-security.sh`
- [ ] Overiť `./bin/check-path-security.sh`
- [ ] Aktualizovať deployment configs
- [ ] Odstrániť `public/storage`

### **Code Changes:**
- [ ] Opraviť `config/deploy/production.php`
- [ ] Opraviť `config/deploy/staging.php`
- [ ] Aktualizovať `environment-setup.md`
- [ ] Pridať `.htaccess` ochranu

### **Testing:**
- [ ] Overiť, že storage nie je web accessible
- [ ] Testovať aplikáciu functionality
- [ ] Skontrolovať databázové pripojenia
- [ ] Validovať file uploads

### **Documentation:**
- [ ] Aktualizovať security guides
- [ ] Pridať path security best practices
- [ ] Dokumentovať správnu štruktúru

## 🎯 Dlhodobé riešenie

### **1. Path Configuration Review:**
- Audit všetkých path konfigurácií
- Implementovať path security validáciu
- Pridať automated security checks

### **2. Deployment Security:**
- Validovať deployment targets
- Implementovať security pre-checks
- Pridať post-deployment verification

### **3. Monitoring:**
- Monitorovať web accessibility
- Alerting pre security issues
- Regular security audits

## 🔗 Súvisiace dokumenty

- [Security Best Practices](security-practices.md)
- [Environment Setup](environment-setup.md)
- [Deployment Guide](../DEPLOYMENT.md)
- [Path Security Audit](../bin/audit-paths.php)
