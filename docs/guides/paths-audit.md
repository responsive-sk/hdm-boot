# Paths Audit Guide

Komplexný audit paths systému v HDM Boot aplikácii.

## 🎯 Audit Overview

Audit všetkých aspektov paths systému:
- **Konfigurácia paths** v `config/paths.php`
- **PathsFactory** implementácia
- **Slim4-paths** package použitie
- **Directory creation** patterns
- **Security implications** paths handling

## 📁 Paths Configuration Audit

### **config/paths.php Analysis:**

```php
// AKTUÁLNA KONFIGURÁCIA:
return [
    'base_path' => __DIR__ . '/..',  // ✅ Správne - project root
    
    'paths' => [
        // Core directories
        'var'       => $basePath . '/var',           // ✅ Správne
        'storage'   => $basePath . '/var/storage',   // ✅ Správne  
        'public'    => $basePath . '/public',        // ✅ Správne
        'config'    => $basePath . '/config',        // ✅ Správne
        'templates' => $basePath . '/templates',     // ✅ Správne
        
        // Var subdirectories  
        'logs'      => $basePath . '/var/logs',      // ✅ Správne
        'cache'     => $basePath . '/var/cache',     // ✅ Správne
        'sessions'  => $basePath . '/var/sessions',  // ✅ Správne
        'uploads'   => $basePath . '/var/uploads',   // ✅ Správne
        
        // Content directories
        'content'   => $basePath . '/content',       // ✅ Správne
        'articles'  => $basePath . '/content/articles', // ✅ Správne
        
        // Public subdirectories
        'css'       => $basePath . '/public/css',    // ✅ Správne
        'js'        => $basePath . '/public/js',     // ✅ Správne
        'images'    => $basePath . '/public/images', // ✅ Správne
        'fonts'     => $basePath . '/public/fonts',  // ✅ Správne
        'media'     => $basePath . '/public/media',  // ✅ Správne
        
        // Security directories
        'keys'      => $basePath . '/var/keys',      // ✅ Správne (nie public)
        
        // Data directories  
        'exports'   => $basePath . '/var/exports',   // ✅ Správne
        'imports'   => $basePath . '/var/imports',   // ✅ Správne
        
        // Localization
        'lang'         => $basePath . '/lang',           // ✅ Správne
        'translations' => $basePath . '/lang/translations', // ✅ Správne
        'locales'      => $basePath . '/lang/locales',      // ✅ Správne
        
        // Development
        'scripts'   => $basePath . '/bin',           // ✅ Správne (reorganizované)
        'bin'       => $basePath . '/bin',           // ✅ Správne
    ],
    
    // PROBLEMATICKÁ SEKCIA:
    'auto_create' => [  // ❌ NEPOUŽÍVA SA!
        'var', 'logs', 'cache', 'uploads', 'storage', 'sessions',
        'templates', 'layouts', 'partials',
        'content', 'articles', 'docs_content', 'orbit',
        'css', 'js', 'images', 'fonts', 'media',
        'keys', 'exports', 'imports',
        'lang', 'translations', 'locales',
        'scripts', 'bin',
    ],
];
```

### **Konfiguračné problémy:**

1. **`auto_create` sa nepoužíva** - mŕtvy kód
2. **Niektoré paths duplicitné** - `scripts` a `bin`
3. **Chýbajúce paths** pre niektoré moduly

## 🏭 PathsFactory Audit

### **PathsFactory Implementation:**

```php
// src/SharedKernel/Services/PathsFactory.php

final class PathsFactory
{
    private static ?Paths $instance = null;  // ✅ Singleton pattern
    
    public static function create(): Paths   // ✅ Main factory method
    public static function createFromConfig(): Paths  // ✅ Config loader
    private static function findConfigFile(): string  // ✅ Auto-discovery
    public static function reset(): void     // ✅ Testing support
    public static function setInstance(Paths $paths): void  // ✅ Testing support
}
```

### **PathsFactory Issues:**

#### **✅ Správne implementované:**
- Singleton pattern pre performance
- Auto-discovery config súboru
- Testing support (reset/setInstance)
- Error handling pre missing config

#### **⚠️ Potenciálne problémy:**
- Žiadna validácia paths existencie
- Žiadne auto-creation adresárov
- Žiadne permission handling

## 📦 Slim4-Paths Package Audit

### **Package Methods Usage:**

```php
// SPRÁVNE POUŽITIE:
$paths->storage('database.db')     // ✅ Named method
$paths->cache('templates')         // ✅ Named method  
$paths->logs('app.log')           // ✅ Named method
$paths->public('css/style.css')   // ✅ Named method

// PROBLEMATICKÉ POUŽITIE:
$paths->path('cache/container')    // ❌ Relatívna cesta!
$paths->path('storage/files')      // ❌ Relatívna cesta!
```

### **Problematické miesta v kóde:**

#### **❌ Slim4Container.php - RIADOK 50:**
```php
$cacheDir = $this->paths->path('cache/container');  // PROBLÉM!
```

#### **❌ Language module - RIADOK 211:**
```php
$cacheDir = $paths->path('cache/translations');     // PROBLÉM!
```

#### **❌ Template module - RIADOK 272-273:**
```php
$paths->cache() . '/templates',  // String concatenation - PROBLÉM!
$paths->cache() . '/twig',       // String concatenation - PROBLÉM!
```

## 🏗️ Directory Creation Audit

### **Audit všetkých `mkdir()` volaní:**

#### **✅ Bezpečné implementácie:**

