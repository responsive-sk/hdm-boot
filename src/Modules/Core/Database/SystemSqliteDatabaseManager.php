<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Database;

use HdmBoot\SharedKernel\Database\AbstractDatabaseManager;
use PDO;
use PDOException;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

/**
 * System SQLite Database Manager - Handles system.db SQLite database.
 *
 * ARCHITECTURE PRINCIPLE: System database is for CORE SYSTEM modules only.
 * This database contains ONLY core system data (Cache, System logs, Template cache, File metadata).
 *
 * NOTE: Business modules (Blog, Orders, etc.) are OPTIONAL and should have their own databases.
 * This is for SYSTEM functionality only, not business logic.
 */
final class SystemSqliteDatabaseManager extends AbstractDatabaseManager
{
    public function __construct(
        string $databasePath = 'storage/system.db',
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
        return 'system';
    }
    
    /**
     * Create PDO connection to system.db.
     */
    protected function createConnection(): PDO
    {
        try {
            $dsn = 'sqlite:' . $this->secureDatabasePath;
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
            throw new RuntimeException('Failed to connect to system database: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Initialize system database schema.
     */
    protected function initializeDatabase(): void
    {
        try {
            // Blog articles table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS blog_articles (
                    id TEXT PRIMARY KEY,
                    title TEXT NOT NULL,
                    slug TEXT UNIQUE NOT NULL,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    author_id TEXT,
                    category TEXT,
                    tags TEXT, -- JSON array
                    status TEXT NOT NULL DEFAULT 'draft',
                    published_at TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");
            
            // System cache table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS system_cache (
                    cache_key TEXT PRIMARY KEY,
                    cache_value TEXT,
                    expires_at INTEGER,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");
            
            // System logs table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS system_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    level TEXT NOT NULL,
                    message TEXT NOT NULL,
                    context TEXT, -- JSON
                    channel TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");
            
            // File metadata cache table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS file_metadata_cache (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    file_path TEXT UNIQUE NOT NULL,
                    modified_time INTEGER,
                    file_size INTEGER,
                    checksum TEXT,
                    metadata TEXT, -- JSON
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                    updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");
            
            // Template cache table
            $this->getConnection()->exec("
                CREATE TABLE IF NOT EXISTS template_cache (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    template_path TEXT UNIQUE NOT NULL,
                    compiled_path TEXT,
                    template_hash TEXT,
                    compiled_at TEXT,
                    expires_at TEXT,
                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                )
            ");
            
            // Create indexes for performance
            $connection = $this->getConnection();
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_blog_articles_slug ON blog_articles(slug)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_blog_articles_status ON blog_articles(status)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_blog_articles_published ON blog_articles(published_at)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_system_cache_expires ON system_cache(expires_at)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_system_logs_level ON system_logs(level)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_system_logs_created ON system_logs(created_at)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_file_metadata_path ON file_metadata_cache(file_path)");
            $connection->exec("CREATE INDEX IF NOT EXISTS idx_template_cache_path ON template_cache(template_path)");
            
            // Create FTS5 virtual table for blog article search
            $connection->exec("
                CREATE VIRTUAL TABLE IF NOT EXISTS blog_articles_fts USING fts5(
                    id,
                    title,
                    content,
                    excerpt,
                    content='blog_articles',
                    content_rowid='rowid'
                )
            ");
            
            // Create triggers to keep FTS index up to date
            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS blog_articles_ai AFTER INSERT ON blog_articles BEGIN
                    INSERT INTO blog_articles_fts(rowid, id, title, content, excerpt)
                    VALUES (new.rowid, new.id, new.title, new.content, new.excerpt);
                END
            ');
            
            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS blog_articles_au AFTER UPDATE ON blog_articles BEGIN
                    DELETE FROM blog_articles_fts WHERE rowid = old.rowid;
                    INSERT INTO blog_articles_fts(rowid, id, title, content, excerpt)
                    VALUES (new.rowid, new.id, new.title, new.content, new.excerpt);
                END
            ');
            
            $connection->exec('
                CREATE TRIGGER IF NOT EXISTS blog_articles_ad AFTER DELETE ON blog_articles BEGIN
                    DELETE FROM blog_articles_fts WHERE rowid = old.rowid;
                END
            ');
            
            // Create sample blog article if none exists
            $this->createSampleBlogArticle();
            
        } catch (PDOException $e) {
            throw new RuntimeException('Failed to initialize app database: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Create sample blog article for development.
     */
    protected function createSampleBlogArticle(): void
    {
        $stmt = $this->getConnection()->query('SELECT COUNT(*) FROM blog_articles');
        $articleCount = 0;
        if ($stmt !== false) {
            $count = $stmt->fetchColumn();
            $articleCount = $count !== false ? (int) $count : 0;
        }
        
        if ($articleCount === 0) {
            $sampleArticle = [
                'id' => 'article-' . uniqid(),
                'title' => 'Welcome to HDM Boot Blog',
                'slug' => 'welcome-to-hdm-boot-blog',
                'content' => "# Welcome to HDM Boot Blog\n\nThis is your first blog article in HDM Boot framework.\n\n## Features\n\n- **Hexagonal Architecture** - Clean separation of concerns\n- **Domain-Driven Design** - Business logic first\n- **Modern PHP** - PHP 8.1+ with strict types\n- **Three-Database Architecture** - Mark, User, App isolation\n\n## Getting Started\n\nEdit this article or create new ones using the Mark system.\n\nEnjoy building with HDM Boot!",
                'excerpt' => 'Welcome to HDM Boot framework - a modern PHP framework built with Hexagonal Architecture and Domain-Driven Design principles.',
                'author_id' => 'system',
                'category' => 'announcement',
                'tags' => '["welcome", "hdm-boot", "framework", "php"]',
                'status' => 'published',
                'published_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $stmt = $this->getConnection()->prepare("
                INSERT INTO blog_articles (id, title, slug, content, excerpt, author_id, category, tags, status, published_at, created_at, updated_at)
                VALUES (:id, :title, :slug, :content, :excerpt, :author_id, :category, :tags, :status, :published_at, :created_at, :updated_at)
            ");
            
            $stmt->execute($sampleArticle);
        }
    }
    
    /**
     * Get database statistics.
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        $tables = ['blog_articles', 'app_cache', 'system_logs', 'file_metadata_cache', 'template_cache'];
        
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
     * Clean expired data.
     */
    public function cleanExpiredData(): int
    {
        $connection = $this->getConnection();
        $totalCleaned = 0;
        
        // Clean expired cache entries
        $stmt = $connection->prepare("DELETE FROM app_cache WHERE expires_at < ?");
        $stmt->execute([time()]);
        $totalCleaned += $stmt->rowCount();
        
        // Clean old system logs (older than 30 days)
        $stmt = $connection->prepare("DELETE FROM system_logs WHERE created_at < datetime('now', '-30 days')");
        $stmt->execute();
        $totalCleaned += $stmt->rowCount();
        
        // Clean expired template cache
        $stmt = $connection->prepare("DELETE FROM template_cache WHERE expires_at < datetime('now')");
        $stmt->execute();
        $totalCleaned += $stmt->rowCount();
        
        return $totalCleaned;
    }
}
