#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Log Cleanup CLI Tool
 * 
 * Provides command-line interface for log management operations.
 * Usage: php bin/log-cleanup [command] [options]
 */

use MvaBootstrap\Modules\Core\Logging\Infrastructure\Services\LogCleanupService;

require_once __DIR__ . '/../vendor/autoload.php';

// Simple log cleanup without full DI container
$basePath = dirname(__DIR__);
$logPath = $basePath . '/var/logs';

// Ensure log directory exists
if (!is_dir($logPath)) {
    mkdir($logPath, 0755, true);
}

// Parse command line arguments
$command = $argv[1] ?? 'help';
$options = array_slice($argv, 2);

function showHelp(): void
{
    echo "🧹 MVA Bootstrap Core - Log Cleanup Tool\n\n";
    echo "Usage: php bin/log-cleanup [command] [options]\n\n";
    echo "Commands:\n";
    echo "  stats                    Show log directory statistics\n";
    echo "  cleanup [days]           Clean up logs older than X days (default: 30)\n";
    echo "  cleanup-dry [days]       Dry run cleanup (show what would be removed)\n";
    echo "  compress [days]          Compress logs older than X days (default: 7)\n";
    echo "  compress-dry [days]      Dry run compression (show what would be compressed)\n";
    echo "  health                   Check log rotation health\n";
    echo "  help                     Show this help message\n\n";
    echo "Examples:\n";
    echo "  php bin/log-cleanup stats\n";
    echo "  php bin/log-cleanup cleanup 14\n";
    echo "  php bin/log-cleanup compress-dry 3\n";
    echo "  php bin/log-cleanup health\n\n";
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    } elseif ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function getLogStats(string $logPath): array
{
    $stats = [
        'total_files' => 0,
        'total_size_bytes' => 0,
        'files_by_type' => [],
        'oldest_file' => null,
        'newest_file' => null,
    ];

    if (!is_dir($logPath)) {
        return $stats;
    }

    $files = glob($logPath . '/*.log*');
    if (!$files) {
        return $stats;
    }

    $oldestTime = PHP_INT_MAX;
    $newestTime = 0;

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $size = filesize($file);
        $mtime = filemtime($file);
        $basename = basename($file);

        $stats['total_files']++;
        $stats['total_size_bytes'] += $size;

        if ($mtime < $oldestTime) {
            $oldestTime = $mtime;
            $stats['oldest_file'] = [
                'name' => $basename,
                'date' => date('Y-m-d H:i:s', $mtime),
                'size_mb' => round($size / (1024 * 1024), 2),
            ];
        }

        if ($mtime > $newestTime) {
            $newestTime = $mtime;
            $stats['newest_file'] = [
                'name' => $basename,
                'date' => date('Y-m-d H:i:s', $mtime),
                'size_mb' => round($size / (1024 * 1024), 2),
            ];
        }

        $type = getLogType($basename);
        if (!isset($stats['files_by_type'][$type])) {
            $stats['files_by_type'][$type] = ['count' => 0, 'size_bytes' => 0];
        }

        $stats['files_by_type'][$type]['count']++;
        $stats['files_by_type'][$type]['size_bytes'] += $size;
    }

    return $stats;
}

function getLogType(string $filename): string
{
    if (str_contains($filename, 'security')) return 'security';
    if (str_contains($filename, 'performance')) return 'performance';
    if (str_contains($filename, 'audit')) return 'audit';
    if (str_contains($filename, 'error')) return 'error';
    if (str_contains($filename, 'debug')) return 'debug';
    return 'general';
}

function cleanupOldLogs(string $logPath, int $daysToKeep = 30, bool $dryRun = false): array
{
    $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

    $result = [
        'files_removed' => 0,
        'bytes_freed' => 0,
        'files_processed' => [],
        'errors' => [],
    ];

    if (!is_dir($logPath)) {
        $result['errors'][] = "Log directory does not exist: {$logPath}";
        return $result;
    }

    $files = glob($logPath . '/*.log*');
    if (!$files) {
        return $result;
    }

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $mtime = filemtime($file);
        $size = filesize($file);
        $basename = basename($file);

        if ($mtime < $cutoffTime) {
            $result['files_processed'][] = [
                'name' => $basename,
                'date' => date('Y-m-d H:i:s', $mtime),
                'size_mb' => round($size / (1024 * 1024), 2),
                'action' => $dryRun ? 'would_remove' : 'removed',
            ];

            if (!$dryRun) {
                if (unlink($file)) {
                    $result['files_removed']++;
                    $result['bytes_freed'] += $size;
                } else {
                    $result['errors'][] = "Failed to remove: {$basename}";
                }
            }
        }
    }

    return $result;
}

