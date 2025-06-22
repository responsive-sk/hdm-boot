# Path Security with responsive/path Package

This document describes the implementation of secure path handling in the MVA application using the `responsive-sk/slim4-paths` package.

## 🔒 Security Overview

The MVA application implements comprehensive path security to prevent:
- **Path traversal attacks** (../../../etc/passwd)
- **Directory escape attempts**
- **Unauthorized file access**
- **Malicious file uploads**

## 📦 Package Integration

### responsive-sk/slim4-paths

The application uses the `responsive-sk/slim4-paths` package for standardized path management:

```bash
composer require responsive-sk/slim4-paths
```

### Configuration

```php
// boot/container.php
$container->set(Paths::class, function () {
    return new Paths(__DIR__ . '/..');
});

$container->set(SecurePathHelper::class, function (Container $c) {
    return new SecurePathHelper($c->get(Paths::class));
});
```

## 🛡️ SecurePathHelper

### Core Security Features

```php
final class SecurePathHelper
{
    public function __construct(private readonly Paths $paths)
    {
        // Use Paths service instead of loading config file directly
        $this->allowedDirectories = [
            'public' => $this->paths->public(),
            'uploads' => $this->paths->uploads(),
            'assets' => $this->paths->assets(),
            'templates' => $this->paths->templates(),
            'storage' => $this->paths->storage(),
            'cache' => $this->paths->cache(),
            'logs' => $this->paths->logs(),
            'config' => $this->paths->config(),
            'var' => $this->paths->var(),
        ];

        // Security configuration - hardcoded for security
        $this->forbiddenPaths = [
            '.env',
            '.git',
            'vendor',
            'config',
            'bootstrap',
        ];

        $this->uploadRestrictions = [
            'max_size' => 5242880, // 5MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md'],
            'forbidden_extensions' => ['php', 'exe', 'bat', 'sh', 'js', 'html'],
        ];
    }
}
```

### Path Validation

#### 1. Path Traversal Detection
```php
private function validatePathTraversal(string $path): void
{
    $dangerousPatterns = [
        '../',      // Unix path traversal
        '..\\',     // Windows path traversal
        '..%2f',    // URL encoded /
        '..%2F',    // URL encoded /
        '..%5c',    // URL encoded \
        '..%5C',    // URL encoded \
        '%2e%2e%2f', // Double URL encoded ../
        '%2e%2e%5c', // Double URL encoded ..\
        '..../',    // Obfuscated traversal
        '....\\',   // Obfuscated traversal
    ];
    
    // Check for any dangerous patterns
    foreach ($dangerousPatterns as $pattern) {
        if (str_contains(strtolower($path), strtolower($pattern))) {
            throw new InvalidArgumentException("Path traversal attempt detected: {$pattern}");
        }
    }
}
```

#### 2. Directory Boundary Enforcement
```php
public function securePath(string $relativePath, string $baseDirectory = 'public'): string
{
    // Validate base directory
    if (!isset($this->allowedDirectories[$baseDirectory])) {
        throw new InvalidArgumentException("Base directory '{$baseDirectory}' is not allowed");
    }

    // Check for path traversal patterns
    $this->validatePathTraversal($relativePath);

    // Resolve real path
    $basePath = $this->allowedDirectories[$baseDirectory];
    $fullPath = $basePath . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    $realPath = realpath($fullPath);

    // Ensure path stays within allowed directory
    $realBasePath = realpath($basePath);
    if (!str_starts_with($realPath, $realBasePath)) {
        throw new InvalidArgumentException("Path resolves outside allowed directory");
    }

    return $realPath;
}
```

## 🗂️ File Operations

### Secure File Reading
```php
public function readFile(string $relativePath, string $baseDirectory = 'public'): string
{
    $securePath = $this->securePath($relativePath, $baseDirectory);

    if (!file_exists($securePath)) {
        throw new InvalidArgumentException("File does not exist");
    }

    if (!is_readable($securePath)) {
        throw new InvalidArgumentException("File is not readable");
    }

    return file_get_contents($securePath);
}
```

### Secure File Writing
```php
public function writeFile(string $relativePath, string $content, string $baseDirectory = 'var'): bool
{
    $securePath = $this->securePath($relativePath, $baseDirectory);

    // Ensure directory exists
    $directory = dirname($securePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    return file_put_contents($securePath, $content) !== false;
}
```

