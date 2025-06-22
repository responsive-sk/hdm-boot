<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Drivers;

use HdmBoot\Modules\Core\Storage\Contracts\StorageDriverInterface;
use SplFileInfo;
use DirectoryIterator;

/**
 * Abstract File Driver.
 *
 * Base implementation for file-based storage drivers.
 * Provides common functionality for file operations.
 */
abstract class AbstractFileDriver implements StorageDriverInterface
{
    /**
     * Cache of loaded files.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $cache = [];

    /**
     * Cache timestamps for directories.
     *
     * @var array<string, int>
     */
    protected array $cacheTimestamps = [];

    public function shouldRestoreCache(string $directory): bool
    {
        if (!isset($this->cacheTimestamps[$directory])) {
            return true;
        }

        $lastModified = $this->getDirectoryLastModified($directory);
        return $lastModified > $this->cacheTimestamps[$directory];
    }

    public function save(array $data, string $filePath): bool
    {
        try {
            // Ensure directory exists
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generate file content
            $content = $this->dumpContent($data);

            // Write to file
            $result = file_put_contents($filePath, $content) !== false;

            if ($result) {
                // Clear cache for this directory
                $this->clearCacheForDirectory(dirname($filePath));
            }

            return $result;
        } catch (\Exception) {
            return false;
        }
    }

    public function delete(string $filePath): bool
    {
        try {
            if (file_exists($filePath)) {
                $result = unlink($filePath);

                if ($result) {
                    // Clear cache for this directory
                    $this->clearCacheForDirectory(dirname($filePath));
                }

                return $result;
            }
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function loadAll(string $directory): array
    {
        // Check cache first
        if (isset($this->cache[$directory]) && !$this->shouldRestoreCache($directory)) {
            return $this->cache[$directory];
        }

        $records = [];

        if (!is_dir($directory)) {
            return $records;
        }

        $iterator = new DirectoryIterator($directory);
        $extension = $this->getExtension();

        foreach ($iterator as $file) {
            // @phpstan-ignore-next-line instanceof.alwaysTrue
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isFile() && $file->getExtension() === $extension) {
                try {
                    $data = $this->parseFile($file);
                    if (!empty($data)) {
                        // Add file metadata
                        $data['_file_path'] = $file->getPathname();
                        $data['_file_name'] = $file->getBasename('.' . $extension);
                        $data['_modified_at'] = date('Y-m-d H:i:s', $file->getMTime());

                        $records[] = $data;
                    }
                } catch (\Exception) {
                    // Skip invalid files
                    continue;
                }
            }
        }

        // Cache results
        $this->cache[$directory] = $records;
        $this->cacheTimestamps[$directory] = time();

        return $records;
    }

    public function getContentColumn(): ?string
    {
        return null; // Override in drivers that support content column
    }

    /**
     * Get last modified timestamp for directory.
     */
    protected function getDirectoryLastModified(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $lastModified = 0;
        $iterator = new DirectoryIterator($directory);
        $extension = $this->getExtension();

        foreach ($iterator as $file) {
            // @phpstan-ignore-next-line instanceof.alwaysTrue
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isFile() && $file->getExtension() === $extension) {
                $lastModified = max($lastModified, $file->getMTime());
            }
        }

        return $lastModified;
    }

    /**
     * Clear cache for specific directory.
     */
    protected function clearCacheForDirectory(string $directory): void
    {
        unset($this->cache[$directory], $this->cacheTimestamps[$directory]);
    }

    /**
     * Clear all cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->cacheTimestamps = [];
    }
}
