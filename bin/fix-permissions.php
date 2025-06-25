#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Permission Management Script.
 * 
 * Uses the centralized PermissionManager to fix file/directory permissions.
 * Supports both strict (production) and relaxed (shared hosting) modes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\SharedKernel\System\PermissionManager;
use ResponsiveSk\Slim4Paths\Paths;

// Parse command line arguments
$mode = $argv[1] ?? 'strict';
$useStrictPermissions = !in_array($mode, ['shared', 'relaxed', 'loose']);

echo "🔧 HDM Boot Permission Management\n";
echo "================================\n\n";

try {
    // Initialize Paths service
    $paths = new Paths(__DIR__ . '/..');
    
    // Initialize PermissionManager
    $permissionManager = new PermissionManager($paths, $useStrictPermissions);
    
    // Display current settings
    $info = $permissionManager->getPermissionInfo();
    echo "📋 Current Settings:\n";
    echo "   • Mode: " . ($useStrictPermissions ? 'STRICT (Production)' : 'RELAXED (Shared Hosting)') . "\n";
    echo "   • Directory permissions: {$info['default_directory_permission']}\n";
    echo "   • File permissions: {$info['default_file_permission']}\n";
    echo "   • Can use strict permissions: " . ($info['can_use_strict_permissions'] ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    // Setup system directories
    echo "📁 Setting up system directories...\n";
    $dirResults = $permissionManager->setupSystemDirectories();
    
    foreach ($dirResults['created'] as $dir) {
        echo "   ✅ Created/fixed: {$dir}\n";
    }
    
    foreach ($dirResults['errors'] as $error) {
        echo "   ❌ Error: {$error}\n";
    }
    
    // Setup log files
    echo "\n📝 Setting up log files...\n";
    $logResults = $permissionManager->setupLogFiles();
    
    foreach ($logResults['created'] as $logFile) {
        echo "   ✅ Created/fixed: {$logFile}\n";
    }
    
    foreach ($logResults['errors'] as $error) {
        echo "   ❌ Error: {$error}\n";
    }
    
    // Fix permissions for existing files
    echo "\n🔧 Fixing permissions for existing files...\n";
    
    $directories = ['var', 'storage'];
    $totalFixed = ['directories' => 0, 'files' => 0, 'errors' => []];
    
    foreach ($directories as $dir) {
        if (is_dir($paths->path($dir))) {
            echo "   📂 Processing: {$dir}/\n";
            $results = $permissionManager->fixDirectoryTreePermissions($dir);
            
            $totalFixed['directories'] += $results['directories_fixed'];
            $totalFixed['files'] += $results['files_fixed'];
            $totalFixed['errors'] = array_merge($totalFixed['errors'], $results['errors']);
            
            echo "      • Directories fixed: {$results['directories_fixed']}\n";
            echo "      • Files fixed: {$results['files_fixed']}\n";
            
            if (!empty($results['errors'])) {
                echo "      • Errors: " . count($results['errors']) . "\n";
            }
        }
    }
    
    // Summary
    echo "\n✅ Permission fix completed!\n";
    echo "================================\n";
    echo "📊 Summary:\n";
    echo "   • Total directories fixed: {$totalFixed['directories']}\n";
    echo "   • Total files fixed: {$totalFixed['files']}\n";
    echo "   • Total errors: " . count($totalFixed['errors']) . "\n";
    
    if (!empty($totalFixed['errors'])) {
        echo "\n❌ Errors encountered:\n";
        foreach (array_slice($totalFixed['errors'], 0, 5) as $error) {
            echo "   • {$error}\n";
        }
        if (count($totalFixed['errors']) > 5) {
            echo "   • ... and " . (count($totalFixed['errors']) - 5) . " more errors\n";
        }
    }
    
    echo "\n🔧 Usage:\n";
    echo "   • Production (strict): php bin/fix-permissions.php\n";
    echo "   • Shared hosting: php bin/fix-permissions.php shared\n";
    echo "\n🚀 Next steps:\n";
    echo "   1. Test application functionality\n";
    echo "   2. Check web server error logs if issues persist\n";
    echo "   3. Verify database file permissions\n";
    
    exit(0);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "🔗 Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    exit(1);
}