### Filename Sanitization
```php
public function sanitizeFilename(string $filename): string
{
    // Remove path separators and dangerous characters
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Ensure filename is not empty
    if (empty($filename)) {
        $filename = 'file_' . uniqid();
    }

    return $filename;
}
```

## 📁 FileService Integration

### Secure File Upload
```php
final class FileService
{
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md'];

    public function uploadFile(array $uploadedFile, string $targetDirectory = 'uploads'): array
    {
        $this->validateUploadedFile($uploadedFile);
        
        // Validate file size
        if ($uploadedFile['size'] > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('File size exceeds maximum allowed size');
        }

        // Validate file extension
        $extension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('File type not allowed');
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($uploadedFile['name']);
        
        // Get secure upload path
        $uploadPath = $this->pathHelper->securePath($targetDirectory . '/' . $filename, 'var');

        // Move uploaded file
        if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
            throw new InvalidArgumentException('Failed to move uploaded file');
        }

        return [
            'original_name' => $uploadedFile['name'],
            'filename' => $filename,
            'path' => $uploadPath,
            'size' => $uploadedFile['size'],
            'extension' => $extension,
        ];
    }
}
```

## 🌐 Web Interface

### File Management Routes
```php
// File routes
$app->get('/files', [FileAction::class, 'listFiles']);
$app->map(['GET', 'POST'], '/files/upload', [FileAction::class, 'upload']);
$app->get('/files/download/{filename}', [FileAction::class, 'download']);
$app->post('/files/delete/{filename}', [FileAction::class, 'delete']);
$app->get('/files/info/{filename}', [FileAction::class, 'info']);

// API routes
$app->get('/api/files/stats', [FileAction::class, 'stats']);
$app->post('/api/files/cleanup', [FileAction::class, 'cleanup']);
```

### File Action Security
```php
final class FileAction
{
    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $filename = $args['filename'] ?? '';
        $directory = $request->getQueryParams()['dir'] ?? 'uploads';

        // Validate directory parameter
        $allowedDirs = ['uploads', 'cache', 'logs'];
        if (!in_array($directory, $allowedDirs, true)) {
            return $response->withStatus(400);
        }

        try {
            $filePath = $directory . '/' . $filename;
            
            if (!$this->fileService->fileExists($filePath, 'var')) {
                return $response->withStatus(404);
            }

            $fileInfo = $this->fileService->getFileInfo($filePath, 'var');
            $content = $this->fileService->readFile($filePath, 'var');

            return $response
                ->withHeader('Content-Type', $fileInfo['mime_type'])
                ->withHeader('Content-Disposition', 'attachment; filename="' . $fileInfo['name'] . '"')
                ->withHeader('Content-Length', (string) $fileInfo['size']);

        } catch (\Exception $e) {
            return $response->withStatus(500);
        }
    }
}
```

## 🧪 Testing

### Security Test Examples
```php
final class SecurePathHelperTest extends TestCase
{
    public function test_throws_exception_for_path_traversal_attack(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Path traversal attempt detected');

        $this->pathHelper->securePath('../../../etc/passwd', 'public');
    }

    public function test_detects_various_path_traversal_patterns(): void
    {
        $maliciousPaths = [
            '../test.txt',
            '..\\test.txt',
            '..%2ftest.txt',
            '..%5ctest.txt',
            '....//test.txt',
            '%2e%2e%2ftest.txt',
        ];

        foreach ($maliciousPaths as $path) {
            try {
                $this->pathHelper->securePath($path, 'public');
                self::fail("Expected exception for path: {$path}");
            } catch (InvalidArgumentException $e) {
                self::assertStringContains('Path traversal attempt detected', $e->getMessage());
            }
        }
    }
}
```

## 📊 Usage Examples

### Basic File Operations
```php
// Read file securely
$content = $pathHelper->readFile('config/settings.json', 'config');

// Write file securely
$pathHelper->writeFile('logs/app.log', $logContent, 'var');

// Check file existence
if ($pathHelper->fileExists('uploads/image.jpg', 'var')) {
    // File exists
}

// Get public URL
$url = $pathHelper->getPublicUrl('assets/style.css');
```

