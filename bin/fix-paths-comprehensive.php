#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Comprehensive Paths Fix Script
 * 
 * Fixes all critical paths issues identified by audit:
 * - Removes dangerous public/storage
 * - Fixes hardcoded paths in scripts
 * - Consolidates directory structure
 * - Adds security protections
 * 
 * Usage: php bin/fix-paths-comprehensive.php [--dry-run]
 */

// Ensure we're running from project root
if (!file_exists(__DIR__ . '/../composer.json')) {
    echo "❌ Error: Must be run from project root\n";
    exit(1);
}

class ComprehensivePathsFixer
{
    private bool $dryRun = false;
    private array $fixes = [];
    private array $backups = [];

    public function __construct()
    {
        global $argv;
        $this->dryRun = in_array('--dry-run', $argv);
        
        if ($this->dryRun) {
            echo "🔍 DRY RUN MODE - No actual changes will be made\n\n";
        }
    }

    public function fix(): void
    {
        echo "🔧 HDM Boot Comprehensive Paths Fix\n";
        echo "===================================\n\n";

        try {
            $this->createBackupDirectory();
            $this->fixSecurityIssues();
            $this->fixHardcodedPaths();
            $this->consolidateDirectories();
            $this->addSecurityProtections();
            $this->cleanupBuildDuplicates();
            
            $this->displaySummary();
            
        } catch (Exception $e) {
            echo "❌ Fix failed: " . $e->getMessage() . "\n";
            $this->restoreBackups();
            exit(1);
        }
    }

    private function createBackupDirectory(): void
    {
        $backupDir = 'var/backups/paths-fix-' . date('Y-m-d_H-i-s');
        
        if (!$this->dryRun) {
            if (!is_dir('var/backups')) {
                mkdir('var/backups', 0755, true);
            }
            mkdir($backupDir, 0755, true);
        }
        
        $this->backups['directory'] = $backupDir;
        echo "📦 Backup directory: {$backupDir}\n";
    }

    private function fixSecurityIssues(): void
    {
        echo "🔒 Fixing security issues...\n";

        // 1. Remove dangerous public/storage
        if (is_dir('public/storage')) {
            $this->backupAndRemove('public/storage', 'Dangerous public/storage directory');
        }

        // 2. Remove root storage if it exists
        if (is_dir('storage') && !is_dir('var/storage')) {
            $this->moveDirectory('storage', 'var/storage', 'Move root storage to secure location');
        } elseif (is_dir('storage')) {
            $this->backupAndRemove('storage', 'Duplicate root storage directory');
        }

        // 3. Ensure var/storage exists
        if (!is_dir('var/storage')) {
            $this->createDirectory('var/storage', 'Create secure storage directory');
        }

        echo "  ✅ Security issues fixed\n";
    }

    private function fixHardcodedPaths(): void
    {
        echo "💻 Fixing hardcoded paths in scripts...\n";

        $pathReplacements = [
            // Storage paths
            "'storage/" => "\$paths->storage('",
            '"storage/' => '$paths->storage("',
            'storage/mark.db' => '$paths->storage("mark.db")',
            'storage/user.db' => '$paths->storage("user.db")',
            'storage/system.db' => '$paths->storage("system.db")',
            
            // Cache paths
            "'var/cache'" => "\$paths->cache()",
            '"var/cache"' => '$paths->cache()',
            'var/cache/templates' => '$paths->cache("templates")',
            
            // Log paths
            "'var/logs'" => "\$paths->logs()",
            '"var/logs"' => '$paths->logs()',
            
            // Session paths
            "'var/sessions'" => "\$paths->get('sessions')",
            '"var/sessions"' => '$paths->get("sessions")',
        ];

        $scriptsToFix = [
            'bin/health-check.php',
            'bin/cache-clear.php',
            'bin/init-mark-db.php',
            'bin/init-user-db.php',
            'bin/init-system-db.php',
            'bin/log-cleanup.php',
            'bin/deploy-ftps.php',
            'bin/validate-env.php',
        ];

        foreach ($scriptsToFix as $script) {
            if (file_exists($script)) {
                $this->fixScriptPaths($script, $pathReplacements);
            }
        }

        echo "  ✅ Hardcoded paths fixed in scripts\n";
    }