**1. Storage module:**
```php
$contentDir = $paths->base() . '/content';  // ✅ Používa base()
$directories = [
    $contentDir,
    $contentDir . '/articles',
    $contentDir . '/docs',
];
```

**2. PermissionManager:**
```php
$securePath = $this->paths->path($path);  // ✅ Používa konfiguráciu
```

#### **❌ Problematické implementácie:**

**1. Slim4Container:**
```php
$cacheDir = $this->paths->path('cache/container');  // ❌ Relatívna cesta
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);  // ❌ Nesprávne permissions
}
```

**2. Language module:**
```php
$cacheDir = $paths->path('cache/translations');  // ❌ Relatívna cesta
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0o755, true);
}
```

**3. Template module:**
```php
$directories = [
    $paths->templates(),
    $paths->cache() . '/templates',  // ❌ String concatenation
    $paths->cache() . '/twig',       // ❌ String concatenation
];
```

## 🚨 Security Audit

### **Bezpečnostné riziká:**

#### **1. Public Directory Exposure:**
```bash
# RIZIKÁ:
public/storage/     # ❌ Databázy verejne prístupné!
public/cache/       # ❌ Cache súbory verejne prístupné!
public/keys/        # ❌ Kľúče verejne prístupné!
```

#### **2. Permission Issues:**
```php
mkdir($cacheDir, 0777, true);  // ❌ Príliš permisívne!
mkdir($cacheDir, 0755, true);  // ✅ Správne
```

#### **3. Path Traversal Risks:**
```php
$paths->path('../../../etc/passwd');  // ❌ Možný path traversal
$paths->storage('../../sensitive');   // ❌ Možný path traversal
```

### **Bezpečnostné opatrenia:**

#### **✅ Implementované:**
- `getPath()` method s path traversal protection
- Paths validation v `SecurePathHelper`
- `.htaccess` protection pre `var/` directory

#### **❌ Chýbajúce:**
- Auto-validation paths pri vytváraní
- Centralizované permission management
- Monitoring nesprávnych path usage

## 📊 Paths Usage Statistics

### **Audit všetkých paths usage:**

```bash
# SPRÁVNE POUŽITIE (named methods):
$paths->storage()    - 15 použití  ✅
$paths->cache()      - 12 použití  ✅  
$paths->logs()       - 8 použití   ✅
$paths->public()     - 6 použití   ✅
$paths->config()     - 4 použití   ✅

# PROBLEMATICKÉ POUŽITIE:
$paths->path()       - 8 použití   ❌ (6 problematických)
String concatenation - 5 použití   ❌
```

### **Najčastejšie chyby:**

1. **Relatívne cesty s `path()`** - 6 prípadov
2. **String concatenation** - 5 prípadov  
3. **Nesprávne permissions** - 3 prípady
4. **Missing validation** - všetky prípady

## 🔧 Odporúčané opravy

### **Priority P0 (Kritické):**

1. **Oprav Slim4Container.php:**
```php
// ❌ PRED:
$cacheDir = $this->paths->path('cache/container');

// ✅ PO:
$cacheDir = $this->paths->cache('container');
```

2. **Oprav Language module:**
```php
// ❌ PRED:
$cacheDir = $paths->path('cache/translations');

// ✅ PO:
$cacheDir = $paths->cache('translations');
```

3. **Odstráň public/storage:**
```bash
rm -rf public/storage
```

### **Priority P1 (Dôležité):**

1. **Oprav Template module:**
```php
// ❌ PRED:
$paths->cache() . '/templates'

// ✅ PO:
$paths->cache('templates')
```

2. **Odstráň auto_create dead code:**
```php
// Zakomentuj alebo odstráň auto_create sekciu
```

3. **Pridaj paths validation:**
```php
// Validácia že paths sú v správnych adresároch
```

### **Priority P2 (Vylepšenia):**

1. **Centralizované permission management**
2. **Paths monitoring a alerting**
3. **Auto-creation s validáciou**

## 📋 Paths Audit Checklist

### **Konfigurácia:**
- [x] ✅ `config/paths.php` existuje
- [x] ✅ Base path správne nastavený
- [x] ✅ Storage paths mimo public/
- [ ] ❌ Auto_create sa nepoužíva
- [ ] ❌ Duplicitné paths (scripts/bin)

### **Implementation:**
- [x] ✅ PathsFactory singleton
- [x] ✅ Config auto-discovery
- [ ] ❌ Paths validation chýba
- [ ] ❌ Auto-creation chýba

### **Usage:**
- [x] ✅ Named methods používané
- [ ] ❌ Relatívne paths s path()
- [ ] ❌ String concatenation
- [ ] ❌ Nesprávne permissions

### **Security:**
- [ ] ❌ Public/storage existuje
- [x] ✅ Var/.htaccess protection
- [ ] ❌ Path traversal validation
- [ ] ❌ Permission standardization

## 🎯 Finálne odporúčania

### **Immediate Actions:**
1. **Spusti path security fix**
2. **Oprav všetky `path()` usage**
3. **Odstráň auto_create dead code**
4. **Validuj všetky directory creation**

### **Long-term Improvements:**
1. **Implementuj centralizované directory management**
2. **Pridaj paths monitoring**
3. **Vytvor paths validation middleware**
4. **Automatizuj security audits**

## 🔗 Súvisiace dokumenty

- [Path Security Fix Guide](path-security-fix.md)
- [Auto-Create Audit Guide](auto-create-audit.md)
- [Paths Service Guide](../PATHS_SERVICE_GUIDE.md)
- [Security Best Practices](security-practices.md)
