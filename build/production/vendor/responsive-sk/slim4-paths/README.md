# Slim4 Paths - Enhanced Path Management

A comprehensive, secure path management package for PHP applications with advanced features for modern web development.

## Features

- **Secure Path Joining** - Prevents path traversal attacks
- **30+ Predefined Paths** - Complete coverage for web applications
- **Cross-Platform Compatibility** - Works on Windows, Linux, macOS
- **Auto-Directory Creation** - Automatic directory structure setup
- **Security Validation** - Input validation and path sanitization
- **Framework Agnostic** - Works with any PHP framework
- **Orbit CMS Support** - Built-in support for content management
- **Module System Integration** - Advanced module path resolution

## Installation

```bash
composer require responsive-sk/slim4-paths
```

## Basic Usage

```php
use ResponsiveSk\Slim4Paths\Paths;

// Initialize with base path and configuration
$paths = new Paths('/path/to/project', [
    'templates' => '/path/to/project/templates',
    'content' => '/path/to/project/content',
    // ... more paths
]);

// Basic path access
$templatePath = $paths->templates();
$configPath = $paths->config();
$publicPath = $paths->public();
```

## Core Path Methods

### Basic Directories
```php
$paths->base()          // Project root directory
$paths->config()        // Configuration directory
$paths->src()           // Source code directory
$paths->public()        // Public web directory
$paths->var()           // Variable/runtime directory
$paths->vendor()        // Vendor directory
```

### Template System
```php
$paths->templates()                    // Main templates directory
$paths->views()                        // Views directory
$paths->layouts('main.php')            // Layout templates
$paths->partials('header.php')         // Partial templates
```

### Content Management (Orbit CMS)
```php
$paths->content()                      // Content root directory
$paths->articles('post.md')            // Article files
$paths->orbit()                        // Orbit database directory
$paths->orbitDatabase('app')           // Orbit database file (app.db)
```

### Module System
```php
$paths->moduleConfig('Core/Storage')           // Module config file
$paths->moduleRoutes('Core/Template')          // Module routes file
$paths->moduleTemplates('Optional/Blog')       // Module templates directory
```

### Asset Management
```php
$paths->css('app.css')             // CSS files
$paths->js('main.js')              // JavaScript files
$paths->images('logo.png')         // Image files
$paths->fonts('roboto.woff2')      // Font files
$paths->media('video.mp4')         // Media files
```

### Development Tools
```php
$paths->docs('api.md')             // Documentation files
$paths->scripts('deploy.sh')       // Script files
$paths->bin('console')             // Executable files
$paths->tests('UserTest.php')      // Test files
```

### Security & Storage
```php
$paths->keys('private.key')        // Security keys
$paths->exports('data.json')       // Export files
$paths->imports('import.csv')      // Import files
$paths->logs('app.log')            // Log files
$paths->cache('views')             // Cache files
$paths->storage('uploads')         // Storage files
```

### Localization
```php
$paths->lang('en', 'messages.php')     // Language files
$paths->translations('app.po')          // Translation files
$paths->locales()                       // Locales directory
```

## Secure Path Joining

The `getPath()` method provides secure path joining with built-in protection against path traversal attacks:

```php
// Secure path joining
$safePath = $paths->getPath($baseDir, $relativePath);

// Automatic validation
try {
    $maliciousPath = $paths->getPath('/safe/dir', '../../../etc/passwd');
} catch (InvalidArgumentException $e) {
    // Path traversal detected and blocked
    echo $e->getMessage(); // "Path traversal detected in: ../../../etc/passwd"
}
```

### Security Features

- **Path Traversal Protection** - Detects and blocks `..` sequences
- **Home Directory Protection** - Blocks `~` access
- **Input Validation** - Validates all path components
- **Cross-Platform Safety** - Handles different directory separators

## Configuration

### Basic Configuration

```php
$config = [
    'base_path' => '/path/to/project',
    'paths' => [
        'templates' => '/path/to/project/templates',
        'content' => '/path/to/project/content',
        'public' => '/path/to/project/public',
        // ... more paths
    ]
];

$paths = new Paths($config['base_path'], $config['paths']);
```

