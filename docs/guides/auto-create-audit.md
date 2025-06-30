# Auto-Create Audit Guide

Audit `auto_create` funkcionality a oprava problémov s vytváraním adresárov.

## 🚨 Kritické zistenia

### **1. `auto_create` konfigurácia sa nepoužíva**
```php
// config/paths.php - RIADOK 86-94
'auto_create' => [
    'var', 'logs', 'cache', 'uploads', 'storage', 'sessions',
    // ... ❌ MŔTVY KÓD - nikde sa nepoužíva!
],
```

### **2. Skutočný problém: `$paths->path()` používa relatívne cesty**

#### **Problematické miesta:**

**A) Slim4Container.php - RIADOK 50-53:**
```php
$cacheDir = $this->paths->path('cache/container');
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);  // ❌ Vytvorí public/cache/container!
}
```

**B) Language module - RIADOK 211:**
```php
$cacheDir = $paths->path('cache/translations');  // ❌ Vytvorí public/cache/translations!
```

**C) Template module - RIADOK 272-273:**
```php
$paths->cache() . '/templates',  // ❌ Môže vytvoriť public/cache/templates!
$paths->cache() . '/twig',       // ❌ Môže vytvoriť public/cache/twig!
```

## 🔍 Root Cause Analysis

### **Problém s `$paths->path()`:**

```php
// ResponsiveSk\Slim4Paths\Paths::path()
public function path(string $relativePath): string
{
    return $this->basePath . '/' . ltrim($relativePath, '/\\');
}
```

**Keď sa aplikácia spustí z `public/` directory:**
- `$this->basePath` = `/path/to/project/public` ❌
- `$paths->path('storage')` = `/path/to/project/public/storage` ❌

**Správne by malo byť:**
- `$this->basePath` = `/path/to/project` ✅
- `$paths->storage()` = `/path/to/project/var/storage` ✅

## 🛠️ Oprava problémov

### **1. Oprava Slim4Container.php:**

```php
// ❌ NESPRÁVNE:
$cacheDir = $this->paths->path('cache/container');

// ✅ SPRÁVNE:
$cacheDir = $this->paths->cache('container');
```

### **2. Oprava Language module:**

```php
// ❌ NESPRÁVNE:
$cacheDir = $paths->path('cache/translations');

// ✅ SPRÁVNE:
$cacheDir = $paths->cache('translations');
```

### **3. Oprava Template module:**

```php
// ❌ NESPRÁVNE:
$paths->cache() . '/templates',
$paths->cache() . '/twig',

// ✅ SPRÁVNE:
$paths->cache('templates'),
$paths->cache('twig'),
```

## 📋 Audit všetkých `mkdir()` volaní

### **Bezpečné implementácie:**

#### **✅ Storage module - SPRÁVNE:**
```php
$contentDir = $paths->base() . '/content';  // Používa base()
```

#### **✅ PermissionManager - SPRÁVNE:**
```php
$securePath = $this->paths->path($path);  // Používa konfiguráciu
```

### **Problematické implementácie:**

#### **❌ Slim4Container - NESPRÁVNE:**
```php
$cacheDir = $this->paths->path('cache/container');  // Relatívna cesta!
```

#### **❌ Language module - NESPRÁVNE:**
```php
$cacheDir = $paths->path('cache/translations');  // Relatívna cesta!
```

## 🔧 Implementácia opráv

### **Fix Script:**

```php
#!/usr/bin/env php
<?php
// bin/fix-auto-create.php

echo "🔧 Fixing auto-create directory issues...\n";

// 1. Fix Slim4Container.php
$containerFile = 'src/SharedKernel/Container/Slim4Container.php';
$content = file_get_contents($containerFile);

$content = str_replace(
    '$cacheDir = $this->paths->path(\'cache/container\');',
    '$cacheDir = $this->paths->cache(\'container\');',
    $content
);

file_put_contents($containerFile, $content);
echo "✅ Fixed Slim4Container.php\n";

// 2. Fix Language module
$langFile = 'src/Modules/Core/Language/config.php';
$content = file_get_contents($langFile);

$content = str_replace(
    '$cacheDir = $paths->path(\'cache/translations\');',
    '$cacheDir = $paths->cache(\'translations\');',
    $content
);

file_put_contents($langFile, $content);
echo "✅ Fixed Language module\n";

// 3. Fix Template module
$templateFile = 'src/Modules/Core/Template/config.php';
$content = file_get_contents($templateFile);

$content = str_replace(
    '$paths->cache() . \'/templates\',',
    '$paths->cache(\'templates\'),',
    $content
);

$content = str_replace(
    '$paths->cache() . \'/twig\',',
    '$paths->cache(\'twig\'),',
    $content
);

file_put_contents($templateFile, $content);
echo "✅ Fixed Template module\n";

// 4. Remove auto_create dead code
$pathsFile = 'config/paths.php';
$content = file_get_contents($pathsFile);

// Comment out auto_create section
$content = str_replace(
    "'auto_create' => [",
    "// 'auto_create' => [ // REMOVED - was not used",
    $content
);

file_put_contents($pathsFile, $content);
echo "✅ Removed unused auto_create configuration\n";

echo "\n🎯 All fixes applied successfully!\n";
```

