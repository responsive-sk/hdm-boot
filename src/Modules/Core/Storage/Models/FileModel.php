<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

use HdmBoot\Modules\Core\Storage\Contracts\StorageDriverInterface;
use HdmBoot\Modules\Core\Storage\Drivers\MarkdownDriver;
use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\SharedKernel\Services\PathsFactory;

/**
 * File Model.
 *
 * Base model for file-based storage.
 * Inspired by Laravel Orbit package.
 */
abstract class FileModel
{
    /**
     * Model data.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Original data (for change detection).
     *
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Indicates if model exists in storage.
     */
    protected bool $exists = false;

    /**
     * Storage driver name.
     */
    protected static string $driver = 'markdown';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'slug';

    /**
     * Indicates if primary key is auto-incrementing.
     */
    protected bool $incrementing = false;

    /**
     * Storage service instance.
     */
    protected static ?FileStorageService $storageService = null;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }

    /**
     * Define the schema for this model.
     *
     * @return array<string, mixed>
     */
    abstract public static function schema(): array;

    /**
     * Get the storage directory name.
     */
    public static function getStorageName(): string
    {
        $className = basename(str_replace('\\', '/', static::class));
        return static::pluralize(static::snakeCase($className));
    }

    /**
     * Get the storage driver.
     */
    public static function getDriver(): StorageDriverInterface
    {
        return static::getStorageService()->getDriver(static::$driver);
    }

    /**
     * Get storage service instance.
     */
    protected static function getStorageService(): FileStorageService
    {
        if (static::$storageService === null) {
            static::$storageService = new FileStorageService();
        }
        return static::$storageService;
    }

    /**
     * Set storage service instance.
     */
    public static function setStorageService(FileStorageService $service): void
    {
        static::$storageService = $service;
    }

    /**
     * Fill model with attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Set attribute value.
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute value.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get all attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get primary key value.
     */
    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Get primary key name.
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Check if model exists in storage.
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * Save model to storage.
     */
    public function save(): bool
    {
        $key = $this->getKey();
        if (empty($key)) {
            throw new \InvalidArgumentException('Primary key value is required for saving');
        }

        $directory = static::getStorageService()->getStorageDirectory(static::getStorageName());
        $driver = static::getDriver();
        $keyString = is_string($key) ? $key : (is_numeric($key) ? (string) $key : '');
        $filePath = static::buildSecureFilePath($directory, $keyString, $driver->getExtension());

        $result = $driver->save($this->attributes, $filePath);

        if ($result) {
            $this->exists = true;
            $this->syncOriginal();
        }

        return $result;
    }

    /**
     * Delete model from storage.
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return true;
        }

        $key = $this->getKey();
        if (empty($key)) {
            return false;
        }

        $directory = static::getStorageService()->getStorageDirectory(static::getStorageName());
        $driver = static::getDriver();
        $keyString = is_string($key) ? $key : (is_numeric($key) ? (string) $key : '');
        $filePath = static::buildSecureFilePath($directory, $keyString, $driver->getExtension());

        $result = $driver->delete($filePath);

        if ($result) {
            $this->exists = false;
        }

        return $result;
    }

    /**
     * Find model by primary key.
     */
    public static function find(mixed $key): ?static
    {
        $models = static::all();

        foreach ($models as $model) {
            if ($model->getKey() === $key) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Get all models.
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        $directory = static::getStorageService()->getStorageDirectory(static::getStorageName());
        $driver = static::getDriver();

        $records = $driver->loadAll($directory);

        $models = [];
        foreach ($records as $data) {
            // @phpstan-ignore-next-line
            $model = new static($data);
            $model->exists = true;
            $model->syncOriginal();
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Create new model.
     *
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        // @phpstan-ignore-next-line
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Sync original attributes.
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    /**
     * Magic getter.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert string to snake_case.
     */
    protected static function snakeCase(string $value): string
    {
        $result = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);
        return strtolower($result ?? $value);
    }

    /**
     * Simple pluralization.
     */
    protected static function pluralize(string $value): string
    {
        if (str_ends_with($value, 'y')) {
            return substr($value, 0, -1) . 'ies';
        }
        if (str_ends_with($value, 's') || str_ends_with($value, 'x') || str_ends_with($value, 'ch') || str_ends_with($value, 'sh')) {
            return $value . 'es';
        }
        return $value . 's';
    }

    /**
     * Build secure file path.
     *
     * Prevents path traversal attacks by validating components.
     */
    protected static function buildSecureFilePath(string $directory, string $key, string $extension): string
    {
        // Validate key for security
        if (str_contains($key, '..') || str_contains($key, '/') || str_contains($key, '\\')) {
            throw new \InvalidArgumentException("Invalid file key: {$key}");
        }

        // Validate extension
        if (str_contains($extension, '..') || str_contains($extension, '/') || str_contains($extension, '\\')) {
            throw new \InvalidArgumentException("Invalid file extension: {$extension}");
        }

        // Build secure path using PathsFactory for cross-platform compatibility
        $paths = PathsFactory::create();
        return $paths->getPath($directory, $key . '.' . $extension);
    }
}