## Framework Integration

### Slim Framework

```php
use ResponsiveSk\Slim4Paths\Paths;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Add Paths to container
$container = $app->getContainer();
$container->set(Paths::class, function() {
    $config = require 'config/paths.php';
    return new Paths($config['base_path'], $config['paths']);
});

// Use in routes
$app->get('/template/{name}', function ($request, $response, $args) {
    $paths = $this->get(Paths::class);
    $templatePath = $paths->templates($args['name'] . '.php');
    
    if (!file_exists($templatePath)) {
        throw new \Slim\Exception\HttpNotFoundException($request);
    }
    
    // Render template...
});
```

## Orbit CMS Integration

This package includes built-in support for Orbit-style content management:

```php
// Article management
$articlePath = $paths->articles('getting-started.md');
$articlesDir = $paths->articles();

// Database management
$appDb = $paths->orbitDatabase('app');      // var/orbit/app.db
$markDb = $paths->orbitDatabase('mark');    // var/orbit/mark.db
$cacheDb = $paths->orbitDatabase('cache');  // var/orbit/cache.db

// Content organization
$contentRoot = $paths->content();           // content/
$docsContent = $paths->content('docs');     // content/docs/
```

## Module System Support

Advanced module path resolution for modular applications:

```php
// Module configuration
$storageConfig = $paths->moduleConfig('Core/Storage');
// Result: src/Modules/Core/Storage/config.php

// Module routes
$templateRoutes = $paths->moduleRoutes('Core/Template');
// Result: src/Modules/Core/Template/routes.php

// Module templates
$blogTemplates = $paths->moduleTemplates('Optional/Blog');
// Result: src/Modules/Optional/Blog/templates/

$blogArticleTemplate = $paths->moduleTemplates('Optional/Blog', 'article.php');
// Result: src/Modules/Optional/Blog/templates/article.php
```

## Security Best Practices

### Always Use Secure Path Joining

```php
// Good - secure path joining
$filePath = $paths->getPath($baseDir, $userInput);

// Bad - vulnerable to path traversal
$filePath = $baseDir . '/' . $userInput;
```

### Validate User Input

```php
function loadUserFile(Paths $paths, string $filename)
{
    // Validate filename
    if (!preg_match('/^[a-zA-Z0-9_-]+\.txt$/', $filename)) {
        throw new InvalidArgumentException('Invalid filename');
    }
    
    // Use secure path joining
    $filePath = $paths->getPath($paths->uploads(), $filename);
    
    return file_get_contents($filePath);
}
```

### Directory Traversal Protection

The package automatically protects against common attacks:

```php
// These will throw InvalidArgumentException
$paths->getPath('/safe/dir', '../../../etc/passwd');
$paths->getPath('/safe/dir', '~/sensitive/file');
$paths->getPath('/safe/dir', 'file/../../../etc/passwd');
```

## Testing

```php
use PHPUnit\Framework\TestCase;
use ResponsiveSk\Slim4Paths\Paths;

class PathsTest extends TestCase
{
    public function testSecurePathJoining()
    {
        $paths = new Paths('/project', ['uploads' => '/project/uploads']);
        
        // Valid path
        $validPath = $paths->getPath($paths->uploads(), 'file.txt');
        $this->assertEquals('/project/uploads/file.txt', $validPath);
        
        // Invalid path should throw exception
        $this->expectException(InvalidArgumentException::class);
        $paths->getPath($paths->uploads(), '../../../etc/passwd');
    }
}
```

## Requirements

- PHP 8.1 or higher
- No additional dependencies

## License

MIT License

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Changelog

### Version 2.0.0
- Added 30+ predefined path methods
- Enhanced security with path traversal protection
- Added Orbit CMS support
- Added module system integration
- Added comprehensive test coverage
- Breaking changes from 1.x (see migration guide)

### Version 1.0.0
- Initial release
- Basic path management functionality
