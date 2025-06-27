# Paths Service Guide

## Overview

The Paths service provides secure, configurable path management for HDM Boot Protocol applications. It prevents path traversal attacks and centralizes path configuration.

## Key Concepts

### Method Types

The Paths service provides two types of methods:

1. **Configured Path Methods** - Use custom configuration from `config/paths.php`
2. **Generic Path Method** - Uses base path + relative path

### Configured Path Methods (RECOMMENDED)

These methods use the custom configuration and should be preferred:

```php
// Database files
$paths->storage('mark.db')        // → /var/storage/mark.db
$paths->storage('user.db')        // → /var/storage/user.db

// Log files  
$paths->logs('app.log')           // → /var/logs/app.log
$paths->logs('error.log')         // → /var/logs/error.log

// Cache files
$paths->cache('templates')        // → /var/cache/templates
$paths->cache('translations')     // → /var/cache/translations

// Session files
$paths->sessions()                // → /var/sessions

// Content files
$paths->content('articles')       // → /content/articles
$paths->articles('post.md')       // → /content/articles/post.md

// Template files
$paths->templates('blog.php')     // → /templates/blog.php
$paths->layouts('main.php')       // → /templates/layouts/main.php
```

### Generic Path Method (LIMITED USE)

This method uses basePath + relativePath and ignores custom configuration:

```php
// ⚠️ LIMITED USE - Does not use custom config
$paths->path('storage/mark.db')   // → /storage/mark.db (NOT /var/storage/)
$paths->path('custom/file.txt')   // → /custom/file.txt
```

**Use only for paths not covered by configured methods.**

## Configuration

### config/paths.php Structure

```php
return [
    'base_path' => $basePath,
    'paths' => [
        // Runtime directories
        'storage'  => $basePath . '/var/storage',  // Database files
        'logs'     => $basePath . '/var/logs',     // Log files
        'cache'    => $basePath . '/var/cache',    // Cache files
        'sessions' => $basePath . '/var/sessions', // Session data
        
        // Content directories
        'content'   => $basePath . '/content',
        'articles'  => $basePath . '/content/articles',
        
        // Template directories
        'templates' => $basePath . '/templates',
        'layouts'   => $basePath . '/templates/layouts',
        'partials'  => $basePath . '/templates/partials',
        
        // Asset directories
        'css'    => $basePath . '/public/assets/css',
        'js'     => $basePath . '/public/assets/js',
        'images' => $basePath . '/public/assets/images',
    ],
];
```

## Usage Patterns

### Database Managers

```php
class MarkSqliteDatabaseManager extends AbstractDatabaseManager
{
    public function __construct(?string $databasePath = null, ?Paths $paths = null)
    {
        $paths = $paths ?? new Paths(__DIR__ . '/../../..');
        $databasePath = $databasePath ?? $paths->storage('mark.db'); // ✅ CORRECT
        parent::__construct($databasePath, [], $paths);
    }
}
```

### Service Classes

```php
class LoggingService
{
    public function __construct(private Paths $paths) {}
    
    public function writeLog(string $message): void
    {
        $logFile = $this->paths->logs('app.log'); // ✅ CORRECT
        file_put_contents($logFile, $message, FILE_APPEND);
    }
}
```

### Template Engines

```php
class TemplateEngine
{
    public function render(string $template): string
    {
        $templatePath = $this->paths->templates($template); // ✅ CORRECT
        return file_get_contents($templatePath);
    }
}
```

## PathsFactory Usage

Use PathsFactory for singleton instance management:

```php
use HdmBoot\SharedKernel\Services\PathsFactory;

// Get singleton instance (loads config automatically)
$paths = PathsFactory::create();

// Reset singleton (useful for testing)
PathsFactory::reset();

// Create new instance from config
$paths = PathsFactory::createFromConfig();
```

## Common Mistakes

### ❌ WRONG - Using path() for configured paths

```php
// These ignore custom configuration
$dbPath = $paths->path('storage/mark.db');     // → /storage/mark.db
$logPath = $paths->path('var/logs/app.log');   // → /var/logs/app.log
$cachePath = $paths->path('var/cache/twig');   // → /var/cache/twig
```

### ✅ CORRECT - Using specific methods

```php
// These use custom configuration
$dbPath = $paths->storage('mark.db');          // → /var/storage/mark.db
$logPath = $paths->logs('app.log');            // → /var/logs/app.log  
$cachePath = $paths->cache('twig');            // → /var/cache/twig
```

### ❌ WRONG - Hardcoded paths

```php
$dbPath = __DIR__ . '/../../var/storage/mark.db';
$logPath = '../var/logs/app.log';
```

### ✅ CORRECT - Paths service

```php
$dbPath = $this->paths->storage('mark.db');
$logPath = $this->paths->logs('app.log');
```

## Security Features

- **Path traversal protection** - Prevents `../` attacks
- **Configurable base paths** - Centralized path management
- **Type-safe path resolution** - Compile-time path validation
- **Directory isolation** - Separate paths for different data types

## Directory Structure

Recommended directory structure for HDM Boot Protocol:

```
project/
├── var/                    # Runtime data
│   ├── storage/           # Database files
│   │   ├── mark.db
│   │   ├── user.db
│   │   └── system.db
│   ├── logs/              # Application logs
│   ├── cache/             # Cache files
│   └── sessions/          # Session data
├── content/               # Content files
│   ├── articles/          # Markdown articles
│   └── docs/              # Documentation
├── templates/             # Template files
│   ├── layouts/
│   └── partials/
├── public/                # Web-accessible files
│   ├── assets/
│   └── media/
└── config/
    └── paths.php          # Path configuration
```

## Migration Guide

### From Hardcoded Paths

1. **Identify hardcoded paths** using the paths audit tool:
   ```bash
   php bin/audit-paths.php
   ```

2. **Replace with Paths service calls**:
   ```php
   // Before
   $dbPath = 'storage/mark.db';
   
   // After  
   $dbPath = $this->paths->storage('mark.db');
   ```

3. **Update constructors** to accept Paths service:
   ```php
   public function __construct(private Paths $paths) {}
   ```

### From path() Method

1. **Identify path() usage** for configured paths
2. **Replace with specific methods**:
   ```php
   // Before
   $paths->path('storage/mark.db')
   
   // After
   $paths->storage('mark.db')
   ```

## Testing

Test path resolution in your applications:

```php
// Test configured paths
$this->assertEquals('/var/storage/mark.db', $paths->storage('mark.db'));
$this->assertEquals('/var/logs/app.log', $paths->logs('app.log'));

// Test file existence
$this->assertTrue(file_exists($paths->storage('mark.db')));
```

## Best Practices

1. **Use specific methods** (storage(), logs(), cache()) over generic path()
2. **Inject Paths service** into constructors rather than creating instances
3. **Use PathsFactory** for singleton management
4. **Configure custom paths** in config/paths.php
5. **Protect sensitive directories** with .htaccess files
6. **Run paths audit** regularly to detect hardcoded paths
7. **Test path resolution** in your test suite
