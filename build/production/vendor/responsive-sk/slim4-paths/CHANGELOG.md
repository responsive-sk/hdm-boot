# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2025-06-27

### Added
- **Environment-based Factory**
  - `fromEnv()` static method for creating Paths instances from environment variables
  - Supports custom environment variable names (default: APP_BASE_PATH)
  - Automatic path validation and error handling

### Enhanced
- **Developer Experience**
  - Environment-based configuration support
  - Better error messages for invalid environment paths
  - Consistent API with existing path methods

### Examples
```php
// Environment-based initialization
putenv('APP_BASE_PATH=/path/to/project');
$paths = Paths::fromEnv();

// Use existing methods with environment-based initialization
$paths->storage('database.db'); // /path/to/project/var/storage/database.db
$paths->logs('app.log');        // /path/to/project/var/logs/app.log
```

## [2.1.0] - 2025-06-27

### Added
- **Convenience Method**
  - `fromHere()` static method for creating Paths instances from current file location
  - Automatic path validation with clear error messages
  - Cross-platform compatibility with proper path resolution
  - Reduces manual path construction errors

### Enhanced
- **Developer Experience**
  - More expressive path creation: `Paths::fromHere(__DIR__, 3)`
  - Less error-prone than manual `../` counting
  - Works in tests, CLI, and without DI containers
  - Better error messages for invalid path resolution

### Examples
```php
// Before (error-prone)
$paths = new Paths(__DIR__ . '/../../..');

// After (clean and safe)
$paths = Paths::fromHere(__DIR__, 3);

// Usage
$dbPath = $paths->storage('database.db');
$logPath = $paths->logs('app.log');
```

## [2.0.0] - 2025-06-18

### Added
- **Security Features**
  - `getPath()` method for secure path joining
  - Path traversal attack protection
  - Input validation for all path operations
  - Home directory access protection

- **Content Management (Orbit CMS)**
  - `content()` method for content directory access
  - `articles()` method for article files
  - `orbit()` method for Orbit database directory
  - `orbitDatabase()` method for specific database files

- **Template System**
  - `views()` method for view templates
  - `layouts()` method for layout templates
  - `partials()` method for partial templates
  - Enhanced template path resolution

- **Module System Integration**
  - `moduleConfig()` method for module configuration files
  - `moduleRoutes()` method for module route files
  - `moduleTemplates()` method for module template directories
  - Advanced module path resolution

- **Asset Management**
  - `css()` method for CSS files
  - `js()` method for JavaScript files
  - `images()` method for image files
  - `fonts()` method for font files
  - `media()` method for media files

- **Development Tools**
  - `docs()` method for documentation files
  - `scripts()` method for script files
  - `bin()` method for executable files
  - `tests()` method for test files

- **Security & Storage**
  - `keys()` method for security keys
  - `exports()` method for export files
  - `imports()` method for import files
  - Enhanced storage path management

- **Localization Support**
  - `lang()` method for language files
  - `translations()` method for translation files
  - `locales()` method for locales directory
  - Multi-language path resolution

- **Enhanced Configuration**
  - 30+ predefined path configurations
  - Auto-directory creation support
  - Security whitelist/blacklist configuration
  - Cross-platform compatibility

### Changed
- **Breaking Changes**
  - Constructor signature changed to accept custom path configuration
  - Default path structure updated for modern applications
  - Method signatures enhanced for better type safety

- **Performance Improvements**
  - Optimized path resolution algorithm
  - Reduced memory footprint
  - Faster path validation

- **Security Enhancements**
  - All path operations now use secure joining
  - Input validation on all methods
  - Protection against common attack vectors

### Deprecated
- Direct path concatenation (use `getPath()` instead)
- Unsafe path operations (replaced with secure alternatives)

### Removed
- Legacy path methods without security validation
- Deprecated configuration options

### Fixed
- Path traversal vulnerabilities
- Cross-platform path separator issues
- Directory creation race conditions
- Memory leaks in path caching

### Security
- **CRITICAL**: Fixed path traversal vulnerabilities
- **HIGH**: Added input validation for all path operations
- **MEDIUM**: Enhanced directory access controls
- **LOW**: Improved error message sanitization

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Basic path management functionality
- Core directory methods:
  - `base()` - Project root directory
  - `config()` - Configuration directory
  - `public()` - Public directory
  - `templates()` - Templates directory
  - `var()` - Variable directory
  - `cache()` - Cache directory
  - `logs()` - Logs directory
  - `storage()` - Storage directory
  - `tests()` - Tests directory
  - `vendor()` - Vendor directory
  - `assets()` - Assets directory
  - `uploads()` - Uploads directory

### Features
- Lightweight implementation
- No external dependencies
- Simple array-based path storage
- Basic path resolution
- Slim Framework integration

## Migration Guide

### From 1.x to 2.0

#### Constructor Changes
```php
// Old (1.x)
$paths = new Paths('/path/to/project');

// New (2.0)
$paths = new Paths('/path/to/project', [
    'templates' => '/path/to/project/templates',
    'content' => '/path/to/project/content',
    // ... more paths
]);
```

#### Security Updates
```php
// Old (1.x) - Potentially unsafe
$filePath = $basePath . '/' . $userInput;

// New (2.0) - Secure
$filePath = $paths->getPath($basePath, $userInput);
```

#### New Methods
All new methods are additive and don't break existing functionality:
- Content management methods (`content()`, `articles()`, `orbit()`)
- Module system methods (`moduleConfig()`, `moduleRoutes()`, `moduleTemplates()`)
- Asset management methods (`css()`, `js()`, `images()`, `fonts()`, `media()`)
- Development tools (`docs()`, `scripts()`, `bin()`)
- Security methods (`keys()`, `exports()`, `imports()`)
- Localization methods (`lang()`, `translations()`, `locales()`)

#### Configuration Updates
Update your configuration to include new path definitions:
```php
// config/paths.php
return [
    'base_path' => '/path/to/project',
    'paths' => [
        // Core directories (existing)
        'config' => '/path/to/project/config',
        'public' => '/path/to/project/public',
        'templates' => '/path/to/project/templates',
        
        // New directories (2.0)
        'content' => '/path/to/project/content',
        'articles' => '/path/to/project/content/articles',
        'orbit' => '/path/to/project/var/orbit',
        'css' => '/path/to/project/public/assets/css',
        'js' => '/path/to/project/public/assets/js',
        // ... more paths
    ]
];
```

## Support

- **Documentation**: See README.md for complete usage guide
- **Issues**: Report bugs and feature requests on GitHub
- **Security**: Report security vulnerabilities privately
- **Contributing**: See CONTRIBUTING.md for development guidelines
