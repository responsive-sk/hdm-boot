<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Storage\Contracts;

use SplFileInfo;

/**
 * Storage Driver Interface.
 *
 * Contract for file-based storage drivers (Markdown, JSON, YAML).
 * Inspired by Laravel Orbit package.
 */
interface StorageDriverInterface
{
    /**
     * Get file extension for this driver.
     */
    public function getExtension(): string;

    /**
     * Check if cache should be restored for directory.
     */
    public function shouldRestoreCache(string $directory): bool;

    /**
     * Save model data to file.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data, string $filePath): bool;

    /**
     * Delete file.
     */
    public function delete(string $filePath): bool;

    /**
     * Load all records from directory.
     *
     * @return array<int, array<string, mixed>>
     */
    public function loadAll(string $directory): array;

    /**
     * Parse single file content.
     *
     * @return array<string, mixed>
     */
    public function parseFile(SplFileInfo $file): array;

    /**
     * Dump model data to file content.
     *
     * @param array<string, mixed> $data
     */
    public function dumpContent(array $data): string;

    /**
     * Get content column name (for Markdown driver).
     */
    public function getContentColumn(): ?string;
}