function compressOldLogs(string $logPath, int $daysOld = 7, bool $dryRun = false): array
{
    $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

    $result = [
        'files_compressed' => 0,
        'bytes_saved' => 0,
        'files_processed' => [],
        'errors' => [],
    ];

    if (!function_exists('gzencode')) {
        $result['errors'][] = 'GZ compression not available';
        return $result;
    }

    if (!is_dir($logPath)) {
        $result['errors'][] = "Log directory does not exist: {$logPath}";
        return $result;
    }

    $files = glob($logPath . '/*.log');
    if (!$files) {
        return $result;
    }

    foreach ($files as $file) {
        if (!is_file($file) || str_ends_with($file, '.gz')) {
            continue;
        }

        $mtime = filemtime($file);
        $basename = basename($file);

        if ($mtime < $cutoffTime) {
            $originalSize = filesize($file);

            if (!$dryRun) {
                $content = file_get_contents($file);
                if ($content === false) {
                    $result['errors'][] = "Failed to read: {$basename}";
                    continue;
                }

                $compressed = gzencode($content, 9);
                if ($compressed === false) {
                    $result['errors'][] = "Failed to compress: {$basename}";
                    continue;
                }

                $compressedFile = $file . '.gz';
                if (file_put_contents($compressedFile, $compressed) === false) {
                    $result['errors'][] = "Failed to write compressed: {$basename}.gz";
                    continue;
                }

                if (unlink($file)) {
                    $compressedSize = filesize($compressedFile);
                    $saved = $originalSize - $compressedSize;

                    $result['files_compressed']++;
                    $result['bytes_saved'] += $saved;
                } else {
                    $result['errors'][] = "Failed to remove original: {$basename}";
                    @unlink($compressedFile); // Cleanup compressed file
                }
            }

            $result['files_processed'][] = [
                'name' => $basename,
                'date' => date('Y-m-d H:i:s', $mtime),
                'size_mb' => round($originalSize / (1024 * 1024), 2),
                'action' => $dryRun ? 'would_compress' : 'compressed',
            ];
        }
    }

    return $result;
}

function showStats(string $logPath): void
{
    echo "📊 Log Directory Statistics\n";
    echo str_repeat('=', 50) . "\n";

    $stats = getLogStats($logPath);

    echo "Total Files: {$stats['total_files']}\n";
    echo "Total Size: " . formatBytes($stats['total_size_bytes']) . "\n";

    if ($stats['total_size_bytes'] > (100 * 1024 * 1024)) {
        echo "⚠️  WARNING: High disk usage!\n";
    }

    echo "\nFiles by Type:\n";
    foreach ($stats['files_by_type'] as $type => $info) {
        echo "  {$type}: {$info['count']} files, " . formatBytes($info['size_bytes']) . "\n";
    }

    if ($stats['oldest_file']) {
        echo "\nOldest File: {$stats['oldest_file']['name']} ({$stats['oldest_file']['date']})\n";
    }

    if ($stats['newest_file']) {
        echo "Newest File: {$stats['newest_file']['name']} ({$stats['newest_file']['date']})\n";
    }

    echo "\n";
}

function showHealth(string $logPath): void
{
    echo "🏥 Log Directory Health Check\n";
    echo str_repeat('=', 50) . "\n";

    $stats = getLogStats($logPath);
    $issues = [];
    $recommendations = [];

    // Check for large files (>50MB)
    foreach ($stats['files_by_type'] as $type => $info) {
        $sizeMB = round($info['size_bytes'] / (1024 * 1024), 2);
        if ($sizeMB > 50) {
            $issues[] = "Large {$type} logs: {$sizeMB}MB";
            $recommendations[] = "Consider more frequent rotation for {$type} logs";
        }
    }

    // Check total disk usage
    if ($stats['total_size_bytes'] > (100 * 1024 * 1024)) {
        $totalMB = round($stats['total_size_bytes'] / (1024 * 1024), 2);
        $issues[] = "High disk usage: {$totalMB}MB";
        $recommendations[] = 'Run log cleanup or compression';
    }

    // Check for very old files
    if ($stats['oldest_file']) {
        $oldestDate = strtotime($stats['oldest_file']['date']);
        $ageInDays = (time() - $oldestDate) / (24 * 60 * 60);

        if ($ageInDays > 60) {
            $issues[] = "Very old log file: {$stats['oldest_file']['name']} (" . round($ageInDays) . " days)";
            $recommendations[] = 'Clean up old log files';
        }
    }

    $status = empty($issues) ? 'healthy' : 'warning';
    $statusIcon = $status === 'healthy' ? '✅' : '⚠️';

    echo "Status: {$statusIcon} {$status}\n";

    if (!empty($issues)) {
        echo "\nIssues found:\n";
        foreach ($issues as $issue) {
            echo "  ⚠️  {$issue}\n";
        }
    }

    if (!empty($recommendations)) {
        echo "\nRecommendations:\n";
        foreach ($recommendations as $recommendation) {
            echo "  💡 {$recommendation}\n";
        }
    }

    if ($status === 'healthy') {
        echo "\n✅ All log systems are working properly!\n";
    }

    echo "\n";
}

