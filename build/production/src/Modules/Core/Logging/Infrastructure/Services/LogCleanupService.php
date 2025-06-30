<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Logging\Infrastructure\Services;

use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Log Cleanup Service.
 *
 * Handles manual log cleanup, rotation monitoring, and disk space management.
 * Provides enterprise-grade log management capabilities.
 */
final class LogCleanupService
{
    private const BYTES_PER_MB = 1024 * 1024;

    public function __construct(
        private readonly Paths $paths,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get log directory statistics.
     *
     * @return array{
     *     total_files: int,
     *     total_size_bytes: int,
     *     total_size_mb: float,
     *     oldest_file: array{name: string, date: string, size_mb: float}|null,
     *     newest_file: array{name: string, date: string, size_mb: float}|null,
     *     files_by_type: array<string, array{count: int, size_bytes: int, size_mb: float}>,
     *     disk_usage_warning: bool
     * }
     */
    public function getLogStats(): array
    {
        $logPath = $this->paths->logs();
        $stats = [
            'total_files'        => 0,
            'total_size_bytes'   => 0,
            'total_size_mb'      => 0,
            'oldest_file'        => null,
            'newest_file'        => null,
            'files_by_type'      => [],
            'disk_usage_warning' => false,
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

            if ($size === false || $mtime === false) {
                continue;
            }

            ++$stats['total_files'];
            $stats['total_size_bytes'] += $size;

            // Track oldest and newest
            if ($mtime < $oldestTime) {
                $oldestTime = $mtime;
                $stats['oldest_file'] = [
                    'name'    => $basename,
                    'date'    => date('Y-m-d H:i:s', $mtime),
                    'size_mb' => round($size / self::BYTES_PER_MB, 2),
                ];
            }

            if ($mtime > $newestTime) {
                $newestTime = $mtime;
                $stats['newest_file'] = [
                    'name'    => $basename,
                    'date'    => date('Y-m-d H:i:s', $mtime),
                    'size_mb' => round($size / self::BYTES_PER_MB, 2),
                ];
            }

            // Categorize by type
            $type = $this->getLogType($basename);
            if (!isset($stats['files_by_type'][$type])) {
                $stats['files_by_type'][$type] = [
                    'count'      => 0,
                    'size_bytes' => 0,
                    'size_mb'    => 0,
                ];
            }

            ++$stats['files_by_type'][$type]['count'];
            $stats['files_by_type'][$type]['size_bytes'] += $size;
            $stats['files_by_type'][$type]['size_mb'] = round(
                $stats['files_by_type'][$type]['size_bytes'] / self::BYTES_PER_MB,
                2
            );
        }

        $stats['total_size_mb'] = round($stats['total_size_bytes'] / self::BYTES_PER_MB, 2);

        // Check for disk usage warning (>100MB)
        $stats['disk_usage_warning'] = $stats['total_size_bytes'] > (100 * self::BYTES_PER_MB);

        return $stats;
    }

    /**
     * Clean up old log files manually.
     *
     * @return array{
     *     files_removed: int,
     *     bytes_freed: int,
     *     files_processed: array<array{name: string, date: string, size_mb: float, action: string}>,
     *     errors: array<string>
     * }
     */
    public function cleanupOldLogs(int $daysToKeep = 30, bool $dryRun = false): array
    {
        $logPath = $this->paths->logs();
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

        $result = [
            'files_removed'   => 0,
            'bytes_freed'     => 0,
            'files_processed' => [],
            'errors'          => [],
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

            if ($mtime === false || $size === false) {
                continue;
            }

            if ($mtime < $cutoffTime) {
                $result['files_processed'][] = [
                    'name'    => $basename,
                    'date'    => date('Y-m-d H:i:s', $mtime),
                    'size_mb' => round($size / self::BYTES_PER_MB, 2),
                    'action'  => $dryRun ? 'would_remove' : 'removed',
                ];

                if (!$dryRun) {
                    if (unlink($file)) {
                        ++$result['files_removed'];
                        $result['bytes_freed'] += $size;

                        $this->logger->info('Log file cleaned up', [
                            'file'     => $basename,
                            'age_days' => round((time() - $mtime) / (24 * 60 * 60)),
                            'size_mb'  => round($size / self::BYTES_PER_MB, 2),
                        ]);
                    } else {
                        $result['errors'][] = "Failed to remove: {$basename}";
                    }
                }
            }
        }

        if (!$dryRun && $result['files_removed'] > 0) {
            $this->logger->info('Log cleanup completed', [
                'files_removed' => $result['files_removed'],
                'bytes_freed'   => $result['bytes_freed'],
                'mb_freed'      => round($result['bytes_freed'] / self::BYTES_PER_MB, 2),
                'days_to_keep'  => $daysToKeep,
            ]);
        }

        return $result;
    }

    /**
     * Compress old log files to save space.
     *
     * @return array{
     *     files_compressed: int,
     *     bytes_saved: int,
     *     files_processed: array<array{name: string, date: string, size_mb: float, action: string}>,
     *     errors: array<string>
     * }
     */
    public function compressOldLogs(int $daysOld = 7, bool $dryRun = false): array
    {
        $logPath = $this->paths->logs();
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

        $result = [
            'files_compressed' => 0,
            'bytes_saved'      => 0,
            'files_processed'  => [],
            'errors'           => [],
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

            if ($mtime === false) {
                continue;
            }

            if ($mtime < $cutoffTime) {
                $originalSize = filesize($file);

                if ($originalSize === false) {
                    continue;
                }

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

                        ++$result['files_compressed'];
                        $result['bytes_saved'] += $saved;

                        $this->logger->info('Log file compressed', [
                            'file'               => $basename,
                            'original_size_mb'   => round($originalSize / self::BYTES_PER_MB, 2),
                            'compressed_size_mb' => round($compressedSize / self::BYTES_PER_MB, 2),
                            'bytes_saved'        => $saved,
                            'compression_ratio'  => round(($saved / $originalSize) * 100, 1) . '%',
                        ]);
                    } else {
                        $result['errors'][] = "Failed to remove original: {$basename}";
                        @unlink($compressedFile); // Cleanup compressed file
                    }
                }

                $result['files_processed'][] = [
                    'name'    => $basename,
                    'date'    => date('Y-m-d H:i:s', $mtime),
                    'size_mb' => round($originalSize / self::BYTES_PER_MB, 2),
                    'action'  => $dryRun ? 'would_compress' : 'compressed',
                ];
            }
        }

