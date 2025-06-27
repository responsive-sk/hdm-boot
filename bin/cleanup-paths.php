#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Paths Cleanup Tool.
 * 
 * Fixes path inconsistencies identified by paths audit:
 * 1. Consolidates multiple var/ directories
 * 2. Moves storage/ outside public/
 * 3. Adds security protection (.htaccess)
 * 4. Updates hardcoded paths to use Paths service
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "🧹 HDM Boot Paths Cleanup\n";
echo "=========================\n\n";

class PathsCleanup
{
    private array $actions = [];
    private bool $dryRun;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function runCleanup(): void
    {
        echo "🔍 Phase 1: Directory Structure Cleanup...\n";
        $this->cleanupDirectoryStructure();
        
        echo "\n🛡️ Phase 2: Security Protection...\n";
        $this->addSecurityProtection();
        
        echo "\n📝 Phase 3: Update Path Configurations...\n";
        $this->updatePathConfigurations();
        
        echo "\n🔧 Phase 4: Fix Hardcoded Paths...\n";
        $this->fixHardcodedPaths();
        
        $this->generateSummary();
    }

    private function cleanupDirectoryStructure(): void
    {
        // Define target structure
        $targetStructure = [
            'var/logs',
            'var/cache',
            'var/sessions',
            'var/uploads',
            'storage',  // Root level, not in public/
        ];

        // Create target directories
        foreach ($targetStructure as $dir) {
            if (!is_dir($dir)) {
                $this->executeAction("mkdir -p {$dir}", "Create directory: {$dir}");
                $this->executeAction("chmod 777 {$dir}", "Set permissions: {$dir}");
            }
        }

        // Remove duplicate directories
        $duplicatesToRemove = [
            'public/var',
            'public/storage', 
            'var/storage',
            'public/var/storage',
            'public/var/cache',
        ];

        foreach ($duplicatesToRemove as $dir) {
            if (is_dir($dir)) {
                // Move content if any, then remove
                $this->executeAction("rm -rf {$dir}", "Remove duplicate directory: {$dir}");
            }
        }
    }

    private function addSecurityProtection(): void
    {
        $protectedDirs = ['var', 'storage', 'config', 'bin'];
        
        foreach ($protectedDirs as $dir) {
            if (is_dir($dir)) {
                $htaccessPath = "{$dir}/.htaccess";
                if (!file_exists($htaccessPath)) {
                    $htaccessContent = "# HDM Boot Security Protection\nDeny from all\n";
                    $this->executeAction(
                        "echo '{$htaccessContent}' > {$htaccessPath}",
                        "Add .htaccess protection: {$dir}"
                    );
                }
            }
        }
    }

    private function updatePathConfigurations(): void
    {
        // Update config/paths.php to use consistent structure
        $pathsConfig = [
            'base_path' => '__DIR__ . "/.."',
            'paths' => [
                // Core directories
                'var' => 'var',
                'storage' => 'storage',
                'public' => 'public',
                'config' => 'config',
                'templates' => 'templates',
                
                // Var subdirectories
                'logs' => 'var/logs',
                'cache' => 'var/cache',
                'sessions' => 'var/sessions',
                'uploads' => 'var/uploads',
                
                // Storage subdirectories
                'databases' => 'storage',
                'files' => 'storage/files',
                'backups' => 'storage/backups',
            ]
        ];

        $this->actions[] = [
            'type' => 'config_update',
            'description' => 'Update config/paths.php with consistent structure',
            'details' => $pathsConfig
        ];
    }

