<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Services;

use MvaBootstrap\Shared\Contracts\DatabaseManagerInterface;
use MvaBootstrap\Shared\Contracts\QueryBuilderInterface;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Query\SelectQuery;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\UpdateQuery;
use Cake\Database\Query\DeleteQuery;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * CakePHP Database Connection Manager.
 *
 * Provides enterprise-level database access using CakePHP Database ORM.
 * Much better than raw PDO queries!
 *
 * Implements: DatabaseManagerInterface + QueryBuilderInterface
 */
final class DatabaseConnectionManager implements DatabaseManagerInterface, QueryBuilderInterface
{
    private Connection $connection;

    public function __construct(
        Paths $paths,
        string $environment = 'development'
    ) {
        $this->connection = $this->createConnection($paths, $environment);
    }

    /**
     * Get CakePHP Database Connection.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Create SELECT query builder.
     */
    public function selectQuery(): SelectQuery
    {
        return $this->connection->selectQuery();
    }

    /**
     * Create INSERT query builder.
     */
    public function insertQuery(): InsertQuery
    {
        return $this->connection->insertQuery();
    }

    /**
     * Create UPDATE query builder.
     */
    public function updateQuery(): UpdateQuery
    {
        return $this->connection->updateQuery();
    }

    /**
     * Create DELETE query builder.
     */
    public function deleteQuery(): DeleteQuery
    {
        return $this->connection->deleteQuery();
    }

    /**
     * Execute raw SQL query.
     *
     * @param array<string|int, mixed> $params
     */
    public function execute(string $sql, array $params = []): \Cake\Database\StatementInterface
    {
        return $this->connection->execute($sql, $params);
    }

    /**
     * Get database manager type.
     */
    public function getManagerType(): string
    {
        return 'CakePHP Database';
    }

    /**
     * Get database configuration info.
     *
     * @return array<string, mixed>
     */
    public function getConnectionInfo(): array
    {
        return [
            'type' => $this->getManagerType(),
            'driver' => 'SQLite',
            'database' => $this->connection->config()['database'] ?? 'unknown',
            'connected' => $this->isConnected(),
        ];
    }

    /**
     * Check database connection health.
     */
    public function isConnected(): bool
    {
        try {
            $this->connection->execute('SELECT 1');
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
        // CakePHP Database handles this automatically
        // Connection will be closed when object is destroyed
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection->begin();
    }

    /**
     * Commit transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback transaction.
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * Execute callback in transaction.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transactional(callable $callback): mixed
    {
        return $this->connection->transactional($callback);
    }

    /**
     * Get table schema.
     */
    public function getSchemaCollection(): \Cake\Database\Schema\CollectionInterface
    {
        return $this->connection->getSchemaCollection();
    }

    /**
     * Check if table exists.
     */
    public function tableExists(string $tableName): bool
    {
        $schema = $this->getSchemaCollection();
        return in_array($tableName, $schema->listTables(), true);
    }

    /**
     * Create database connection.
     */
    private function createConnection(Paths $paths, string $environment): Connection
    {
        $config = $this->getDatabaseConfig($paths, $environment);

        return new Connection($config);
    }

    /**
     * Get database configuration.
     *
     * @return array<string, mixed>
     */
    private function getDatabaseConfig(Paths $paths, string $environment): array
    {
        $databasePath = $paths->base() . '/database/app.db';

        // Ensure database directory exists
        $databaseDir = dirname($databasePath);
        if (!is_dir($databaseDir)) {
            mkdir($databaseDir, 0755, true);
        }

        return [
            'driver' => Sqlite::class,
            'database' => $databasePath,
            'encoding' => 'utf8',
            'timezone' => 'UTC',
            'cacheMetadata' => $environment === 'production',
            'quoteIdentifiers' => false,
            'persistent' => false,
            'log' => $environment === 'development',
            'flags' => [],
            'init' => [
                'PRAGMA foreign_keys = ON',
                'PRAGMA journal_mode = WAL',
                'PRAGMA synchronous = NORMAL',
                'PRAGMA cache_size = 1000',
                'PRAGMA temp_store = MEMORY',
            ],
        ];
    }

    /**
     * Initialize database with tables.
     */
    public function initializeDatabase(): void
    {
        $this->createUsersTable();
        $this->createSecurityLoginAttemptsTable();
        $this->createUserActivityTable();
    }

    /**
     * Create users table.
     */
    private function createUsersTable(): void
    {
        if ($this->tableExists('users')) {
            return;
        }

        $sql = '
        CREATE TABLE users (
            id TEXT PRIMARY KEY,
            email TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT "user",
            status TEXT DEFAULT "active",
            email_verified INTEGER DEFAULT 0,
            email_verification_token TEXT,
            password_reset_token TEXT,
            password_reset_expires TEXT,
            last_login_at TEXT,
            login_count INTEGER DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )';

        $this->execute($sql);
    }

    /**
     * Create security login attempts table.
     */
    private function createSecurityLoginAttemptsTable(): void
    {
        if ($this->tableExists('security_login_attempts')) {
            return;
        }

        $sql = '
        CREATE TABLE security_login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            success INTEGER NOT NULL DEFAULT 0,
            attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_agent TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )';

        $this->execute($sql);

        // Create indexes for performance
        $this->execute('CREATE INDEX idx_security_login_email ON security_login_attempts(email)');
        $this->execute('CREATE INDEX idx_security_login_ip ON security_login_attempts(ip_address)');
        $this->execute('CREATE INDEX idx_security_login_attempted_at ON security_login_attempts(attempted_at)');
    }

    /**
     * Create user activity table.
     */
    private function createUserActivityTable(): void
    {
        if ($this->tableExists('user_activity')) {
            return;
        }

        $sql = '
        CREATE TABLE user_activity (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            action TEXT NOT NULL,
            description TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )';

        $this->execute($sql);

        // Create indexes
        $this->execute('CREATE INDEX idx_user_activity_user_id ON user_activity(user_id)');
        $this->execute('CREATE INDEX idx_user_activity_action ON user_activity(action)');
        $this->execute('CREATE INDEX idx_user_activity_created_at ON user_activity(created_at)');
    }
}
