<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\System;

use ResponsiveSk\Slim4Paths\Paths;

/**
 * Centralized Permission Manager for HDM Boot.
 *
 * Handles file/directory permissions across the entire system.
 * Uses strict permissions by default (755/644) with environment-specific adjustments.
 */
final class PermissionManager
{
    // Default strict permissions (production-safe)
    public const DIR_PERMISSION_STRICT = 0o755;   // rwxr-xr-x
    public const FILE_PERMISSION_STRICT = 0o644;  // rw-r--r--

    // Relaxed permissions (shared hosting)
    public const DIR_PERMISSION_RELAXED = 0o777;  // rwxrwxrwx
    public const FILE_PERMISSION_RELAXED = 0o666; // rw-rw-rw-

    // Log file permissions (need write access)
    public const LOG_FILE_PERMISSION = 0o666;     // rw-rw-rw-

    // Cache permissions
    public const CACHE_DIR_PERMISSION = 0o755;    // rwxr-xr-x
    public const CACHE_FILE_PERMISSION = 0o644;   // rw-r--r--

    private readonly bool $useStrictPermissions;

    public function __construct(
        private readonly Paths $paths,
        ?bool $useStrictPermissions = null
    ) {
        // Default to strict permissions unless explicitly disabled
        $this->useStrictPermissions = $useStrictPermissions ??
            ($_ENV['PERMISSIONS_STRICT'] ?? 'true') === 'true';
    }

    /**
     * Create directory with appropriate permissions.
     */
    public function createDirectory(string $path, ?int $permissions = null): bool
    {
        $securePath = $this->paths->path($path);

        if (is_dir($securePath)) {
            return true;
        }

        $permissions ??= $this->getDefaultDirectoryPermissions();

        if (!mkdir($securePath, $permissions, true)) {
            return false;
        }

        // Ensure permissions are set correctly
        return $this->setDirectoryPermissions($securePath, $permissions);
    }

    /**
     * Create file with appropriate permissions.
     */
    public function createFile(string $path, string $content = '', ?int $permissions = null): bool
    {
        $securePath = $this->paths->path($path);

        // Ensure directory exists
        $directory = dirname($securePath);
        if (!is_dir($directory)) {
            $this->createDirectory(dirname($path));
        }

        if (file_put_contents($securePath, $content) === false) {
            return false;
        }

        $permissions ??= $this->getDefaultFilePermissions();

        return $this->setFilePermissions($securePath, $permissions);
    }

    /**
     * Set directory permissions.
     */
    public function setDirectoryPermissions(string $path, ?int $permissions = null): bool
    {
        $securePath = $this->paths->path($path);

        if (!is_dir($securePath)) {
            return false;
        }

        $permissions ??= $this->getDefaultDirectoryPermissions();

        return chmod($securePath, $permissions);
    }

    /**
     * Set file permissions.
     */
    public function setFilePermissions(string $path, ?int $permissions = null): bool
    {
        $securePath = $this->paths->path($path);

        if (!is_file($securePath)) {
            return false;
        }

        $permissions ??= $this->getDefaultFilePermissions();

        return chmod($securePath, $permissions);
    }

    /**
     * Fix permissions for entire directory tree.
     *
     * @return array<string, mixed>
     */
    public function fixDirectoryTreePermissions(string $path): array
    {
        $securePath = $this->paths->path($path);
        $results = [
            'directories_fixed' => 0,
            'files_fixed'       => 0,
            'errors'            => [],
        ];

        if (!is_dir($securePath)) {
            $results['errors'][] = "Directory not found: {$path}";

            return $results;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($securePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            try {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }

                if ($item->isDir()) {
                    $pathname = $item->getPathname();
                    if ($this->setDirectoryPermissions($pathname)) {
                        ++$results['directories_fixed'];
                    }
                } elseif ($item->isFile()) {
                    $pathname = $item->getPathname();
                    if ($this->setFilePermissions($pathname)) {
                        ++$results['files_fixed'];
                    }
                }
            } catch (\Exception $e) {
                $pathnameStr = $item->getPathname();
                $results['errors'][] = "Failed to fix permissions for {$pathnameStr}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Setup system directories with correct permissions.
     *
     * @return array<string, mixed>
     */
    public function setupSystemDirectories(): array
    {
        $directories = [
            'storage'       => self::DIR_PERMISSION_STRICT,
            'var'           => self::DIR_PERMISSION_STRICT,
            $this->paths->path('logs')      => self::DIR_PERMISSION_STRICT,
            $this->paths->path('sessions')  => self::DIR_PERMISSION_STRICT,
            $this->paths->path('cache')     => self::CACHE_DIR_PERMISSION,
        ];

        $results = [
            'created' => [],
            'errors'  => [],
        ];

        foreach ($directories as $dir => $permission) {
            try {
                if ($this->createDirectory($dir, $permission)) {
                    $results['created'][] = $dir;
                } else {
                    $results['errors'][] = "Failed to create directory: {$dir}";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Error creating {$dir}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Setup log files with correct permissions.
     *
     * @return array<string, mixed>
     */
    public function setupLogFiles(): array
    {
        $logFiles = [
            $this->paths->path('logs/app.log'),
            $this->paths->path('logs/security.log'),
            $this->paths->path('logs/error.log'),
            $this->paths->path('logs/debug.log'),
        ];

        $results = [
            'created' => [],
            'errors'  => [],
        ];

        foreach ($logFiles as $logFile) {
            try {
                if ($this->createFile($logFile, '', self::LOG_FILE_PERMISSION)) {
                    $results['created'][] = $logFile;
                } else {
                    $results['errors'][] = "Failed to create log file: {$logFile}";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Error creating {$logFile}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get default directory permissions based on environment.
     */
    public function getDefaultDirectoryPermissions(): int
    {
        return $this->useStrictPermissions ? self::DIR_PERMISSION_STRICT : self::DIR_PERMISSION_RELAXED;
    }

    /**
     * Get default file permissions based on environment.
     */
    public function getDefaultFilePermissions(): int
    {
        return $this->useStrictPermissions ? self::FILE_PERMISSION_STRICT : self::FILE_PERMISSION_RELAXED;
    }

    /**
     * Check if current environment supports strict permissions.
     */
    public function canUseStrictPermissions(): bool
    {
        // Test by creating a temporary file with strict permissions
        $testFile = $this->paths->path('var') . '/permission_test_' . uniqid();

        try {
            if (file_put_contents($testFile, 'test') === false) {
                return false;
            }

            $result = chmod($testFile, self::FILE_PERMISSION_STRICT);
            unlink($testFile);

            return $result;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get permission info for debugging.
     *
     * @return array<string, mixed>
     */
    public function getPermissionInfo(): array
    {
        return [
            'strict_permissions_enabled'   => $this->useStrictPermissions,
            'can_use_strict_permissions'   => $this->canUseStrictPermissions(),
            'default_directory_permission' => sprintf('0%o', $this->getDefaultDirectoryPermissions()),
            'default_file_permission'      => sprintf('0%o', $this->getDefaultFilePermissions()),
            'environment_override'         => $_ENV['PERMISSIONS_STRICT'] ?? 'not_set',
        ];
    }
}
