<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks;

use HdmBoot\SharedKernel\HealthChecks\Contracts\HealthCheckInterface;
use HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult;
use HdmBoot\SharedKernel\Services\PathsFactory;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Filesystem Health Check.
 *
 * Checks filesystem accessibility and disk space.
 */
final class FilesystemHealthCheck implements HealthCheckInterface
{
    private const CRITICAL_DISK_USAGE_THRESHOLD = 90; // 90%
    private const WARNING_DISK_USAGE_THRESHOLD = 80;  // 80%

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Paths $paths
    ) {
    }

    public function getName(): string
    {
        return 'filesystem';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $checks = [
                'log_directory'     => $this->checkLogDirectory(),
                'temp_directory'    => $this->checkTempDirectory(),
                'cache_directory'   => $this->checkCacheDirectory(),
                'disk_space'        => $this->checkDiskSpace(),
                'write_permissions' => $this->checkWritePermissions(),
            ];

            $duration = microtime(true) - $startTime;

            // Check for any failures
            $failures = array_filter($checks, fn ($check) => !$check['success']);

            if (!empty($failures)) {
                $failureMessages = array_map(fn ($check) => $check['message'], $failures);

                return HealthCheckResult::unhealthy(
                    $this->getName(),
                    'Filesystem checks failed: ' . implode(', ', $failureMessages),
                    $checks,
                    $duration
                );
            }

            // Check for warnings (disk space)
            $diskUsageValue = $checks['disk_space']['usage_percentage'] ?? 0;
            $diskUsage = is_numeric($diskUsageValue) ? (float) $diskUsageValue : 0.0;
            if ($diskUsage >= self::WARNING_DISK_USAGE_THRESHOLD) {
                return HealthCheckResult::degraded(
                    $this->getName(),
                    "Disk usage is high: {$diskUsage}%",
                    $checks,
                    $duration
                );
            }

            return HealthCheckResult::healthy(
                $this->getName(),
                'Filesystem is accessible and has sufficient space',
                $checks,
                $duration
            );
        } catch (\Exception $e) {
            $this->logger->error('Filesystem health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Filesystem check failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    public function getTimeout(): int
    {
        return 3; // 3 seconds timeout
    }

    public function isCritical(): bool
    {
        return true; // Filesystem is critical
    }

    public function getCategory(): string
    {
        return 'filesystem';
    }

    public function getTags(): array
    {
        return ['filesystem', 'storage', 'infrastructure'];
    }

    /**
     * Check log directory accessibility.
     *
     * @return array<string, mixed>
     */
    private function checkLogDirectory(): array
    {
        $logDir = $this->paths->logs();

        if (!is_dir($logDir)) {
            return ['success' => false, 'message' => 'Log directory does not exist'];
        }

        if (!is_writable($logDir)) {
            return ['success' => false, 'message' => 'Log directory is not writable'];
        }

        return [
            'success'     => true,
            'message'     => 'Log directory is accessible',
            'path'        => $this->getCanonicalPath($logDir),
            'permissions' => substr(sprintf('%o', fileperms($logDir)), -4),
        ];
    }

    /**
     * Check temp directory accessibility.
     *
     * @return array<string, mixed>
     */
    private function checkTempDirectory(): array
    {
        $tempDir = sys_get_temp_dir();

        if (!is_dir($tempDir)) {
            return ['success' => false, 'message' => 'Temp directory does not exist'];
        }

        if (!is_writable($tempDir)) {
            return ['success' => false, 'message' => 'Temp directory is not writable'];
        }

        return [
            'success' => true,
            'message' => 'Temp directory is accessible',
            'path'    => $tempDir,
        ];
    }

    /**
     * Check cache directory accessibility.
     *
     * @return array<string, mixed>
     */
    private function checkCacheDirectory(): array
    {
        $cacheDir = $this->paths->cache();

        // Create cache directory if it doesn't exist
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0o755, true)) {
                return ['success' => false, 'message' => 'Cannot create cache directory'];
            }
        }

        if (!is_writable($cacheDir)) {
            return ['success' => false, 'message' => 'Cache directory is not writable'];
        }

        return [
            'success'     => true,
            'message'     => 'Cache directory is accessible',
            'path'        => $this->getCanonicalPath($cacheDir),
            'permissions' => substr(sprintf('%o', fileperms($cacheDir)), -4),
        ];
    }

    /**
     * Check disk space.
     *
     * @return array<string, mixed>
     */
    private function checkDiskSpace(): array
    {
        $basePath = $this->paths->base();
        $freeBytes = disk_free_space($basePath);
        $totalBytes = disk_total_space($basePath);

        if ($freeBytes === false || $totalBytes === false) {
            return ['success' => false, 'message' => 'Cannot determine disk space'];
        }

        $usedBytes = $totalBytes - $freeBytes;
        $usagePercentage = round(($usedBytes / $totalBytes) * 100, 2);

        $success = $usagePercentage < self::CRITICAL_DISK_USAGE_THRESHOLD;

        return [
            'success'          => $success,
            'message'          => $success ? 'Sufficient disk space available' : 'Disk space critically low',
            'free_bytes'       => $freeBytes,
            'total_bytes'      => $totalBytes,
            'used_bytes'       => $usedBytes,
            'usage_percentage' => $usagePercentage,
            'free_gb'          => round($freeBytes / 1024 / 1024 / 1024, 2),
            'total_gb'         => round($totalBytes / 1024 / 1024 / 1024, 2),
        ];
    }

    /**
     * Check write permissions by creating a test file.
     *
     * @return array<string, mixed>
     */
    private function checkWritePermissions(): array
    {
        $testFile = $this->paths->logs() . '/health_check_test.tmp';

        try {
            $handle = fopen($testFile, 'w');
            if ($handle === false) {
                return ['success' => false, 'message' => 'Cannot create test file'];
            }

            fwrite($handle, 'health check test');
            fclose($handle);

            if (!unlink($testFile)) {
                return ['success' => false, 'message' => 'Cannot delete test file'];
            }

            return [
                'success'   => true,
                'message'   => 'Write permissions are working',
                'test_file' => $testFile,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Write permission test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get canonical path safely using PathsFactory.
     */
    private function getCanonicalPath(string $path): string
    {
        // Use PathsFactory for secure path handling
        try {
            $paths = PathsFactory::create();
            // If path exists, return it normalized
            if (file_exists($path)) {
                return $paths->getPath(dirname($path), basename($path));
            }
        } catch (\Exception $e) {
            // Fallback to original path if PathsFactory fails
        }

        return $path;
    }
}