## 🔍 Verifikácia opráv

### **Test Script:**

```bash
#!/bin/bash
# bin/verify-auto-create-fix.sh

echo "🔍 Verifying auto-create fixes..."

# 1. Check for problematic patterns
echo "Checking for problematic path() usage..."

ISSUES=0

# Check Slim4Container
if grep -q "path('cache/" src/SharedKernel/Container/Slim4Container.php; then
    echo "❌ Slim4Container still uses path() for cache"
    ((ISSUES++))
else
    echo "✅ Slim4Container fixed"
fi

# Check Language module
if grep -q "path('cache/" src/Modules/Core/Language/config.php; then
    echo "❌ Language module still uses path() for cache"
    ((ISSUES++))
else
    echo "✅ Language module fixed"
fi

# Check Template module
if grep -q "cache() \. '/" src/Modules/Core/Template/config.php; then
    echo "❌ Template module still uses string concatenation"
    ((ISSUES++))
else
    echo "✅ Template module fixed"
fi

# 2. Test directory creation
echo ""
echo "Testing directory creation..."

# Start test server
php -S localhost:8890 -t public > /dev/null 2>&1 &
SERVER_PID=$!
sleep 2

# Make request to trigger initialization
curl -s http://localhost:8890/ > /dev/null

# Kill server
kill $SERVER_PID 2>/dev/null || true

# Check if public/storage was created
if [ -d "public/storage" ]; then
    echo "❌ CRITICAL: public/storage still being created!"
    ((ISSUES++))
else
    echo "✅ public/storage not created"
fi

# Check if public/cache was created
if [ -d "public/cache" ]; then
    echo "❌ CRITICAL: public/cache still being created!"
    ((ISSUES++))
else
    echo "✅ public/cache not created"
fi

# Check if var/cache exists
if [ -d "var/cache" ]; then
    echo "✅ var/cache exists (correct location)"
else
    echo "⚠️  var/cache missing"
fi

echo ""
if [ $ISSUES -eq 0 ]; then
    echo "🎉 All auto-create fixes verified successfully!"
    exit 0
else
    echo "❌ Found $ISSUES issues - fixes incomplete!"
    exit 1
fi
```

## 📊 Audit výsledky

### **Pred opravou:**
```
❌ public/storage/     - Vytváral sa nesprávne
❌ public/cache/       - Vytváral sa nesprávne  
❌ src/cache/          - Vytváral sa nesprávne
❌ auto_create         - Mŕtvy kód
```

### **Po oprave:**
```
✅ var/storage/        - Správne umiestnenie
✅ var/cache/          - Správne umiestnenie
✅ Žiadne public dirs  - Bezpečné
✅ Clean config        - Bez mŕtveho kódu
```

## 🎯 Best Practices

### **Správne použitie Paths:**

```php
// ✅ SPRÁVNE - používaj named methods:
$paths->storage('database.db')     // var/storage/database.db
$paths->cache('templates')         // var/cache/templates  
$paths->logs('app.log')           // var/logs/app.log

// ❌ NESPRÁVNE - nepoužívaj path() s relatívnymi cestami:
$paths->path('storage/database.db')  // Môže vytvoriť public/storage!
$paths->path('cache/templates')      // Môže vytvoriť public/cache!
```

### **Directory Creation Pattern:**

```php
// ✅ SPRÁVNE:
$cacheDir = $paths->cache('translations');
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// ❌ NESPRÁVNE:
$cacheDir = $paths->path('cache/translations');
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
```

## 🔗 Súvisiace dokumenty

- [Path Security Fix Guide](path-security-fix.md)
- [Paths Service Guide](../PATHS_SERVICE_GUIDE.md)
- [Security Best Practices](security-practices.md)
