# Scripts Organization Guide

Reorganizácia scriptov v HDM Boot pre lepšiu konzistentnosť a prehľadnosť.

## 🎯 Aktuálny problém

**Nekonzistentná štruktúra:**
```
bin/                    # 20 scriptov (PHP + bash)
├── *.php              # PHP skripty
├── *.sh               # Bash skripty
└── scripts/           # Vnorený adresár
    └── check-paths.sh # 1 bash script

scripts/               # Root level
└── cleanup-project.sh # 1 bash script
```

**Problémy:**
- ❌ **Mätúca štruktúra** - skripty na 3 miestach
- ❌ **Nekonzistentné umiestnenie** - podobné skripty na rôznych miestach
- ❌ **Duplicitné adresáre** - `bin/scripts/` a `scripts/`
- ❌ **Nejasné rozdelenie** - nie je jasné, čo kam patrí

## 🏗️ Navrhovaná štruktúra

### **Variant A: Všetko v bin/ (Odporúčané)**
```
bin/                   # Všetky executable skripty
├── README.md         # Dokumentácia scriptov
├── *.php            # PHP skripty
└── *.sh             # Bash skripty
```

### **Variant B: Rozdelenie podľa typu**
```
bin/                   # PHP skripty (hlavné)
├── README.md
└── *.php

scripts/              # Bash skripty (pomocné)
├── README.md
└── *.sh
```

### **Variant C: Rozdelenie podľa účelu**
```
bin/                   # Core aplikačné skripty
├── setup/            # Setup a inicializácia
├── maintenance/      # Údržba a cleanup
├── deployment/       # Build a deployment
└── monitoring/       # Health check a monitoring

scripts/              # Project-level skripty
├── development/      # Development utilities
├── ci-cd/           # CI/CD skripty
└── tools/           # Pomocné nástroje
```

## 🎯 Odporúčané riešenie: Variant A

**Dôvody:**
- ✅ **Jednoduchosť** - všetko na jednom mieste
- ✅ **Konzistentnosť** - štandardná Unix konvencia
- ✅ **Prehľadnosť** - ľahko nájsť všetky skripty
- ✅ **PATH friendly** - ľahko pridať do PATH

## 🔄 Migration Plan

### **Krok 1: Presun scriptov**
```bash
# Presun z scripts/ do bin/
mv scripts/cleanup-project.sh bin/

# Presun z bin/scripts/ do bin/
mv bin/scripts/check-paths.sh bin/

# Odstránenie prázdnych adresárov
rmdir bin/scripts/
rmdir scripts/
```

### **Krok 2: Aktualizácia dokumentácie**
```bash
# Aktualizovať všetky odkazy v dokumentácii
docs/guides/scripts-audit.md
docs/guides/scripts-organization.md
bin/README.md
```

### **Krok 3: Aktualizácia scriptov**
```bash
# Aktualizovať odkazy v scriptoch, ktoré volajú iné skripty
# Napríklad v deployment scriptoch
```

### **Krok 4: Aktualizácia CI/CD**
```bash
# Aktualizovať CI/CD pipeline ak používa skripty
.github/workflows/
.gitlab-ci.yml
```

## 📁 Finálna štruktúra

### **Po reorganizácii:**
```
bin/                           # Všetky executable skripty
├── README.md                 # Dokumentácia scriptov
│
├── # Setup & Initialization
├── init-all-databases.php
├── init-user-db.php
├── init-mark-db.php
├── init-system-db.php
├── generate-keys.php
│
├── # Security & Audit
├── audit-paths.php
├── check-paths.sh           # Moved from bin/scripts/
├── cleanup-paths.php
├── validate-env.php
│
├── # Maintenance & Cleanup
├── cache-clear.php
├── cleanup-project.sh       # Moved from scripts/
├── fix-permissions.php
├── fix-permissions.sh
├── log-cleanup.php
├── log-rotation.sh
│
├── # Build & Deployment
├── build-production.php
├── deploy-ftps.php
│
├── # Monitoring & Health
├── health-check.php
├── check-protocol-compliance.php
│
└── # Utilities
    ├── route-list.php
    └── ...
```