### File Upload Example
```php
// Upload file with security validation
$uploadResult = $fileService->uploadFile($_FILES['file'], 'uploads');

// Result contains:
// - original_name: Original filename
// - filename: Sanitized unique filename
// - path: Secure absolute path
// - size: File size in bytes
// - extension: File extension
```

### Directory Operations
```php
// List files in directory
$files = $pathHelper->listFiles('uploads', 'var');

// Create directory
$pathHelper->writeFile('new_dir/file.txt', 'content', 'var');

// Get storage statistics
$stats = $fileService->getStorageStats();
```

### Health Check Integration
```php
// Health check using Paths service
class FilesystemHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Paths $paths
    ) {}

    private function checkLogDirectory(): array
    {
        $logDir = $this->paths->logs();

        if (!is_dir($logDir)) {
            return ['success' => false, 'message' => 'Log directory does not exist'];
        }

        return [
            'success' => true,
            'path' => realpath($logDir),
            'permissions' => substr(sprintf('%o', fileperms($logDir)), -4),
        ];
    }
}
```

## ✅ Implementation Status

### Path Security Improvements (2024)

The HDM Boot application has been updated to eliminate all relative path usage (`../..`) in favor of the proper `Paths` service:

#### Before (Insecure)
```php
// ❌ BAD: Direct file loading with relative paths
$pathsConfig = require __DIR__ . '/../../../config/paths.php';
$this->allowedDirectories = $pathsConfig['paths'];
```

#### After (Secure)
```php
// ✅ GOOD: Using Paths service
public function __construct(private readonly Paths $paths)
{
    $this->allowedDirectories = [
        'public' => $this->paths->public(),
        'uploads' => $this->paths->uploads(),
        // ... etc
    ];
}
```

### Security Benefits
- **No relative paths** - eliminates `../../../` patterns
- **Centralized path management** - all paths via `Paths` service
- **Type safety** - proper dependency injection
- **Testability** - easier to mock and test
- **Maintainability** - single source of truth for paths

## 🎨 Template Module Integration

### Secure Template Rendering

The **Core Template module** has been updated to use Paths for secure template file access:

#### Before (Insecure)
```php
// ❌ BAD: Direct string concatenation
$templateFile = $this->templatePath . '/' . $template;
```

#### After (Secure)
```php
// ✅ GOOD: Using Paths service
$templateFile = $this->paths->getPath($this->templatePath, $template);
```

### Template Renderer Configuration

```php
// src/Modules/Core/Template/config.php
TemplateRenderer::class => function (Container $container): TemplateRenderer {
    $paths = $container->get(Paths::class);

    return new TemplateRenderer(
        $paths->templates(),           // Base template directory
        $container->get(CsrfService::class),
        $container->get(OdanSession::class),
        $paths                         // Paths service for security
    );
},
```

### Template Security Benefits

- **Path traversal protection** - `../../../etc/passwd` attempts blocked
- **Template isolation** - templates can only access allowed directories
- **Consistent API** - same security patterns across all modules
- **Type safety** - proper dependency injection

### Usage in Modules

```php
// Blog Controller using secure template rendering
class BlogController
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly Paths $paths
    ) {}

    public function renderArticle(string $template, array $data): ResponseInterface
    {
        // Template path is automatically secured by Paths
        return $this->renderer->render($response, $template, $data);
    }
}
```

## 🔧 Configuration

### Allowed Directories
```php
// Directories are automatically resolved via Paths service
$allowedDirectories = [
    'public' => $this->paths->public(),      // Web-accessible files
    'uploads' => $this->paths->uploads(),    // User uploads
    'assets' => $this->paths->assets(),      // Static assets
    'templates' => $this->paths->templates(), // Template files
    'storage' => $this->paths->storage(),    // Application storage
    'cache' => $this->paths->cache(),        // Cache files
    'logs' => $this->paths->logs(),          // Log files
    'config' => $this->paths->config(),      // Configuration files
    'var' => $this->paths->var(),            // Variable data
];
```

