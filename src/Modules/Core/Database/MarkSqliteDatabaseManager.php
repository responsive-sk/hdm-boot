<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Database;

use HdmBoot\SharedKernel\Database\AbstractDatabaseManager;
use PDO;
use PDOException;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

/**
 * Mark SQLite Database Manager - Handles mark.db SQLite database.
 *
 * ARCHITECTURE PRINCIPLE: Mark system has its own isolated database.
 * This database contains ONLY mark-related data (mark users, sessions, audit logs).
 */
final class MarkSqliteDatabaseManager extends AbstractDatabaseManager
{
    public function __construct(
        string $databasePath = 'storage/mark.db',
        ?Paths $paths = null
    ) {
        parent::__construct($databasePath, [], $paths);
    }

    /**
     * Get database type identifier.
     */
    public function getDatabaseType(): string
    {
        return 'sqlite';
    }

    /**
     * Get database name/identifier.
     */
    public function getDatabaseName(): string
    {
        return 'mark';
    }

    /**
     * Create PDO connection to mark.db.
     */
    protected function createConnection(): PDO
    {
        try {
            // Debug info for production troubleshooting
            $dbPath = $this->secureDatabasePath;
            $dbDir = dirname($dbPath);

            // Check if directory exists and is writable
            if (!is_dir($dbDir)) {
                // Try to create directory with 777 permissions for shared hosting
                if (!mkdir($dbDir, 0o777, true)) {
                    throw new RuntimeException("Cannot create database directory: {$dbDir}");
                }
                chmod($dbDir, 0o777); // Ensure 777 permissions
            }

            // Check if database file exists
            if (!file_exists($dbPath)) {
                // Try to create empty database file
                if (!touch($dbPath)) {
                    throw new RuntimeException("Cannot create database file: {$dbPath}");
                }
                chmod($dbPath, 0o666); // Ensure 666 permissions
            }

            // Check permissions
            if (!is_readable($dbPath)) {
                throw new RuntimeException("Database file not readable: {$dbPath}");
            }

            if (!is_writable($dbPath)) {
                throw new RuntimeException("Database file not writable: {$dbPath}");
            }

            $dsn = 'sqlite:' . $dbPath;
            $connection = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Enable WAL mode for better concurrency
            $connection->exec('PRAGMA journal_mode=WAL');
            $connection->exec('PRAGMA synchronous=NORMAL');
            $connection->exec('PRAGMA cache_size=1000');
            $connection->exec('PRAGMA temp_store=MEMORY');

            return $connection;
        } catch (PDOException $e) {
            throw new RuntimeException("Failed to connect to mark database: {$e->getMessage()} | Path: {$this->secureDatabasePath} | Dir exists: " . (is_dir(dirname($this->secureDatabasePath)) ? 'yes' : 'no') . ' | File exists: ' . (file_exists($this->secureDatabasePath) ? 'yes' : 'no'), 0, $e);
        } catch (\Exception $e) {
            throw new RuntimeException("Mark database error: {$e->getMessage()} | Path: {$this->secureDatabasePath}", 0, $e);
        }
    }

    /**
     * Initialize mark database schema.
     */
    protected function initializeDatabase(): void
    {
        try {
            // Mark users table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS mark_users (
                    id TEXT PRIMARY KEY,
                    username TEXT UNIQUE NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'mark_admin',
                    status TEXT NOT NULL DEFAULT 'active',
                    last_login_at TEXT,
                    login_count INTEGER DEFAULT 0,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");

            // Mark sessions table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS mark_sessions (
                    session_id TEXT PRIMARY KEY,
                    mark_user_id TEXT NOT NULL,
                    session_data TEXT,
                    expires_at TEXT NOT NULL,
                    ip_address TEXT,
                    user_agent TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (mark_user_id) REFERENCES mark_users(id) ON DELETE CASCADE
                )
            ");

            // Mark audit logs table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS mark_audit_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    mark_user_id TEXT,
                    action TEXT NOT NULL,
                    resource_type TEXT,
                    resource_id TEXT,
                    details TEXT,
                    ip_address TEXT,
                    user_agent TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (mark_user_id) REFERENCES mark_users(id) ON DELETE SET NULL
                )
            ");

            // Mark settings table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS mark_settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key TEXT UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type TEXT DEFAULT 'string',
                    description TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");

            // Create indexes for performance
            $connection = $this->getConnection();
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_users_email ON mark_users(email)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_users_username ON mark_users(username)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_sessions_user_id ON mark_sessions(mark_user_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_sessions_expires ON mark_sessions(expires_at)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_audit_user_id ON mark_audit_logs(mark_user_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_audit_created ON mark_audit_logs(created_at)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_mark_settings_key ON mark_settings(setting_key)');

            // Create default mark user if none exists
            $this->createDefaultMarkUser();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to initialize mark database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create default mark user for initial setup.
     */
    protected function createDefaultMarkUser(): void
    {
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM mark_users');
        $userCount = 0;
        if ($stmt !== false) {
            $count = $stmt->fetchColumn();
            $userCount = $count !== false ? (int) $count : 0;
        }

        if ($userCount === 0) {
            $defaultMarkUser = [
                'id'            => 'mark-' . uniqid(),
                'username'      => 'mark',
                'email'         => 'mark@responsive.sk',
                'password_hash' => password_hash('mark123', PASSWORD_DEFAULT),
                'role'          => 'mark_admin',
                'status'        => 'active',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];

            $stmt = $this->getConnection()->prepare('
                INSERT INTO mark_users (id, username, email, password_hash, role, status, created_at, updated_at)
                VALUES (:id, :username, :email, :password_hash, :role, :status, :created_at, :updated_at)
            ');

            $stmt->execute($defaultMarkUser);
        }
    }

    /**
     * Get database statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $stats = [];

        $tables = ['mark_users', 'mark_sessions', 'mark_audit_logs', 'mark_settings'];

        foreach ($tables as $table) {
            $stmt = $this->getConnection()->query("SELECT COUNT(*) FROM {$table}");
            if ($stmt !== false) {
                $count = $stmt->fetchColumn();
                $stats[$table] = $count !== false ? (int) $count : 0;
            } else {
                $stats[$table] = 0;
            }
        }

        return $stats;
    }

    /**
     * Clean expired sessions.
     */
    public function cleanExpiredSessions(): int
    {
        $connection = $this->getConnection();
        $stmt = $connection->prepare("DELETE FROM mark_sessions WHERE expires_at < datetime('now')");
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Clean expired data.
     */
    public function cleanExpiredData(): int
    {
        $connection = $this->getConnection();
        $totalCleaned = 0;

        // Clean expired mark sessions
        $stmt = $connection->prepare("DELETE FROM mark_sessions WHERE expires_at < datetime('now')");
        $stmt->execute();
        $totalCleaned += $stmt->rowCount();

        return $totalCleaned;
    }

    /**
     * Close database connection.
     */
    public function close(): void
    {
        $this->connection = null;
    }
}
