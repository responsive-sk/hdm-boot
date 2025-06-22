<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Storage\Services;

use HdmBoot\SharedKernel\Services\PathsFactory;
use PDO;

/**
 * Database Manager.
 *
 * Manages multiple SQLite databases for different purposes.
 * Prevents read/write conflicts by separating databases by function.
 *
 * Databases are stored in var/orbit/ (Orbit-style) separate from content files.
 */
class DatabaseManager
{
    /**
     * Database connections.
     *
     * @var array<string, PDO>
     */
    private static array $connections = [];

    /**
     * Database configurations.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $configs = [];

    /**
     * Base directory for databases.
     */
    private static string $baseDirectory = '';

    /**
     * Initialize database manager.
     */
    public static function initialize(string $contentDirectory): void
    {
        // Use var/orbit for databases (Orbit-style) with PathsFactory
        $paths = PathsFactory::create();
        self::$baseDirectory = $paths->getPath(dirname($contentDirectory), 'var/orbit');

        // Ensure directory exists
        if (!is_dir(self::$baseDirectory)) {
            mkdir(self::$baseDirectory, 0755, true);
        }

        // Register default database configurations
        self::registerDefaultDatabases();
    }

    /**
     * Register a database configuration.
     *
     * @param array<string, mixed> $config
     */
    public static function registerDatabase(string $name, array $config): void
    {
        self::$configs[$name] = $config;
    }

    /**
     * Get database connection.
     */
    public static function getConnection(string $database): PDO
    {
        if (!isset(self::$connections[$database])) {
            self::$connections[$database] = self::createConnection($database);
        }

        return self::$connections[$database];
    }

    /**
     * Create new database connection.
     */
    private static function createConnection(string $database): PDO
    {
        if (!isset(self::$configs[$database])) {
            throw new \InvalidArgumentException("Database configuration '{$database}' not found");
        }

        $config = self::$configs[$database];
        $filenameRaw = $config['filename'] ?? $database . '.db';
        $filename = is_string($filenameRaw) ? $filenameRaw : $database . '.db';
        $path = self::buildSecurePath(self::$baseDirectory, $filename);

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Performance optimizations
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA cache_size = 1000');
        $pdo->exec('PRAGMA temp_store = MEMORY');

        return $pdo;
    }

    /**
     * Register default database configurations.
     */
    private static function registerDefaultDatabases(): void
    {
        // Main application database
        self::registerDatabase('app', [
            'filename' => 'app.db',
            'description' => 'Main application database for users, sessions, preferences',
            'tables' => [
                'users' => 'Application users and authentication',
                'user_sessions' => 'User session management',
                'user_preferences' => 'User settings and preferences',
                'user_activity_logs' => 'User activity tracking',
                'notifications' => 'User notifications',
            ],
        ]);

        // Mark admin database
        self::registerDatabase('mark', [
            'filename' => 'mark.db',
            'description' => 'Mark admin system database for admin users and operations',
            'tables' => [
                'mark_users' => 'Admin users for Mark system',
                'mark_sessions' => 'Admin session management',
                'mark_settings' => 'Admin configuration settings',
                'mark_audit_logs' => 'Admin action audit trail',
                'content_revisions' => 'Article revision history',
                'publishing_queue' => 'Scheduled content publishing',
            ],
        ]);

        // Cache and temporary data database
        self::registerDatabase('cache', [
            'filename' => 'cache.db',
            'description' => 'Cache and temporary data storage',
            'tables' => [
                'file_metadata_cache' => 'File modification times and metadata',
                'query_result_cache' => 'Cached query results',
                'search_index_cache' => 'Search index data',
                'temp_uploads' => 'Temporary file uploads',
                'background_jobs' => 'Background job queue',
                'system_metrics' => 'Performance and system metrics',
            ],
        ]);

        // Analytics database (optional)
        self::registerDatabase('analytics', [
            'filename' => 'analytics.db',
            'description' => 'Analytics and reporting data',
            'tables' => [
                'page_views' => 'Article and page view tracking',
                'user_engagement' => 'User interaction metrics',
                'search_queries' => 'Search query analytics',
                'performance_logs' => 'Application performance data',
                'error_logs' => 'Application error tracking',
            ],
        ]);
    }