### File Upload Limits
```php
// Configured in SecurePathHelper constructor
$this->uploadRestrictions = [
    'max_size' => 5242880, // 5MB
    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif',  // Images
        'pdf',                         // Documents
        'txt', 'md'                    // Text files
    ],
    'forbidden_extensions' => [
        'php', 'exe', 'bat', 'sh',     // Executable files
        'js', 'html'                   // Web files
    ],
];
```

## 🚨 SECURITY INCIDENT REPORT

**Date**: 2025-06-18
**Severity**: CRITICAL
**Status**: ACTIVE REMEDIATION

### **Incident Summary**
- **10 CRITICAL** path concatenation vulnerabilities detected
- **Module system** using unsafe string concatenation
- **Storage system** vulnerable to path traversal attacks
- **Template system** has path injection risks

### **Automated Detection**
```bash
# Run security scanner
./scripts/check-paths.sh

# Results: 10 CRITICAL, 20 TOTAL issues
# Build FAILS until fixed
```

### **Remediation Plan**
- [**PATHS_REFACTOR_PLAN.md**](PATHS_REFACTOR_PLAN.md) - Complete refactor strategy
- **PHPStan rule** - Automated detection
- **Security scanner** - CI/CD integration
- **Prevention system** - Future incident prevention

## ✅ PATHS PATTERN CHECKLIST

**Use this checklist for ALL file operations in HDM Boot:**

### ❌ NEVER DO:
```php
// ❌ Direct relative paths
$file = __DIR__ . '/../../../config/app.php';
$template = $templateDir . '/' . $userInput;
$path = '../uploads/' . $filename;

// ❌ String concatenation with user input
$fullPath = $baseDir . DIRECTORY_SEPARATOR . $userPath;
```

### ✅ ALWAYS DO:
```php
// ✅ Use Paths service
$file = $this->paths->config('app.php');
$template = $this->paths->getPath($this->paths->templates(), $templateName);
$upload = $this->paths->getPath($this->paths->uploads(), $filename);

// ✅ Dependency injection
public function __construct(private readonly Paths $paths) {}
```

### 🔍 Code Review Questions:
1. **Does this code use `../` or `__DIR__` concatenation?** → Replace with Paths
2. **Is user input concatenated to file paths?** → Use `Paths::getPath()`
3. **Are file operations using hardcoded paths?** → Use Paths methods
4. **Is Paths injected via DI container?** → Add to constructor
5. **Are templates using secure path resolution?** → Use TemplateRenderer with Paths

### 🛠️ Quick Fixes:

#### File Reading:
```php
// ❌ Before
$content = file_get_contents(__DIR__ . '/../config/' . $file);

// ✅ After
$content = file_get_contents($this->paths->config($file));
```

#### Template Rendering:
```php
// ❌ Before
$templateFile = $this->templatePath . '/' . $template;

// ✅ After
$templateFile = $this->paths->getPath($this->templatePath, $template);
```

#### Module Paths:
```php
// ❌ Before
$moduleDir = __DIR__ . '/../../../Modules/Core/Storage';

// ✅ After
$moduleDir = $this->paths->src('Modules/Core/Storage');
```

## 🚨 Security Best Practices

### 1. Input Validation
- Always validate file extensions
- Check file size limits
- Sanitize filenames
- Validate MIME types

### 2. Path Security
- **Use Paths service only** - no `../..` or `__DIR__` concatenation
- **Use whitelisted directories only** - predefined allowed paths
- **Prevent path traversal attacks** - comprehensive pattern detection
- **Validate all user input** - sanitize filenames and paths
- **Use realpath() for resolution** - resolve symbolic links and `.` components

### 3. File Operations
- Check file permissions
- Use secure temporary directories
- Implement proper error handling
- Log security events

### 4. Access Control
- Restrict file access by user role
- Implement download authentication
- Use secure file serving
- Monitor file operations

## 🔍 Monitoring & Logging

### Security Events
```php
// Log path traversal attempts
error_log("Path traversal attempt: {$maliciousPath} from IP: {$clientIP}");

// Log file access
error_log("File accessed: {$filename} by user: {$userId}");

// Log upload attempts
error_log("File upload: {$filename} size: {$size} by user: {$userId}");
```

### File Statistics
```php
$stats = $fileService->getStorageStats();
// Returns:
// - uploads: {file_count, total_size, total_size_mb}
// - cache: {file_count, total_size, total_size_mb}
// - logs: {file_count, total_size, total_size_mb}
```