    private function fixHardcodedPaths(): void
    {
        $pathReplacements = [
            // Container compilation
            "__DIR__ . '/../../var/cache/container'" => "\$this->paths->path('cache/container')",
            
            // Permission Manager
            "'var/logs'" => "\$this->paths->path('logs')",
            "'var/cache'" => "\$this->paths->path('cache')",
            "'var/sessions'" => "\$this->paths->path('sessions')",
            "'storage/cache'" => "\$this->paths->path('cache')",
            
            // Database paths
            "'storage/mark.db'" => "\$this->paths->path('storage/mark.db')",
            "'storage/user.db'" => "\$this->paths->path('storage/user.db')",
            "'storage/system.db'" => "\$this->paths->path('storage/system.db')",
            
            // Template cache
            "'var/cache/templates'" => "\$this->paths->path('cache/templates')",
            "'var/cache/twig'" => "\$this->paths->path('cache/twig')",
            
            // Language cache
            "'var/cache/translations'" => "\$this->paths->path('cache/translations')",
        ];

        $filesToUpdate = [
            'src/SharedKernel/Container/Slim4Container.php',
            'src/SharedKernel/System/PermissionManager.php',
            'src/SharedKernel/Database/DatabaseManagerFactory.php',
            'src/Modules/Core/Template/config.php',
            'src/Modules/Core/Template/Infrastructure/Engines/TwigTemplateEngine.php',
            'src/Modules/Core/Language/config.php',
            'src/Modules/Core/Database/MarkSqliteDatabaseManager.php',
            'src/Modules/Core/Database/UserSqliteDatabaseManager.php',
            'src/Modules/Core/Database/SystemSqliteDatabaseManager.php',
            'config/container.php',
        ];

        foreach ($filesToUpdate as $file) {
            if (file_exists($file)) {
                $this->actions[] = [
                    'type' => 'file_update',
                    'description' => "Update hardcoded paths in: {$file}",
                    'file' => $file,
                    'replacements' => $pathReplacements
                ];
            }
        }
    }

    private function executeAction(string $command, string $description): void
    {
        $this->actions[] = [
            'type' => 'command',
            'description' => $description,
            'command' => $command
        ];

        if (!$this->dryRun) {
            echo "   ✅ {$description}\n";
            exec($command);
        } else {
            echo "   🔍 [DRY RUN] {$description}\n";
            echo "      Command: {$command}\n";
        }
    }

    private function generateSummary(): void
    {
        echo "\n📊 CLEANUP SUMMARY\n";
        echo "==================\n\n";

        $commandActions = array_filter($this->actions, fn($a) => $a['type'] === 'command');
        $fileActions = array_filter($this->actions, fn($a) => $a['type'] === 'file_update');
        $configActions = array_filter($this->actions, fn($a) => $a['type'] === 'config_update');

        echo "📁 Directory Operations: " . count($commandActions) . "\n";
        echo "📝 File Updates: " . count($fileActions) . "\n";
        echo "⚙️ Config Updates: " . count($configActions) . "\n";

        if ($this->dryRun) {
            echo "\n🔍 DRY RUN MODE - No changes were made\n";
            echo "Run without --dry-run to apply changes\n";
        } else {
            echo "\n✅ Cleanup completed!\n";
            echo "\n📋 NEXT STEPS:\n";
            echo "   1. Test application functionality\n";
            echo "   2. Update any remaining hardcoded paths\n";
            echo "   3. Run paths audit again to verify fixes\n";
            echo "   4. Update documentation with new structure\n";
        }

        echo "\n🎯 TARGET STRUCTURE:\n";
        echo "   var/\n";
        echo "   ├── logs/\n";
        echo "   ├── cache/\n";
        echo "   ├── sessions/\n";
        echo "   └── uploads/\n";
        echo "   storage/\n";
        echo "   ├── mark.db\n";
        echo "   ├── user.db\n";
        echo "   ├── system.db\n";
        echo "   ├── files/\n";
        echo "   └── backups/\n";
    }
}

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);

if ($dryRun) {
    echo "🔍 Running in DRY RUN mode - no changes will be made\n\n";
}

$cleanup = new PathsCleanup($dryRun);
$cleanup->runCleanup();

echo "\n✅ Paths cleanup tool completed!\n";