function showCleanupResult(array $result, bool $dryRun): void
{
    if ($dryRun) {
        echo "🔍 Dry Run - Files that would be removed:\n";
    } else {
        echo "🧹 Cleanup completed:\n";
    }
    
    echo str_repeat('=', 50) . "\n";
    
    if ($dryRun) {
        echo "Files to remove: " . count($result['files_processed']) . "\n";
        $totalSize = array_sum(array_column($result['files_processed'], 'size_mb'));
        echo "Space to free: {$totalSize} MB\n";
    } else {
        echo "Files removed: {$result['files_removed']}\n";
        echo "Space freed: " . formatBytes($result['bytes_freed']) . "\n";
    }
    
    if (!empty($result['files_processed'])) {
        echo "\nProcessed files:\n";
        foreach ($result['files_processed'] as $file) {
            $action = $dryRun ? '(would remove)' : '(removed)';
            echo "  {$file['name']} - {$file['size_mb']} MB {$action}\n";
        }
    }
    
    if (!empty($result['errors'])) {
        echo "\nErrors:\n";
        foreach ($result['errors'] as $error) {
            echo "  ❌ {$error}\n";
        }
    }
    
    echo "\n";
}

function showCompressionResult(array $result, bool $dryRun): void
{
    if ($dryRun) {
        echo "🔍 Dry Run - Files that would be compressed:\n";
    } else {
        echo "📦 Compression completed:\n";
    }
    
    echo str_repeat('=', 50) . "\n";
    
    if ($dryRun) {
        echo "Files to compress: " . count($result['files_processed']) . "\n";
    } else {
        echo "Files compressed: {$result['files_compressed']}\n";
        echo "Space saved: " . formatBytes($result['bytes_saved']) . "\n";
    }
    
    if (!empty($result['files_processed'])) {
        echo "\nProcessed files:\n";
        foreach ($result['files_processed'] as $file) {
            $action = $dryRun ? '(would compress)' : '(compressed)';
            echo "  {$file['name']} - {$file['size_mb']} MB {$action}\n";
        }
    }
    
    if (!empty($result['errors'])) {
        echo "\nErrors:\n";
        foreach ($result['errors'] as $error) {
            echo "  ❌ {$error}\n";
        }
    }
    
    echo "\n";
}



// Execute command
try {
    switch ($command) {
        case 'stats':
            showStats($logPath);
            break;
            
        case 'cleanup':
            $days = isset($options[0]) ? (int)$options[0] : 30;
            echo "🧹 Cleaning up logs older than {$days} days...\n\n";
            $result = cleanupOldLogs($logPath, $days, false);
            showCleanupResult($result, false);
            break;

        case 'cleanup-dry':
            $days = isset($options[0]) ? (int)$options[0] : 30;
            echo "🔍 Dry run: Checking logs older than {$days} days...\n\n";
            $result = cleanupOldLogs($logPath, $days, true);
            showCleanupResult($result, true);
            break;

        case 'compress':
            $days = isset($options[0]) ? (int)$options[0] : 7;
            echo "📦 Compressing logs older than {$days} days...\n\n";
            $result = compressOldLogs($logPath, $days, false);
            showCompressionResult($result, false);
            break;

        case 'compress-dry':
            $days = isset($options[0]) ? (int)$options[0] : 7;
            echo "🔍 Dry run: Checking logs for compression older than {$days} days...\n\n";
            $result = compressOldLogs($logPath, $days, true);
            showCompressionResult($result, true);
            break;

        case 'health':
            showHealth($logPath);
            break;
            
        case 'help':
        default:
            showHelp();
            break;
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