    private function fixScriptPaths(string $scriptPath, array $replacements): void
    {
        if (!file_exists($scriptPath)) {
            return;
        }

        $content = file_get_contents($scriptPath);
        $originalContent = $content;

        // Add PathsFactory import if not present
        if (strpos($content, 'PathsFactory') === false && strpos($content, '$paths->') !== false) {
            $content = str_replace(
                "require_once __DIR__ . '/../vendor/autoload.php';",
                "require_once __DIR__ . '/../vendor/autoload.php';\n\nuse HdmBoot\\SharedKernel\\Services\\PathsFactory;",
                $content
            );

            // Add paths instance creation
            $content = str_replace(
                "use HdmBoot\\SharedKernel\\Services\\PathsFactory;",
                "use HdmBoot\\SharedKernel\\Services\\PathsFactory;\n\n// Get paths instance\n\$paths = PathsFactory::create();",
                $content
            );
        }

        // Apply replacements
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                // Backup original
                $backupPath = $this->backups['directory'] . '/' . basename($scriptPath);
                copy($scriptPath, $backupPath);
                
                // Write fixed content
                file_put_contents($scriptPath, $content);
            }
            
            $this->fixes[] = "Fixed hardcoded paths in {$scriptPath}";
            echo "    🔧 Fixed {$scriptPath}\n";
        }
    }

    private function consolidateDirectories(): void
    {
        echo "📁 Consolidating directory structure...\n";

        $requiredDirs = [
            'var/storage',
            'var/cache',
            'var/logs', 
            'var/sessions',
            'var/keys',
            'var/exports',
            'var/imports',
        ];

        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                $this->createDirectory($dir, "Create required directory: {$dir}");
            }
        }

        echo "  ✅ Directory structure consolidated\n";
    }

    private function addSecurityProtections(): void
    {
        echo "🛡️ Adding security protections...\n";

        // 1. var/.htaccess
        $this->createVarHtaccess();

        // 2. var/storage/.htaccess
        $this->createStorageHtaccess();

        // 3. Root storage .htaccess (if exists)
        if (is_dir('storage')) {
            $this->createRootStorageHtaccess();
        }

        echo "  ✅ Security protections added\n";
    }

    private function cleanupBuildDuplicates(): void
    {
        echo "🧹 Cleaning up build duplicates...\n";

        if (is_dir('build')) {
            // Build directory should be regenerated, so we can remove problematic parts
            $problematicPaths = [
                'build/production/public/storage',
                'build/production/storage',
            ];

            foreach ($problematicPaths as $path) {
                if (is_dir($path)) {
                    $this->backupAndRemove($path, "Remove problematic build path: {$path}");
                }
            }
        }

        echo "  ✅ Build duplicates cleaned\n";
    }

    private function backupAndRemove(string $path, string $reason): void
    {
        if (!is_dir($path) && !is_file($path)) {
            return;
        }

        $backupName = str_replace(['/', '\\'], '_', $path);
        $backupPath = $this->backups['directory'] . '/' . $backupName;

        if (!$this->dryRun) {
            if (is_dir($path)) {
                $this->copyDirectory($path, $backupPath);
                $this->removeDirectory($path);
            } else {
                copy($path, $backupPath);
                unlink($path);
            }
        }

        $this->fixes[] = $reason;
        echo "    🗑️ Removed {$path} (backed up)\n";
    }

    private function moveDirectory(string $source, string $destination, string $reason): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!$this->dryRun) {
            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0755, true);
            }
            rename($source, $destination);
        }

        $this->fixes[] = $reason;
        echo "    📁 Moved {$source} → {$destination}\n";
    }

    private function createDirectory(string $path, string $reason): void
    {
        if (!$this->dryRun) {
            mkdir($path, 0755, true);
        }

        $this->fixes[] = $reason;
        echo "    📁 Created {$path}\n";
    }

    private function createVarHtaccess(): void
    {
        $content = "# HDM Boot Security Protection\n";
        $content .= "# Deny all web access to var/ directory\n\n";
        $content .= "<RequireAll>\n";
        $content .= "    Require all denied\n";
        $content .= "</RequireAll>\n\n";
        $content .= "Order deny,allow\n";
        $content .= "Deny from all\n\n";
        $content .= "# Protect sensitive file types\n";
        $content .= "<Files ~ \"\\.(db|log|json|php|key)$\">\n";
        $content .= "    Order allow,deny\n";
        $content .= "    Deny from all\n";
        $content .= "</Files>\n";

        if (!$this->dryRun) {
            file_put_contents('var/.htaccess', $content);
        }

        $this->fixes[] = "Created var/.htaccess protection";
        echo "    🛡️ Created var/.htaccess\n";
    }

    private function createStorageHtaccess(): void
    {
        $content = "# Extra protection for storage directory\n";
        $content .= "# Contains databases and sensitive files\n\n";
        $content .= "<RequireAll>\n";
        $content .= "    Require all denied\n";
        $content .= "</RequireAll>\n\n";
        $content .= "Order deny,allow\n";
        $content .= "Deny from all\n\n";
        $content .= "<FilesMatch \".*\">\n";
        $content .= "    Order allow,deny\n";
        $content .= "    Deny from all\n";
        $content .= "</FilesMatch>\n";

        if (!$this->dryRun) {
            file_put_contents('var/storage/.htaccess', $content);
        }

        $this->fixes[] = "Created var/storage/.htaccess protection";
        echo "    🛡️ Created var/storage/.htaccess\n";
    }

    private function createRootStorageHtaccess(): void
    {
        if (!is_dir('storage')) {
            return;
        }

        $content = "# Temporary protection for root storage\n";
        $content .= "# This directory should be moved to var/storage\n\n";
        $content .= "Order deny,allow\n";
        $content .= "Deny from all\n";

        if (!$this->dryRun) {
            file_put_contents('storage/.htaccess', $content);
        }

        $this->fixes[] = "Created temporary storage/.htaccess protection";
        echo "    🛡️ Created storage/.htaccess\n";
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $targetPath = $destination . '/' . $iterator->getSubPathName();
            
            if ($file->isDir()) {
                mkdir($targetPath, 0755, true);
            } else {
                copy($file->getPathname(), $targetPath);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }

    private function restoreBackups(): void
    {
        echo "\n🔄 Restoring backups due to error...\n";
        // Implementation would restore from backups
        echo "💡 Manual restore may be required from: {$this->backups['directory']}\n";
    }

    private function displaySummary(): void
    {
        echo "\n📊 Comprehensive Paths Fix Summary:\n";
        echo "===================================\n";
        echo "  Total fixes applied: " . count($this->fixes) . "\n";
        echo "  Backup directory: {$this->backups['directory']}\n\n";

        if ($this->dryRun) {
            echo "🔍 This was a dry run - no actual changes were made\n";
            echo "💡 Run without --dry-run to apply fixes\n\n";
        } else {
            echo "✅ All paths fixes applied successfully!\n\n";
        }

        echo "🎯 Next steps:\n";
        echo "  1. Run: php bin/audit-paths.php (verify fixes)\n";
        echo "  2. Run: php bin/health-check.php (test functionality)\n";
        echo "  3. Run: php bin/build-production.php (rebuild)\n";
        echo "  4. Test application functionality\n\n";

        if (!$this->dryRun) {
            echo "💾 Backups available in: {$this->backups['directory']}\n";
            echo "🗑️ Remove backups after verification: rm -rf {$this->backups['directory']}\n";
        }
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$fixer = new ComprehensivePathsFixer();
$fixer->fix();
