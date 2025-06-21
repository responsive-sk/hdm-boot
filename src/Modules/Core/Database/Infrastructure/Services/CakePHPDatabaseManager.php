<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Database\Infrastructure\Services;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Query\DeleteQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\UpdateQuery;
use MvaBootstrap\Modules\Core\Database\Domain\Contracts\DatabaseManagerInterface;
use MvaBootstrap\Modules\Core\Database\Domain\Contracts\QueryBuilderInterface;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

/**
 * CakePHP Database Manager.
 *
 * Provides database access using CakePHP Database library with query builder support.
 */
final class CakePHPDatabaseManager implements DatabaseManagerInterface, QueryBuilderInterface
{
    private ?Connection $connection = null;

    private readonly string $databasePath;

    private readonly string $databaseFile;

    public function __construct(
        private readonly Paths $paths
    ) {
        $this->databasePath = $this->paths->base() . '/var/storage';
        $this->databaseFile = $this->databasePath . '/app.db';
    }

    /**
     * Get CakePHP Database connection.
     */
    public function getConnection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * Execute raw SQL query.
     */
    public function execute(string $sql, array $params = []): mixed
    {
        try {
            $statement = $this->getConnection()->execute($sql, $params);
            return $statement;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to execute SQL: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Begin database transaction.
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->begin();
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
        $this->getConnection()->rollback();
    }

    /**
     * Execute callback within a transaction.
     */
    public function transactional(callable $callback): mixed
    {
        // @phpstan-ignore-next-line
        return $this->getConnection()->transactional($callback);
    }

    /**
     * Check if table exists in database.
     */
    public function tableExists(string $tableName): bool
    {
        try {
            $query = $this->getConnection()
                ->selectQuery()
                ->select(['name'])
                ->from('sqlite_master')
                ->where(['type' => 'table', 'name' => $tableName]);

            $result = $query->execute()->fetch();
            return $result !== false;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Initialize database schema.
     */
    public function initializeDatabase(): void
    {
        // Ensure storage directory exists
        if (!is_dir($this->databasePath)) {
            mkdir($this->databasePath, 0o755, true);
        }

        try {
            $connection = $this->getConnection();

            // Create metadata table
            $connection->execute("
                CREATE TABLE IF NOT EXISTS _database_metadata (
                    key TEXT PRIMARY KEY,
                    value TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )
            ");

            // Insert application metadata
            $connection->execute("
                INSERT OR REPLACE INTO _database_metadata (key, value, created_at, updated_at)
                VALUES ('application', 'MVA Bootstrap', datetime('now', 'localtime'), datetime('now', 'localtime'))
            ");

            // Create users table (example)
            $connection->execute("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    name TEXT NOT NULL,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");

            // Create sessions table
            $connection->execute("
                CREATE TABLE IF NOT EXISTS sessions (
                    session_id TEXT PRIMARY KEY,
                    user_id INTEGER,
                    data TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    last_activity TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to initialize database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get database manager type.
     */
    public function getManagerType(): string
    {
        return 'cakephp';
    }

    /**
     * Get database connection info.
     */
    public function getConnectionInfo(): array
    {
        return [
            'type'          => $this->getManagerType(),
            'driver'        => 'sqlite',
            'database_file' => $this->databaseFile,
            'database_path' => $this->databasePath,
            'file_exists'   => file_exists($this->databaseFile),
            'file_size'     => file_exists($this->databaseFile) ? filesize($this->databaseFile) : 0,
            'writable'      => is_writable($this->databasePath),
        ];
    }

    /**
     * Check database connection health.
     */
    public function isConnected(): bool
    {
        try {
            $this->getConnection()->execute('SELECT 1');
            return true;
        } catch (\Exception) {
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

    // === QUERY BUILDER INTERFACE ===

    /**
     * Create SELECT query builder.
     *
     * @phpstan-ignore-next-line
     */
    public function selectQuery(): SelectQuery
    {
        return $this->getConnection()->selectQuery();
    }

    /**
     * Create INSERT query builder.
     */
    public function insertQuery(): InsertQuery
    {
        return $this->getConnection()->insertQuery();
    }

    /**
     * Create UPDATE query builder.
     */
    public function updateQuery(): UpdateQuery
    {
        return $this->getConnection()->updateQuery();
    }

    /**
     * Create DELETE query builder.
     */
    public function deleteQuery(): DeleteQuery
    {
        return $this->getConnection()->deleteQuery();
    }

    // === PRIVATE METHODS ===

    /**
     * Create CakePHP Database connection.
     */
    private function createConnection(): Connection
    {
        try {
            // Ensure storage directory exists
            if (!is_dir($this->databasePath)) {
                mkdir($this->databasePath, 0o755, true);
            }

            $config = [
                'driver' => Sqlite::class,
                'database' => $this->databaseFile,
                'encoding' => 'utf8',
                'timezone' => 'UTC',
                'flags' => [],
                'cacheMetadata' => true,
                'log' => false,
            ];

            $connection = new Connection($config);

            // Test connection
            $connection->execute('SELECT 1');

            return $connection;
        } catch (\Exception $e) {
            throw new RuntimeException(
                "Failed to connect to database {$this->databaseFile}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get database health status.
     *
     * @return array<string, mixed>
     */
    public function getHealthStatus(): array
    {
        try {
            $health = [
                'status'                     => 'OK',
                'manager_type'               => $this->getManagerType(),
                'database_file'              => $this->databaseFile,
                'storage_directory_exists'   => is_dir($this->databasePath),
                'storage_directory_writable' => is_writable($this->databasePath),
                'database_file_exists'       => file_exists($this->databaseFile),
                'connection_working'         => $this->isConnected(),
                'last_check'                 => date('Y-m-d H:i:s'),
            ];

            // Get database version
            if ($this->isConnected()) {
                $result = $this->getConnection()->execute('SELECT sqlite_version()')->fetch();
                $health['database_version'] = is_array($result) && isset($result[0]) ? $result[0] : 'unknown';
            }

            return $health;
        } catch (\Exception $e) {
            return [
                'status'        => 'ERROR',
                'error'         => $e->getMessage(),
                'manager_type'  => $this->getManagerType(),
                'last_check'    => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Get table statistics.
     *
     * @return array<string, mixed>
     */
    public function getTableStatistics(): array
    {
        /** @var array<string, mixed> $tables */
        $tables = [];

        try {
            $query = $this->selectQuery()
                ->select(['name'])
                ->from('sqlite_master')
                ->where(['type' => 'table'])
                ->andWhere(['name NOT LIKE' => 'sqlite_%']);

            foreach ($query->execute() as $row) {
                // @phpstan-ignore-next-line function.alreadyNarrowedType
                if (is_array($row) && isset($row['name']) && is_string($row['name'])) {
                    $tableName = $row['name'];
                    $countQuery = $this->selectQuery()
                        ->select(['COUNT(*) as count'])
                        ->from($tableName);

                    $countResult = $countQuery->execute()->fetch();
                    $count = is_array($countResult) && isset($countResult['count']) ? $countResult['count'] : 0;
                    $tables[$tableName] = is_numeric($count) ? (int) $count : 0;
                }
            }
        } catch (\Exception) {
            // Return empty array on error
        }

        return $tables;
    }
}
