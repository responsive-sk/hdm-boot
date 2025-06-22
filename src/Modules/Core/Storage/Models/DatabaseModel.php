<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Models;

use HdmBoot\Modules\Core\Storage\Contracts\StorageDriverInterface;
use HdmBoot\Modules\Core\Storage\Drivers\SqliteDriver;
use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Services\DatabaseManager;
use PDO;

/**
 * Database Model.
 *
 * Base model for SQLite database storage.
 * Part of hybrid storage approach - database for relational data.
 */
abstract class DatabaseModel
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
    protected static string $driver = 'sqlite';

    /**
     * Primary key field name.
     */
    protected string $primaryKey = 'id';

    /**
     * Indicates if primary key is auto-incrementing.
     */
    protected bool $incrementing = true;

    /**
     * Table name for database storage.
     */
    protected static string $table = '';

    /**
     * Database name for multi-database support.
     */
    protected static string $database = 'app';

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
     * Get the table name.
     */
    public static function getTableName(): string
    {
        if (!empty(static::$table)) {
            return static::$table;
        }

        $className = basename(str_replace('\\', '/', static::class));
        return static::pluralize(static::snakeCase($className));
    }

    /**
     * Get the storage driver.
     */
    public static function getDriver(): StorageDriverInterface
    {
        $pdo = static::getPdo();
        $tableName = static::getTableName();

        $driver = new SqliteDriver($pdo, $tableName);

        // Ensure table has required columns based on schema
        static::ensureTableSchema($driver);

        return $driver;
    }

    /**
     * Get PDO instance.
     */
    protected static function getPdo(): PDO
    {
        // Initialize DatabaseManager if not already done
        if (!DatabaseManager::getDatabases()) {
            $contentDir = static::getStorageService()->getContentDirectory();
            DatabaseManager::initialize($contentDir);
        }

        return DatabaseManager::getConnection(static::$database);
    }

    /**
     * Get database name.
     */
    public static function getDatabaseName(): string
    {
        return static::$database;
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
        $driver = static::getDriver();
        $key = $this->getKey();
        $keyString = is_string($key) || is_numeric($key) ? (string) $key : 'new';
        $filePath = 'database/' . static::getTableName() . '/' . $keyString . '.db';

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

        $driver = static::getDriver();
        $keyString = is_string($key) || is_numeric($key) ? (string) $key : '';
        $filePath = 'database/' . static::getTableName() . '/' . $keyString . '.db';

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
            if ($model->getKey() == $key) { // Use == for type flexibility
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
        $driver = static::getDriver();
        $directory = 'database/' . static::getTableName();

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
     * Ensure table has required columns.
     */
    protected static function ensureTableSchema(SqliteDriver $driver): void
    {
        $schema = static::schema();

        foreach ($schema as $column => $definition) {
            if ($column === 'id') {
                continue; // ID is handled by driver
            }

            // Simple type mapping
            $type = 'TEXT';
            $definitionStr = is_string($definition) ? $definition : '';
            if (str_contains($definitionStr, 'integer')) {
                $type = 'INTEGER';
            } elseif (str_contains($definitionStr, 'boolean')) {
                $type = 'INTEGER'; // SQLite uses INTEGER for boolean
            } elseif (str_contains($definitionStr, 'datetime')) {
                $type = 'TEXT'; // SQLite uses TEXT for datetime
            }

            $driver->addColumn($column, $type);
        }
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
}
