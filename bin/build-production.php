#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Production Build Script.
 * 
 * Creates production-ready package for FTP/FTPS upload to shared hosting.
 * Removes development files, optimizes autoloader, sets production permissions.
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "🚀 HDM Boot Production Build\n";
echo "============================\n\n";

$buildDir = __DIR__ . '/../build/production';
$sourceDir = __DIR__ . '/..';

try {
    // Clean previous build
    if (is_dir($buildDir)) {
        echo "🧹 Cleaning previous build...\n";
        exec("rm -rf " . escapeshellarg($buildDir));
    }
    
    // Create build directory
    mkdir($buildDir, 0755, true);
    echo "📁 Created build directory: {$buildDir}\n";
    
    // Copy production files
    echo "📋 Copying production files...\n";
    
    $productionFiles = [
        'src/',
        'public/',
        'config/',
        'templates/',
        'var/',
        // vendor/ will be created with composer install --no-dev
        'bin/init-all-databases.php',
        'bin/init-mark-db.php',
        'bin/init-user-db.php',
        'bin/init-system-db.php',
        'bin/check-protocol-compliance.php',
        'composer.json',
        'composer.lock',
        '.htaccess',
        'README.md',
    ];
    
    foreach ($productionFiles as $file) {
        $source = $sourceDir . '/' . $file;
        $dest = $buildDir . '/' . $file;
        
        if (is_file($source)) {
            echo "   📄 Copying file: {$file}\n";

            // Create directory if needed
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            copy($source, $dest);
        } elseif (is_dir($source)) {
            echo "   📁 Copying directory: {$file}\n";
            exec("cp -r " . escapeshellarg($source) . " " . escapeshellarg($dest));
        }
    }
    
    // Remove development files from build
    echo "\n🗑️  Removing development files...\n";
    
    $devFiles = [
        'tests/',
        'docs/',
        '.git/',
        '.github/',
        'phpstan.neon',
        '.php-cs-fixer.dist.php',
        'var/cache/',
        'var/logs/',
        'storage/',  // Remove old storage directory completely
    ];
    
    foreach ($devFiles as $pattern) {
        $fullPattern = $buildDir . '/' . $pattern;
        echo "   🗑️  Removing: {$pattern}\n";
        exec("rm -rf " . escapeshellarg($fullPattern));
    }

    // Install production dependencies only
    echo "\n📦 Installing production dependencies (--no-dev)...\n";
    $currentDir = getcwd();
    chdir($buildDir);

    $composerCommand = 'composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist';
    echo "   Running: {$composerCommand}\n";

    $output = [];
    $returnCode = 0;
    exec($composerCommand . ' 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        echo "   ⚠️  Composer install failed, output:\n";
        foreach ($output as $line) {
            echo "      {$line}\n";
        }
        echo "   ℹ️  Dependencies may need to be installed manually on server\n";
    } else {
        echo "   ✅ Production dependencies installed successfully\n";
    }

    chdir($currentDir);

    // Create production .env template
    echo "\n⚙️  Creating production .env template...\n";
    $envTemplate = <<<ENV
# HDM Boot Production Configuration
APP_ENV=production
APP_DEBUG=false
APP_NAME="HDM Boot Application"

# Database Configuration
DATABASE_PATH=var/storage/
MARK_DATABASE_PATH=var/storage/mark.db
USER_DATABASE_PATH=var/storage/user.db
SYSTEM_DATABASE_PATH=var/storage/system.db

# Permission Configuration
PERMISSIONS_STRICT=false

# Security Configuration
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict
HTTPS=true

# Cache Configuration
CACHE_ENABLED=true
TEMPLATE_CACHE_ENABLED=true

# Logging Configuration
LOG_LEVEL=error
LOG_PATH=var/logs/

# Replace these with your actual values
SECRET_KEY=CHANGE_THIS_SECRET_KEY_IN_PRODUCTION
CSRF_SECRET=CHANGE_THIS_CSRF_SECRET_IN_PRODUCTION
ENV;
    
    file_put_contents($buildDir . '/.env.example', $envTemplate);
    
    // Create production directories with correct permissions
    echo "\n📁 Creating production directories...\n";
    
    $prodDirs = [
        'var/storage',
        'var/logs',
        'var/sessions',
        'var/cache',
    ];
    
    foreach ($prodDirs as $dir) {
        $fullDir = $buildDir . '/' . $dir;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0777, true); // Relaxed for shared hosting
            echo "   📁 Created: {$dir} (777)\n";
        }
    }
    
    // Create production log files
    echo "\n📝 Creating production log files...\n";
    
    $logFiles = [
        'var/logs/app.log',
        'var/logs/security.log',
        'var/logs/error.log',
    ];
    
    foreach ($logFiles as $logFile) {
        $fullPath = $buildDir . '/' . $logFile;
        touch($fullPath);
        chmod($fullPath, 0666); // Writable for shared hosting
        echo "   📝 Created: {$logFile} (666)\n";
    }
    
    // Create production .htaccess for security
    echo "\n🔒 Creating production .htaccess...\n";
    
    $htaccess = <<<HTACCESS
