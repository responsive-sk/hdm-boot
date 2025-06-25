<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User\Database;

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
        string $databasePath = 'storage/user.db',
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
        return 'user';
    }
    
    /**
     * Get User database connection.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
            $this->initializeDatabase();
        }
        
        return $this->connection;
    }
    
    /**
     * Create PDO connection to user.db.
     */
    private function createConnection(): PDO
    {
        try {
            $dsn = 'sqlite:' . $this->databasePath;
            $connection = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Enable WAL mode for better concurrency
            $connection->exec('PRAGMA journal_mode=WAL');
            $connection->exec('PRAGMA synchronous=NORMAL');
            $connection->exec('PRAGMA cache_size=1000');
            $connection->exec('PRAGMA temp_store=MEMORY');
            
            return $connection;
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to connect to user database: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Initialize user database schema.
     */
    private function initializeDatabase(): void
    {
        try {
            // Users table
            $this->connection->exec("
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
            $this->connection->exec("
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
            $this->connection->exec("
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
            $this->connection->exec("
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
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_users_status ON users(status)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_user_sessions_expires ON user_sessions(expires_at)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON user_preferences(user_id)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_user_activity_user_id ON user_activity_logs(user_id)");
            $this->connection->exec("CREATE INDEX IF NOT EXISTS idx_user_activity_created ON user_activity_logs(created_at)");
            
            // Create FTS5 virtual table for user search
            $this->connection->exec("
                CREATE VIRTUAL TABLE IF NOT EXISTS users_fts USING fts5(
                    id,
                    email,
                    name,
                    content='users',
                    content_rowid='rowid'
                )
            ");
            
            // Create triggers to keep FTS index up to date
            $this->connection->exec('
                CREATE TRIGGER IF NOT EXISTS users_ai AFTER INSERT ON users BEGIN
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ');
            
            $this->connection->exec('
                CREATE TRIGGER IF NOT EXISTS users_au AFTER UPDATE ON users BEGIN
                    DELETE FROM users_fts WHERE rowid = old.rowid;
                    INSERT INTO users_fts(rowid, id, email, name)
                    VALUES (new.rowid, new.id, new.email, new.name);
                END
            ');
            
            $this->connection->exec('
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
    private function createDefaultTestUser(): void
    {
        $stmt = $this->connection->query('SELECT COUNT(*) FROM users');
        $userCount = (int) $stmt->fetchColumn();
        
        if ($userCount === 0) {
            $defaultUser = [
                'id' => 'user-' . uniqid(),
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $stmt = $this->connection->prepare("
                INSERT INTO users (id, email, name, password_hash, role, status, email_verified, created_at, updated_at)
                VALUES (:id, :email, :name, :password_hash, :role, :status, :email_verified, :created_at, :updated_at)
            ");
            
            $stmt->execute($defaultUser);
        }
    }
    
    /**
     * Get database statistics.
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        $tables = ['users', 'user_sessions', 'user_preferences', 'user_activity_logs'];
        
        foreach ($tables as $table) {
            $stmt = $this->connection->query("SELECT COUNT(*) FROM {$table}");
            $stats[$table] = (int) $stmt->fetchColumn();
        }
        
        return $stats;
    }
    
    /**
     * Clean expired sessions.
     */
    public function cleanExpiredSessions(): int
    {
        $stmt = $this->connection->prepare("DELETE FROM user_sessions WHERE expires_at < datetime('now')");
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Close database connection.
     */
    public function close(): void
    {
        $this->connection = null;
    }
}