### **Výhody novej štruktúry:**
- ✅ **Jeden adresár** pre všetky skripty
- ✅ **Jasné pomenovanie** - všetko v `bin/`
- ✅ **Ľahká navigácia** - `ls bin/` ukáže všetko
- ✅ **PATH friendly** - `export PATH=$PATH:./bin`
- ✅ **Štandardná konvencia** - Unix/Linux štandard

## 🔧 Implementation

### **Migration Script**
```bash
#!/bin/bash
# scripts/reorganize-scripts.sh

echo "🔄 Reorganizing scripts structure..."

# Create backup
echo "📦 Creating backup..."
tar -czf scripts-backup-$(date +%Y%m%d_%H%M%S).tar.gz bin/ scripts/

# Move scripts to bin/
echo "📁 Moving scripts to bin/..."

# Move from scripts/ to bin/
if [ -f "scripts/cleanup-project.sh" ]; then
    mv scripts/cleanup-project.sh bin/
    echo "  ✅ Moved scripts/cleanup-project.sh -> bin/"
fi

# Move from bin/scripts/ to bin/
if [ -f "bin/scripts/check-paths.sh" ]; then
    mv bin/scripts/check-paths.sh bin/
    echo "  ✅ Moved bin/scripts/check-paths.sh -> bin/"
fi

# Remove empty directories
if [ -d "bin/scripts" ] && [ -z "$(ls -A bin/scripts)" ]; then
    rmdir bin/scripts
    echo "  🗑️  Removed empty bin/scripts/"
fi

if [ -d "scripts" ] && [ -z "$(ls -A scripts)" ]; then
    rmdir scripts
    echo "  🗑️  Removed empty scripts/"
fi

# Update permissions
echo "🔐 Updating permissions..."
chmod +x bin/*.sh
chmod +x bin/*.php

echo "✅ Scripts reorganization completed!"
echo ""
echo "📊 Final structure:"
ls -la bin/
```

### **Documentation Updates**
```bash
# Update all documentation references
find docs/ -name "*.md" -exec sed -i 's|scripts/cleanup-project.sh|bin/cleanup-project.sh|g' {} \;
find docs/ -name "*.md" -exec sed -i 's|bin/scripts/check-paths.sh|bin/check-paths.sh|g' {} \;

# Update README files
sed -i 's|scripts/cleanup-project.sh|bin/cleanup-project.sh|g' bin/README.md
```

## 📋 Migration Checklist

### **Pre-migration:**
- [ ] Backup current scripts structure
- [ ] Review all script dependencies
- [ ] Check CI/CD pipeline references
- [ ] Notify team about changes

### **Migration:**
- [ ] Run migration script
- [ ] Verify all scripts moved correctly
- [ ] Update file permissions
- [ ] Test script execution

### **Post-migration:**
- [ ] Update documentation
- [ ] Update CI/CD configurations
- [ ] Test all scripts functionality
- [ ] Remove backup after verification

### **Verification:**
- [ ] All scripts in bin/ directory
- [ ] No duplicate scripts
- [ ] All scripts executable
- [ ] Documentation updated
- [ ] CI/CD pipeline working

## 🎯 Benefits

### **Before (Problematic):**
```
❌ 3 different locations for scripts
❌ Confusing structure
❌ Hard to find specific scripts
❌ Inconsistent organization
```

### **After (Clean):**
```
✅ Single location for all scripts
✅ Clear, consistent structure  
✅ Easy script discovery
✅ Standard Unix convention
✅ PATH-friendly organization
```

## 🔗 Related Documentation

- [Scripts Audit Guide](scripts-audit.md)
- [Deployment Guide](../DEPLOYMENT.md)
- [Environment Setup](environment-setup.md)
- [Project Cleanup Guide](project-cleanup.md)