        return $result;
    }

    /**
     * Get log type from filename.
     */
    private function getLogType(string $filename): string
    {
        if (str_contains($filename, 'security')) {
            return 'security';
        }
        if (str_contains($filename, 'performance')) {
            return 'performance';
        }
        if (str_contains($filename, 'audit')) {
            return 'audit';
        }
        if (str_contains($filename, 'error')) {
            return 'error';
        }
        if (str_contains($filename, 'debug')) {
            return 'debug';
        }

        return 'general';
    }

    /**
     * Check if log rotation is working properly.
     *
     * @return array{
     *     status: string,
     *     issues: array<string>,
     *     recommendations: array<string>
     * }
     */
    public function checkRotationHealth(): array
    {
        $stats = $this->getLogStats();
        $health = [
            'status'          => 'healthy',
            'issues'          => [],
            'recommendations' => [],
        ];

        // Check for very large files (>50MB)
        foreach ($stats['files_by_type'] as $type => $info) {
            $sizeMB = round($info['size_bytes'] / self::BYTES_PER_MB, 2);
            if ($sizeMB > 50) {
                $health['issues'][] = "Large {$type} logs: {$sizeMB}MB";
                $health['recommendations'][] = "Consider more frequent rotation for {$type} logs";
            }
        }

        // Check total disk usage
        if ($stats['disk_usage_warning']) {
            $totalSizeMB = round($stats['total_size_bytes'] / self::BYTES_PER_MB, 2);
            $health['issues'][] = "High disk usage: {$totalSizeMB}MB";
            $health['recommendations'][] = 'Run log cleanup or compression';
        }

        // Check for very old files
        if ($stats['oldest_file'] !== null) {
            $oldestDate = strtotime($stats['oldest_file']['date']);
            if ($oldestDate !== false) {
                $ageInDays = (time() - $oldestDate) / (24 * 60 * 60);

                if ($ageInDays > 60) {
                    $health['issues'][] = "Very old log file: {$stats['oldest_file']['name']} (" . round($ageInDays) . ' days)';
                    $health['recommendations'][] = 'Clean up old log files';
                }
            }
        }

        if (!empty($health['issues'])) {
            $health['status'] = 'warning';
        }

        return $health;
    }
}
