<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Services;

use HdmBoot\Modules\Core\Storage\Contracts\StorageDriverInterface;
use HdmBoot\Modules\Core\Storage\Drivers\MarkdownDriver;
use HdmBoot\Modules\Core\Storage\Drivers\JsonDriver;
use HdmBoot\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * File Storage Service.
 *
 * Manages file-based storage drivers and content directories.
 */
class FileStorageService
{
    /**
     * Registered drivers.
     *
     * @var array<string, StorageDriverInterface>
     */
    protected array $drivers = [];

    /**
     * Base content directory.
     */
    protected string $contentDirectory;

    /**
     * Paths service for secure file operations.
     */
    protected ?Paths $paths;

    public function __construct(?string $contentDirectory = null, ?Paths $paths = null)
    {
        $this->paths = $paths;
        $this->contentDirectory = $contentDirectory ?? $this->getDefaultContentDirectory();
        $this->registerDefaultDrivers();
    }

    /**
     * Register a storage driver.
     */
    public function registerDriver(string $name, StorageDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    /**
     * Get a storage driver.
     */
    public function getDriver(string $name): StorageDriverInterface
    {
        if (!isset($this->drivers[$name])) {
            throw new \InvalidArgumentException("Storage driver '{$name}' not found");
        }

        return $this->drivers[$name];
    }

    /**
     * Get storage directory for a model.
     */
    public function getStorageDirectory(string $modelName): string
    {
        // Use Paths service for secure path resolution
        if ($this->paths) {
            // Use content directory from Paths config
            $contentDir = $this->paths->content();
            $directory = $this->paths->getPath($contentDir, $modelName);
        } else {
            // Fallback to secure path helper
            $directory = $this->securePathJoin($this->contentDirectory, $modelName);
        }

        // Ensure directory exists
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory;
    }

    /**
     * Get base content directory.
     */
    public function getContentDirectory(): string
    {
        return $this->contentDirectory;
    }

    /**
     * Set content directory.
     */
    public function setContentDirectory(string $directory): void
    {
        $this->contentDirectory = $directory;
    }

    /**
     * Clear all driver caches.
     */
    public function clearCache(): void
    {
        foreach ($this->drivers as $driver) {
            if (method_exists($driver, 'clearCache')) {
                $driver->clearCache();
            }
        }
    }

    /**
     * Get default content directory.
     */
    protected function getDefaultContentDirectory(): string
    {
        // Use Paths service if available
        if ($this->paths) {
            return $this->paths->content();
        }

        // Fallback: Use PathsFactory for secure path resolution
        try {
            $paths = PathsFactory::create();
            return $paths->content();
        } catch (\Exception $e) {
            // Last resort fallback
            throw new \RuntimeException('Unable to determine content directory: ' . $e->getMessage());
        }
    }

    /**
     * Register default drivers.
     */
    protected function registerDefaultDrivers(): void
    {
        $this->registerDriver('markdown', new MarkdownDriver());
        $this->registerDriver('json', new JsonDriver());
    }

    /**
     * Secure path joining helper.
     *
     * Prevents path traversal attacks when Paths service is not available.
     */
    private function securePathJoin(string $basePath, string $relativePath): string
    {
        // Validate relative path for security
        if (str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException("Path traversal detected in: {$relativePath}");
        }

        if (str_contains($relativePath, '~')) {
            throw new \InvalidArgumentException("Home directory access not allowed: {$relativePath}");
        }

        // Clean and normalize the path
        $relativePath = ltrim($relativePath, '/\\');

        // Use PathsFactory for secure cross-platform path joining
        $paths = PathsFactory::create();
        return $paths->getPath($basePath, $relativePath);
    }
}