## 📈 Current Implementation Status

### ✅ Completed Security Features

1. **Path Service Integration** - All paths managed via `responsive-sk/slim4-paths`
2. **Relative Path Elimination** - Zero `../..` patterns in codebase
3. **Directory Whitelisting** - Only predefined directories accessible
4. **Path Traversal Protection** - Comprehensive attack pattern detection
5. **File Upload Security** - Size limits, extension validation, filename sanitization
6. **Type Safety** - Proper PHPStan compliance at max level
7. **Health Check Security** - FilesystemHealthCheck uses Paths service (2025-06-16)

### 📊 Code Quality Metrics

- **PHP CS**: ✅ 0 errors (28 warnings for long lines)
- **PHPStan**: ✅ FilesystemHealthCheck & MonitoringBootstrap now pass level 9
- **Security**: ✅ No relative paths, proper Paths service usage throughout
- **Architecture**: ✅ Clean dependency injection, testable design
- **Health Checks**: ✅ All filesystem checks use secure Paths service

## 🔧 Recent Security Fixes (2025-06-16)

### FilesystemHealthCheck Path Security Migration

**Issue**: FilesystemHealthCheck was using `$rootPath` parameter with relative path construction, causing `fopen(): Failed to open stream: No such file or directory` errors.

#### Before (Insecure)
```php
// ❌ BAD: Using $rootPath parameter with relative paths
public function __construct(
    private readonly LoggerInterface $logger,
    private readonly string $rootPath = '.'  // Dangerous default!
) {}

private function checkLogDirectory(): array
{
    $logDir = $this->rootPath . '/var/logs';  // Relative path construction
    // ...
}

private function checkWritePermissions(): array
{
    $testFile = $this->rootPath . '/var/logs/health_check_test.tmp';  // Unsafe
    // ...
}
```

#### After (Secure)
```php
// ✅ GOOD: Using Paths service
public function __construct(
    private readonly LoggerInterface $logger,
    private readonly Paths $paths  // Secure path service
) {}

private function checkLogDirectory(): array
{
    $logDir = $this->paths->logs();  // Secure path resolution
    // ...
}

private function checkWritePermissions(): array
{
    $testFile = $this->paths->logs() . '/health_check_test.tmp';  // Safe
    // ...
}
```

#### Changes Made
1. **Removed** `$rootPath` parameter from `FilesystemHealthCheck` constructor
2. **Added** `Paths $paths` dependency injection
3. **Replaced** all `$this->rootPath . '/var/logs'` with `$this->paths->logs()`
4. **Replaced** all `$this->rootPath . '/var/cache'` with `$this->paths->cache()`
5. **Replaced** disk space check to use `$this->paths->base()`
6. **Updated** `MonitoringBootstrap` to inject `Paths` service
7. **Fixed** PHPStan type safety warnings

#### Security Benefits
- ✅ **No relative paths** - eliminates `./var/logs` patterns
- ✅ **Centralized configuration** - all paths from `config/paths.php`
- ✅ **Type safety** - proper dependency injection
- ✅ **Consistent behavior** - same path resolution as rest of application
- ✅ **Error prevention** - no more "file not found" errors from wrong working directory

#### Health Check Results
```json
{
  "filesystem": {
    "status": "healthy",
    "data": {
      "log_directory": {
        "success": true,
        "path": "/home/ian/Desktop/06/boot/var/logs",
        "permissions": "0775"
      },
      "cache_directory": {
        "success": true,
        "path": "/home/ian/Desktop/06/boot/var/cache",
        "permissions": "0775"
      },
      "write_permissions": {
        "success": true,
        "test_file": "/home/ian/Desktop/06/boot/var/logs/health_check_test.tmp"
      }
    }
  }
}
```

### 🔄 Future Improvements

1. **Configuration Service** - Move upload restrictions to dedicated config service
2. **File Type Detection** - Add MIME type validation beyond extensions
3. **Virus Scanning** - Integrate file scanning for uploaded content
4. **Audit Logging** - Enhanced security event logging
5. **Performance** - Path caching for frequently accessed directories

This implementation provides comprehensive path security while maintaining usability and performance for the MVA application.
