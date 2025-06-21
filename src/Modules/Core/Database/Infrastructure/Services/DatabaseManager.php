<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Database\Infrastructure\Services;

use MvaBootstrap\Modules\Core\Database\Domain\Contracts\DatabaseManagerInterface;
use PDO;
use PDOException;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

/**
 * Database Manager for MVA Bootstrap Application.
 *
 * Manages SQLite database connection with proper path handling,
 * initialization, and performance optimizations.
 * Adapted from the parent project.
 */
final class DatabaseManager implements DatabaseManagerInterface
{
    private ?PDO $connection = null;

    private readonly string $databasePath;

    private readonly string $databaseFile;

    public function __construct(
        private readonly Paths $paths,
        private readonly string $filename = 'app.db'
    ) {
        $this->databasePath = $this->paths->getPath($this->paths->base(), 'var/storage');
        $this->databaseFile = $this->paths->getPath($this->databasePath, $this->filename);
        $this->ensureDatabaseDirectory();
    }

    /**
     * Get database connection.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
            $this->initializeDatabase();
        }

        assert($this->connection !== null);

        return $this->connection;
    }

    /**
     * Test database connection.
     */
    /** @return array<string, mixed> */
    public function testConnection(): array
    {
        try {
            $this->getConnection()->query('SELECT 1');

            return [
                'status'        => 'OK',
                'database_file' => $this->databaseFile,
                'file_exists'   => file_exists($this->databaseFile),
                'file_size'     => file_exists($this->databaseFile) ? filesize($this->databaseFile) : 0,
                'writable'      => is_writable($this->databasePath),
            ];
        } catch (PDOException $e) {
            return [
                'status'        => 'ERROR',
                'error'         => $e->getMessage(),
                'database_file' => $this->databaseFile,
                'file_exists'   => file_exists($this->databaseFile),
                'writable'      => is_writable($this->databasePath),
            ];
        }
    }

    /**
     * Get database statistics.
     */
    /** @return array<string, mixed> */
    /** @return array<string, mixed> */
    public function getStatistics(): array
    {
        $connection = $this->getConnection();

        // Get table statistics
        $tables = [];
        $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        if ($stmt === false) {
            throw new RuntimeException('Failed to query database tables');
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row) || !isset($row['name']) || !is_string($row['name'])) {
                continue;
            }
            $tableName = $row['name'];
            $countStmt = $connection->query("SELECT COUNT(*) as count FROM {$tableName}");
            if ($countStmt === false) {
                continue;
            }
            $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($countRow) && isset($countRow['count'])) {
                $count = $countRow['count'];
                $tables[$tableName] = is_numeric($count) ? (int) $count : 0;
            }
        }

        // Get file size
        $fileSize = file_exists($this->databaseFile) ? filesize($this->databaseFile) : 0;

        return [
            'database_file'     => $this->databaseFile,
            'file_size_bytes'   => $fileSize,
            'file_size_mb'      => round($fileSize / 1024 / 1024, 2),
            'tables'            => $tables,
            'total_records'     => array_sum($tables),
            'connection_status' => 'OK',
        ];
    }

    /**
     * Execute raw SQL query (for migrations, etc.).
     */
    public function executeRawSql(string $sql): void
    {
        try {
            $this->getConnection()->exec($sql);
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to execute SQL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get database file path.
     */
    public function getDatabasePath(): string
    {
        return $this->databaseFile;
    }

    /**
     * Check if database file exists.
     */
    public function databaseExists(): bool
    {
        return file_exists($this->databaseFile);
    }

    /**
     * Create database connection.
     */
    private function createConnection(): PDO
    {
        try {
            $pdo = new PDO("sqlite:{$this->databaseFile}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Enable foreign key constraints
            $pdo->exec('PRAGMA foreign_keys = ON');

            // Performance optimizations
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA cache_size = 10000');
            $pdo->exec('PRAGMA temp_store = MEMORY');

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to connect to database {$this->databaseFile}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Initialize database with basic structure.
     */
    public function initializeDatabase(): void
    {
        // Basic initialization - tables will be created by individual modules
        // This ensures the database file exists and is properly configured

        try {
            assert($this->connection !== null);

            // Create a simple metadata table to track database version
            $this->connection->exec('
                CREATE TABLE IF NOT EXISTS _database_metadata (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL,
                    created_at TEXT NOT NULL ,
                    updated_at TEXT NOT NULL
                )
            ');

            // Insert or update database version
            $this->connection->exec("
                INSERT OR REPLACE INTO _database_metadata (key, value, created_at, updated_at)
                VALUES ('version', '1.0.0', datetime('now', 'localtime'), datetime('now', 'localtime'))
            ");

            // Insert or update application info
            $this->connection->exec("
                INSERT OR REPLACE INTO _database_metadata (key, value, created_at, updated_at)
                VALUES ('application', 'MVA Bootstrap', datetime('now', 'localtime'), datetime('now', 'localtime'))
            ");
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to initialize database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Ensure database directory exists and is writable.
     */
    private function ensureDatabaseDirectory(): void
    {
        if (!is_dir($this->databasePath)) {
            if (!mkdir($this->databasePath, 0o755, true)) {
                throw new RuntimeException("Failed to create database directory: {$this->databasePath}");
            }
        }

        if (!is_writable($this->databasePath)) {
            throw new RuntimeException("Database directory is not writable: {$this->databasePath}");
        }
    }

    // === DatabaseManagerInterface Implementation ===

    /**
     * Execute raw SQL query.
     */
    public function execute(string $sql, array $params = []): mixed
    {
        $connection = $this->getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Begin database transaction.
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Commit database transaction.
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Rollback database transaction.
     */
    public function rollback(): void
    {
        $this->getConnection()->rollBack();
    }

    /**
     * Execute callback within a transaction.
     */
    public function transactional(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback();
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Check if table exists in database.
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $statement = $this->getConnection()->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
            );
            $statement->execute([$tableName]);

            return $statement->fetch() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database manager type/name.
     */
    public function getManagerType(): string
    {
        return 'sqlite';
    }

    /**
     * Get database configuration info.
     */
    public function getConnectionInfo(): array
    {
        return [
            'type'          => $this->getManagerType(),
            'database_file' => $this->databaseFile,
            'database_path' => $this->databasePath,
            'file_exists'   => file_exists($this->databaseFile),
            'file_size'     => file_exists($this->databaseFile) ? filesize($this->databaseFile) : 0,
            'writable'      => is_writable($this->databasePath),
            'connected'     => $this->connection !== null,
        ];
    }

    /**
     * Check database connection health.
     */
    public function isConnected(): bool
    {
        try {
            if ($this->connection === null) {
                return false;
            }
            $this->connection->query('SELECT 1');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Close database connection.
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }
}
