<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Drivers;

use HdmBoot\Modules\Core\Storage\Contracts\StorageDriverInterface;
use HdmBoot\SharedKernel\Services\PathsFactory;
use SplFileInfo;
use PDO;

/**
 * SQLite Driver.
 *
 * Handles SQLite database storage for relational data.
 * Part of hybrid storage approach - files for content, database for users/sessions.
 */
class SqliteDriver implements StorageDriverInterface
{
    protected PDO $pdo;
    protected string $tableName;

    /**
     * Cache of loaded records.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $cache = [];

    public function __construct(PDO $pdo, string $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->ensureTableExists();
    }

    public function getExtension(): string
    {
        return 'db'; // Not used for database storage
    }

    public function shouldRestoreCache(string $directory): bool
    {
        // For database, we'll use simple cache invalidation
        return !isset($this->cache[$directory]);
    }

    public function save(array $data, string $filePath): bool
    {
        try {
            // Extract ID from data or file path
            $id = $data['id'] ?? $this->extractIdFromPath($filePath);

            // Remove internal fields
            $cleanData = $data;
            unset($cleanData['_file_path'], $cleanData['_file_name'], $cleanData['_modified_at']);

            if ($this->recordExists($id)) {
                return $this->updateRecord($id, $cleanData);
            } else {
                return $this->insertRecord($cleanData);
            }
        } catch (\Exception) {
            return false;
        }
    }

    public function delete(string $filePath): bool
    {
        try {
            $id = $this->extractIdFromPath($filePath);

            $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                $this->clearCache();
            }

            return $result;
        } catch (\Exception) {
            return false;
        }
    }

    public function loadAll(string $directory): array
    {
        // Check cache first
        if (!$this->shouldRestoreCache($directory)) {
            return $this->cache[$directory] ?? [];
        }

        try {
            $stmt = $this->pdo->query("SELECT * FROM {$this->tableName}");
            if ($stmt === false) {
                return [];
            }

            $records = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (is_array($row)) {
                    // Add metadata with safe casting
                    $id = $row['id'] ?? '';
                    $idString = is_string($id) ? $id : (is_numeric($id) ? (string) $id : '');

                    $row['_file_path'] = $this->buildSecureFilePath($directory, $idString);
                    $row['_file_name'] = $idString;
                    $row['_modified_at'] = $row['updated_at'] ?? date('Y-m-d H:i:s');

                    /** @var array<string, mixed> $typedRow */
                    $typedRow = $row;
                    $records[] = $typedRow;
                }
            }

            // Cache results with proper typing
            /** @var array<int, array<string, mixed>> $typedRecords */
            $typedRecords = $records;
            $this->cache[$directory] = $typedRecords;

            return $typedRecords;
        } catch (\Exception) {
            return [];
        }
    }

    public function parseFile(SplFileInfo $file): array
    {
        // Not used for database storage
        return [];
    }

    public function dumpContent(array $data): string
    {
        // Not used for database storage - data is stored directly in database
        $result = json_encode($data, JSON_PRETTY_PRINT);
        return $result !== false ? $result : '{}';
    }

    public function getContentColumn(): ?string
    {
        return null; // Database storage doesn't use content column
    }

    /**
     * Check if record exists.
     */
    protected function recordExists(mixed $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        return is_numeric($count) && (int) $count > 0;
    }

    /**
     * Insert new record.
     *
     * @param array<string, mixed> $data
     */
    protected function insertRecord(array $data): bool
    {
        // Add timestamps
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(array_values($data));

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Update existing record.
     *
     * @param array<string, mixed> $data
     */
    protected function updateRecord(mixed $id, array $data): bool
    {
        // Add updated timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');

        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            if ($column !== 'id') { // Don't update ID
                $setParts[] = "{$column} = ?";
                $values[] = $value;
            }
        }

        $values[] = $id; // Add ID for WHERE clause

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($values);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * Extract ID from file path.
     */
    protected function extractIdFromPath(string $filePath): string
    {
        $basename = basename($filePath, '.db');
        return $basename;
    }

    /**
     * Ensure table exists with basic structure.
     */
    protected function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )";

        $this->pdo->exec($sql);
    }

    /**
     * Clear cache.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Add column to table if it doesn't exist.
     */
    public function addColumn(string $column, string $type = 'TEXT'): void
    {
        try {
            $sql = "ALTER TABLE {$this->tableName} ADD COLUMN {$column} {$type}";
            $this->pdo->exec($sql);
        } catch (\Exception) {
            // Column probably already exists, ignore
        }
    }

    /**
     * Get table schema.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTableSchema(): array
    {
        $stmt = $this->pdo->query("PRAGMA table_info({$this->tableName})");
        if ($stmt === false) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $columns */
        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row)) {
                // Ensure proper typing for the array
                /** @var array<string, mixed> $typedRow */
                $typedRow = $row;
                $columns[] = $typedRow;
            }
        }

        return $columns;
    }

    /**
     * Build secure file path for database records.
     *
     * Prevents path traversal attacks by validating ID.
     */
    private function buildSecureFilePath(string $directory, string $id): string
    {
        // Validate ID for security
        if (str_contains($id, '..') || str_contains($id, '/') || str_contains($id, '\\')) {
            throw new \InvalidArgumentException("Invalid record ID: {$id}");
        }

        if (str_contains($id, '~')) {
            throw new \InvalidArgumentException("Home directory access not allowed: {$id}");
        }

        // Build secure path using PathsFactory for cross-platform compatibility
        $paths = PathsFactory::create();
        return $paths->getPath($directory, $id . '.db');
    }
}