# HDM Boot Production Security
RewriteEngine On

# Deny access to sensitive files
<FilesMatch "\.(env|log|db|json|lock|md)$">
    Require all denied
</FilesMatch>

# Deny access to directories
<DirectoryMatch "(storage|var|config|src|vendor)">
    Require all denied
</DirectoryMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</IfModule>
HTACCESS;
    
    file_put_contents($buildDir . '/.htaccess', $htaccess);

    // Create public/.htaccess for proper routing
    $publicHtaccess = <<<HTACCESS
# HDM Boot Public Directory
RewriteEngine On

# Handle all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
HTACCESS;

    file_put_contents($buildDir . '/public/.htaccess', $publicHtaccess);

    // Initialize databases locally for shared hosting
    echo "\n🗄️ Initializing databases for shared hosting...\n";

    // Change to build directory to create databases there
    $originalDir = getcwd();
    chdir($buildDir);

    try {
        // Run database initialization
        echo "   🔴 Creating mark.db...\n";
        exec('php bin/init-mark-db.php', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to create mark.db');
        }

        echo "   🔵 Creating user.db...\n";
        exec('php bin/init-user-db.php', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to create user.db');
        }

        echo "   🟢 Creating system.db...\n";
        exec('php bin/init-system-db.php', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to create system.db');
        }

        echo "   ✅ All databases created successfully\n";

    } catch (\Exception $e) {
        echo "   ❌ Database creation failed: " . $e->getMessage() . "\n";
        echo "   ℹ️  Databases will need to be created manually on server\n";
    } finally {
        chdir($originalDir);
    }

    // Create deployment instructions
    echo "\n📋 Creating deployment instructions...\n";
    
    $instructions = <<<INSTRUCTIONS
# HDM Boot Production Deployment Instructions

## 1. Upload Files (FTP/FTPS)
Upload all files from this build directory to your web server root via FTP/FileZilla.

IMPORTANT: Make sure your web server points to the 'public/' directory as document root,
or upload the contents of 'public/' to your web root and other files outside web root.

## 2. Database Files (Pre-created)
The following databases are already created and ready to use:
- var/storage/mark.db (Mark system users)
- var/storage/user.db (Application users)
- var/storage/system.db (Core system data)

NO database initialization needed - just upload and use!

## 3. Configure Environment
1. Copy `.env.example` to `.env`
2. Edit `.env` with your production values:
   - Change SECRET_KEY and CSRF_SECRET
   - Set your domain/URL settings
   - Set PERMISSIONS_STRICT=false for shared hosting

## 4. Set Permissions (if possible)
If your hosting provider allows:
```bash
chmod 777 var/ var/storage/ var/logs/ var/sessions/ var/cache/
chmod 666 var/logs/*.log var/storage/*.db
```

## 5. Default Users (Pre-created)
Mark Users (mark.db):
- mark@responsive.sk / mark123
- admin@example.com / admin123

Application Users (user.db):
- test@example.com / password123
- user@example.com / user123

## 6. Test Installation
1. Visit your website
2. Try logging in as mark user
3. Try registering as regular user
4. Check error logs if issues occur

## 7. Security Checklist
- [ ] Changed default passwords
- [ ] Updated .env secrets
- [ ] Verified .htaccess is working
- [ ] Tested file permissions
- [ ] Checked error logs

## Support
- Documentation: docs/
- Protocol: docs/HDM_BOOT_PROTOCOL.md
- Troubleshooting: docs/TROUBLESHOOTING.md

INSTRUCTIONS;
    
    file_put_contents($buildDir . '/DEPLOYMENT.md', $instructions);
    
    // Create ZIP package
    echo "\n📦 Creating ZIP package...\n";
    
    $zipFile = __DIR__ . '/../hdm-boot-production-' . date('Y-m-d-H-i-s') . '.zip';
    
    chdir($buildDir);
    exec("zip -r " . escapeshellarg($zipFile) . " .", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ ZIP package created: " . basename($zipFile) . "\n";
        echo "📊 Package size: " . formatBytes(filesize($zipFile)) . "\n";
    } else {
        echo "❌ Failed to create ZIP package\n";
    }
    
    // Summary
    echo "\n✅ Production build completed!\n";
    echo "================================\n";
    echo "📁 Build directory: {$buildDir}\n";
    echo "📦 ZIP package: " . basename($zipFile) . "\n";
    echo "📋 Deployment instructions: DEPLOYMENT.md\n";
    echo "\n🚀 Ready for FTP upload to shared hosting!\n";
    
} catch (\Exception $e) {
    echo "❌ Build failed: " . $e->getMessage() . "\n";
    exit(1);
}

function formatBytes(int $size, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