    /**
     * Get all registered databases.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getDatabases(): array
    {
        return self::$configs;
    }

    /**
     * Get database health status.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getHealthStatus(): array
    {
        $status = [];

        foreach (self::$configs as $name => $config) {
            $filenameRaw = $config['filename'] ?? $name . '.db';
            $filename = is_string($filenameRaw) ? $filenameRaw : $name . '.db';
            $path = self::buildSecurePath(self::$baseDirectory, $filename);

            $dbStatus = [
                'name' => $name,
                'filename' => $filename,
                'path' => $path,
                'exists' => file_exists($path),
                'size' => file_exists($path) ? filesize($path) : 0,
                'writable' => is_writable(dirname($path)),
                'description' => $config['description'] ?? '',
                'tables' => $config['tables'] ?? [],
            ];

            // Test connection if file exists
            if ($dbStatus['exists']) {
                try {
                    $pdo = self::getConnection($name);
                    $dbStatus['connected'] = true;

                    // Get SQLite version
                    $stmt = $pdo->query('SELECT sqlite_version()');
                    if ($stmt !== false) {
                        $version = $stmt->fetchColumn();
                        $dbStatus['sqlite_version'] = is_string($version) ? $version : 'unknown';
                    }

                    // Get table count
                    $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
                    if ($stmt !== false) {
                        $count = $stmt->fetchColumn();
                        $dbStatus['table_count'] = is_numeric($count) ? (int) $count : 0;
                    }
                } catch (\Exception $e) {
                    $dbStatus['connected'] = false;
                    $dbStatus['error'] = $e->getMessage();
                }
            } else {
                $dbStatus['connected'] = false;
            }

            $status[$name] = $dbStatus;
        }

        return $status;
    }

    /**
     * Close all connections.
     */
    public static function closeAll(): void
    {
        self::$connections = [];
    }

    /**
     * Get database file path.
     */
    public static function getDatabasePath(string $database): string
    {
        if (!isset(self::$configs[$database])) {
            throw new \InvalidArgumentException("Database configuration '{$database}' not found");
        }

        $config = self::$configs[$database];
        $filenameRaw = $config['filename'] ?? $database . '.db';
        $filename = is_string($filenameRaw) ? $filenameRaw : $database . '.db';

        return self::buildSecurePath(self::$baseDirectory, $filename);
    }

    /**
     * Create database tables for a specific database.
     */
    public static function createTables(string $database): void
    {
        $pdo = self::getConnection($database);

        switch ($database) {
            case 'app':
                self::createAppTables($pdo);
                break;
            case 'mark':
                self::createMarkTables($pdo);
                break;
            case 'cache':
                self::createCacheTables($pdo);
                break;
            case 'analytics':
                self::createAnalyticsTables($pdo);
                break;
        }
    }

    /**
     * Create app database tables.
     */
    private static function createAppTables(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                first_name TEXT,
                last_name TEXT,
                role TEXT DEFAULT "user",
                status TEXT DEFAULT "active",
                email_verified INTEGER DEFAULT 0,
                email_verified_at TEXT,
                last_login_at TEXT,
                login_count INTEGER DEFAULT 0,
                preferences TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS user_sessions (
                id TEXT PRIMARY KEY,
                user_id INTEGER,
                ip_address TEXT,
                user_agent TEXT,
                payload TEXT,
                last_activity INTEGER,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ');
    }

    /**
     * Create mark database tables.
     */
    private static function createMarkTables(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS mark_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT "admin",
                status TEXT DEFAULT "active",
                last_login_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS mark_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                resource_type TEXT,
                resource_id TEXT,
                details TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES mark_users (id)
            )
        ');
    }

    /**
     * Create cache database tables.
     */
    private static function createCacheTables(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS file_metadata_cache (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path TEXT UNIQUE NOT NULL,
                modified_time INTEGER,
                file_size INTEGER,
                checksum TEXT,
                metadata TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS query_result_cache (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cache_key TEXT UNIQUE NOT NULL,
                result_data TEXT,
                expires_at INTEGER,
                created_at TEXT NOT NULL
            )
        ');
    }

    /**
     * Create analytics database tables.
     */
    private static function createAnalyticsTables(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS page_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url TEXT NOT NULL,
                user_id INTEGER,
                ip_address TEXT,
                user_agent TEXT,
                referer TEXT,
                created_at TEXT NOT NULL
            )
        ');
    }

    /**
     * Build secure path for database files.
     *
     * Prevents path traversal attacks by validating filename.
     */
    private static function buildSecurePath(string $baseDirectory, string $filename): string
    {
        // Validate filename for security
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new \InvalidArgumentException("Invalid database filename: {$filename}");
        }

        if (str_contains($filename, '~')) {
            throw new \InvalidArgumentException("Home directory access not allowed: {$filename}");
        }

        // Use PathsFactory for secure cross-platform path joining
        $paths = PathsFactory::create();
        return $paths->getPath($baseDirectory, $filename);
    }
}
