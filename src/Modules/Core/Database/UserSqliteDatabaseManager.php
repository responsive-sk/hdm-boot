<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Database;

use HdmBoot\SharedKernel\Database\AbstractDatabaseManager;
use PDO;
use PDOException;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

/**
 * User SQLite Database Manager - Handles user.db SQLite database.
 *
 * ARCHITECTURE PRINCIPLE: User system has its own isolated database.
 * This database contains ONLY user-related data (users, sessions, preferences, activity).
 */
final class UserSqliteDatabaseManager extends AbstractDatabaseManager
{
    public function __construct(
        ?string $databasePath = null,
        ?Paths $paths = null
    ) {
        $paths = $paths ?? new Paths(__DIR__ . '/../../..');
        $databasePath = $databasePath ?? $paths->path('storage/user.db');
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
        return 'user';
    }

    /**
     * Create PDO connection to user.db.
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
            throw new RuntimeException("Failed to connect to user database: {$e->getMessage()} | Path: {$this->secureDatabasePath} | Dir exists: " . (is_dir(dirname($this->secureDatabasePath)) ? 'yes' : 'no') . ' | File exists: ' . (file_exists($this->secureDatabasePath) ? 'yes' : 'no'), 0, $e);
        } catch (\Exception $e) {
            throw new RuntimeException("User database error: {$e->getMessage()} | Path: {$this->secureDatabasePath}", 0, $e);
        }
    }

    /**
     * Initialize user database schema.
     */
    protected function initializeDatabase(): void
    {
        try {
            $connection = $this->getConnection();

            // Users table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id TEXT PRIMARY KEY,
                    email TEXT UNIQUE NOT NULL,
                    name TEXT NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'user',
                    status TEXT NOT NULL DEFAULT 'active',
                    email_verified INTEGER NOT NULL DEFAULT 0,
                    last_login_at TEXT,
                    login_count INTEGER DEFAULT 0,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");

            // User sessions table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS user_sessions (
                    session_id TEXT PRIMARY KEY,
                    user_id TEXT NOT NULL,
                    session_data TEXT,
                    expires_at TEXT NOT NULL,
                    ip_address TEXT,
                    user_agent TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // User preferences table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS user_preferences (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id TEXT NOT NULL,
                    preference_key TEXT NOT NULL,
                    preference_value TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE(user_id, preference_key)
                )
            ");

            // User activity logs table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS user_activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id TEXT,
                    action TEXT NOT NULL,
                    resource_type TEXT,
                    resource_id TEXT,
                    details TEXT,
                    ip_address TEXT,
                    user_agent TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ");

            // Create indexes for performance
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON user_preferences(user_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_user_activity_user_id ON user_activity_logs(user_id)');
            $connection->exec('CREATE INDEX IF NOT EXISTS idx_user_activity_created ON user_activity_logs(created_at)');

            // Create FTS5 virtual table for user search
            $connection->exec("
                CREATE VIRTUAL TABLE IF NOT EXISTS users_fts USING fts5(
                    id,
                    email,
                    name,
                    content='users',
                    content_rowid='rowid'
                )
            ");

            // Create triggers to keep FTS index up to date
            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS users_ai AFTER INSERT ON users BEGIN
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ');

            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS users_au AFTER UPDATE ON users BEGIN
                    DELETE FROM users_fts WHERE rowid = old.rowid;
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ');

            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS users_ad AFTER DELETE ON users BEGIN
                    DELETE FROM users_fts WHERE rowid = old.rowid;
                END
            ');

            // Create default test user if none exists
            $this->createDefaultTestUser();
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to initialize user database: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create default test user for development.
     */
    protected function createDefaultTestUser(): void
    {
        $connection = $this->getConnection();

        // Check if test@example.com exists
        $stmt = $connection->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute(['test@example.com']);
        $testUserExists = (int) $stmt->fetchColumn() > 0;

        // Check if user@example.com exists
        $stmt->execute(['user@example.com']);
        $userUserExists = (int) $stmt->fetchColumn() > 0;

        // Prepare statement for user creation
        $stmt = $connection->prepare('
            INSERT INTO users (id, email, name, password_hash, role, status, email_verified, created_at, updated_at)
            VALUES (:id, :email, :name, :password_hash, :role, :status, :email_verified, :created_at, :updated_at)
        ');

        if (!$testUserExists) {
            // Create test@example.com user
            $testPasswordHash = password_hash('password123', PASSWORD_DEFAULT);
            error_log('🔍 DB DEBUG: Creating test@example.com with password hash: ' . $testPasswordHash);

            $testUser = [
                'id'             => 'user-' . uniqid(),
                'email'          => 'test@example.com',
                'name'           => 'Test User',
                'password_hash'  => $testPasswordHash,
                'role'           => 'user',
                'status'         => 'active',
                'email_verified' => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $stmt->execute($testUser);
        }

        if (!$userUserExists) {
            // Create user@example.com user
            $userUser = [
                'id'             => 'user-' . uniqid(),
                'email'          => 'user@example.com',
                'name'           => 'Example User',
                'password_hash'  => password_hash('user123', PASSWORD_DEFAULT),
                'role'           => 'user',
                'status'         => 'active',
                'email_verified' => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $stmt->execute($userUser);
        }
    }

    /**
     * Get database statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $connection = $this->getConnection();
        $stats = [];

        $tables = ['users', 'user_sessions', 'user_preferences', 'user_activity_logs'];

        foreach ($tables as $table) {
            $stmt = $connection->query("SELECT COUNT(*) FROM {$table}");
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
        $stmt = $connection->prepare("DELETE FROM user_sessions WHERE expires_at < datetime('now')");
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

        // Clean expired user sessions
        $stmt = $connection->prepare("DELETE FROM user_sessions WHERE expires_at < datetime('now')");
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
