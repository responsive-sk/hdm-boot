#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cache Clear Script
 * 
 * Clears various types of cache in HDM Boot application.
 * Supports different cache types and provides detailed feedback.
 * 
 * Usage: php bin/cache-clear.php [type] [options]
 * 
 * Types:
 *   all        Clear all cache types (default)
 *   app        Application cache
 *   template   Template cache
 *   opcache    OPcache
 *   session    Session cache
 *   logs       Log files (old)
 * 
 * Options:
 *   --force    Force clear even if cache is locked
 *   --verbose  Show detailed information
 *   --dry-run  Show what would be cleared without actually clearing
 */

// Ensure we're running from project root
if (!file_exists(__DIR__ . '/../composer.json')) {
    echo "❌ Error: Must be run from project root\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

class CacheClearer
{
    private string $cacheType;
    private bool $force = false;
    private bool $verbose = false;
    private bool $dryRun = false;
    private array $stats = [];

    public function __construct()
    {
        $this->parseArguments();
    }

    public function clear(): void
    {
        echo "🧹 HDM Boot Cache Clearer\n";
        echo "========================\n\n";

        if ($this->dryRun) {
            echo "🔍 DRY RUN MODE - No actual changes will be made\n\n";
        }

        $startTime = microtime(true);

        try {
            match ($this->cacheType) {
                'all' => $this->clearAllCache(),
                'app' => $this->clearApplicationCache(),
                'template' => $this->clearTemplateCache(),
                'opcache' => $this->clearOpcache(),
                'session' => $this->clearSessionCache(),
                'logs' => $this->clearOldLogs(),
                default => throw new InvalidArgumentException("Unknown cache type: {$this->cacheType}")
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->displaySummary($duration);

        } catch (Exception $e) {
            echo "❌ Cache clear failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function parseArguments(): void
    {
        global $argv;

        // Find cache type (first non-option argument)
        $this->cacheType = 'all';
        for ($i = 1; $i < count($argv); $i++) {
            if (!str_starts_with($argv[$i], '--')) {
                $this->cacheType = $argv[$i];
                break;
            }
        }

        // Parse options
        foreach ($argv as $arg) {
            if ($arg === '--force') {
                $this->force = true;
            }
            if ($arg === '--verbose') {
                $this->verbose = true;
            }
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            }
        }
    }

    private function clearAllCache(): void
    {
        echo "🗑️  Clearing all cache types...\n\n";

        $this->clearApplicationCache();
        $this->clearTemplateCache();
        $this->clearOpcache();
        $this->clearSessionCache();
        $this->clearOldLogs();
    }

    private function clearApplicationCache(): void
    {
        echo "📱 Clearing application cache...\n";

        $cacheDir = $paths->cache();
        
        if (!is_dir($cacheDir)) {
            echo "  ℹ️  Cache directory not found\n";
            return;
        }

        $cleared = $this->clearDirectory($cacheDir, ['*.cache', '*.tmp']);
        $this->stats['app_cache'] = $cleared;

        echo "  ✅ Application cache cleared ({$cleared} files)\n";
    }

    private function clearTemplateCache(): void
    {
        echo "🎨 Clearing template cache...\n";

        $templateCacheDir = '$paths->cache("templates")';
        
        if (!is_dir($templateCacheDir)) {
            echo "  ℹ️  Template cache directory not found\n";
            return;
        }

        $cleared = $this->clearDirectory($templateCacheDir, ['*.php', '*.cache']);
        $this->stats['template_cache'] = $cleared;

        echo "  ✅ Template cache cleared ({$cleared} files)\n";
    }

    private function clearOpcache(): void
    {
        echo "⚡ Clearing OPcache...\n";

        if (!extension_loaded('opcache')) {
            echo "  ⚠️  OPcache extension not loaded\n";
            return;
        }

        if ($this->dryRun) {
            echo "  [DRY RUN] Would clear OPcache\n";
            return;
        }

        $status = opcache_get_status();
        if (!$status || !$status['opcache_enabled']) {
            echo "  ℹ️  OPcache not enabled\n";
            return;
        }

        $beforeStats = $status['opcache_statistics'];
        
        if (opcache_reset()) {
            $this->stats['opcache'] = [
                'scripts_cached' => $beforeStats['num_cached_scripts'] ?? 0,
                'memory_used' => $beforeStats['memory_usage']['used_memory'] ?? 0,
            ];
            echo "  ✅ OPcache cleared\n";
        } else {
            echo "  ❌ Failed to clear OPcache\n";
        }
    }

    private function clearSessionCache(): void
    {
        echo "🔐 Clearing session cache...\n";

        $sessionDir = $paths->get('sessions');
        
        if (!is_dir($sessionDir)) {
            echo "  ℹ️  Session directory not found\n";
            return;
        }

        // Only clear old sessions (older than 24 hours)
        $cleared = $this->clearOldFiles($sessionDir, 86400, ['sess_*']);
        $this->stats['session_cache'] = $cleared;

        echo "  ✅ Old sessions cleared ({$cleared} files)\n";
    }

    private function clearOldLogs(): void
    {
        echo "📝 Clearing old log files...\n";

        $logDir = $paths->logs();
        
        if (!is_dir($logDir)) {
            echo "  ℹ️  Log directory not found\n";
            return;
        }

        // Clear logs older than 30 days
        $cleared = $this->clearOldFiles($logDir, 30 * 86400, ['*.log', '*.log.*']);
        $this->stats['old_logs'] = $cleared;

        echo "  ✅ Old logs cleared ({$cleared} files)\n";
    }

    private function clearDirectory(string $dir, array $patterns = ['*']): int
    {
        $cleared = 0;

        foreach ($patterns as $pattern) {
            $files = glob($dir . '/' . $pattern);
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    if ($this->verbose) {
                        echo "    🗑️  " . basename($file) . "\n";
                    }
                    
                    if (!$this->dryRun) {
                        if (unlink($file)) {
                            $cleared++;
                        }
                    } else {
                        $cleared++;
                    }
                }
            }
        }

        // Clear subdirectories recursively
        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $cleared += $this->clearDirectory($subdir, $patterns);
        }

        return $cleared;
    }

    private function clearOldFiles(string $dir, int $maxAge, array $patterns = ['*']): int
    {
        $cleared = 0;
        $cutoffTime = time() - $maxAge;

        foreach ($patterns as $pattern) {
            $files = glob($dir . '/' . $pattern);
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    if ($this->verbose) {
                        $age = round((time() - filemtime($file)) / 86400, 1);
                        echo "    🗑️  " . basename($file) . " (age: {$age} days)\n";
                    }
                    
                    if (!$this->dryRun) {
                        if (unlink($file)) {
                            $cleared++;
                        }
                    } else {
                        $cleared++;
                    }
                }
            }
        }

        return $cleared;
    }

    private function displaySummary(float $duration): void
    {
        echo "\n📊 Cache Clear Summary:\n";
        echo "=====================\n";

        $totalFiles = 0;
        
        foreach ($this->stats as $type => $count) {
            $displayName = match ($type) {
                'app_cache' => 'Application Cache',
                'template_cache' => 'Template Cache',
                'session_cache' => 'Session Cache',
                'old_logs' => 'Old Log Files',
                'opcache' => 'OPcache',
                default => ucfirst(str_replace('_', ' ', $type))
            };

            if ($type === 'opcache' && is_array($count)) {
                echo "  ⚡ {$displayName}: {$count['scripts_cached']} scripts, " . 
                     round($count['memory_used'] / 1024 / 1024, 2) . "MB memory\n";
            } else {
                echo "  🗑️  {$displayName}: {$count} files\n";
                $totalFiles += $count;
            }
        }

        echo "\n";
        echo "  📁 Total files cleared: {$totalFiles}\n";
        echo "  ⏱️  Duration: {$duration}ms\n";

        if ($this->dryRun) {
            echo "\n🔍 This was a dry run - no files were actually deleted\n";
            echo "💡 Run without --dry-run to perform actual cleanup\n";
        } else {
            echo "\n✅ Cache clearing completed successfully!\n";
        }

        // Recommendations
        if (!empty($this->stats)) {
            echo "\n💡 Recommendations:\n";
            
            if (($this->stats['app_cache'] ?? 0) > 100) {
                echo "  • Consider implementing cache expiration policies\n";
            }
            
            if (($this->stats['old_logs'] ?? 0) > 50) {
                echo "  • Consider setting up automatic log rotation\n";
            }
            
            if (isset($this->stats['opcache']) && is_array($this->stats['opcache'])) {
                echo "  • OPcache was reset - performance may be temporarily reduced\n";
            }
        }
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$cacheClearer = new CacheClearer();
$cacheClearer->clear();
