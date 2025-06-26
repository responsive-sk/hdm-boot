<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Database;

use PDO;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Abstract Database Manager.
 * 
 * Defines the contract for all database managers in HDM Boot.
 * Each database type (Mark, User, App) has its own manager that extends this.
 */
abstract class AbstractDatabaseManager
{
    protected ?PDO $connection = null;
    protected readonly string $secureDatabasePath;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        protected readonly string $databasePath,
        protected readonly array $options = [],
        protected readonly ?Paths $paths = null
    ) {
        // Use secure path resolution
        $this->secureDatabasePath = $this->resolveDatabasePath($databasePath);
    }

    /**
     * Resolve database path securely using Paths service.
     */
    protected function resolveDatabasePath(string $path): string
    {
        if ($this->paths !== null) {
            // Use Paths service for secure path resolution
            return $this->paths->path($path);
        }

        // Fallback: basic security check
        $realPath = realpath(dirname($path));
        if ($realPath === false) {
            // Directory doesn't exist, create it securely with strict permissions
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true); // 755 = rwxr-xr-x (owner: rwx, group: r-x, other: r-x)
                // Set directory permissions for shared hosting compatibility
                chmod($directory, 0777);
            }
            $realPath = realpath(dirname($path));
        }

        if ($realPath === false) {
            throw new \RuntimeException("Cannot resolve secure path for database: {$path}");
        }

        return $realPath . DIRECTORY_SEPARATOR . basename($path);
    }
    
    /**
     * Get database connection.
     *
     * @throws \RuntimeException If connection cannot be established
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
            $this->initializeDatabase();
        }

        if ($this->connection === null) {
            throw new \RuntimeException('Database connection is null after initialization');
        }

        return $this->connection;
    }
    
    /**
     * Create database connection - implemented by concrete classes.
     */
    abstract protected function createConnection(): PDO;
    
    /**
     * Initialize database schema - implemented by concrete classes.
     */
    abstract protected function initializeDatabase(): void;
    
    /**
     * Get database statistics - implemented by concrete classes.
     *
     * @return array<string, mixed>
     */
    abstract public function getStatistics(): array;
    
    /**
     * Clean expired data - implemented by concrete classes.
     */
    abstract public function cleanExpiredData(): int;
    
    /**
     * Get database type identifier.
     */
    abstract public function getDatabaseType(): string;
    
    /**
     * Get database name/identifier.
     */
    abstract public function getDatabaseName(): string;
    
    /**
     * Check if database exists and is accessible.
     */
    public function isAccessible(): bool
    {
        try {
            $connection = $this->getConnection();
            $connection->query('SELECT 1');
            return true;
        } catch (\Exception) {
            return false;
        }
    }
    
    /**
     * Get database file size (for file-based databases).
     */
    public function getDatabaseSize(): int
    {
        if (file_exists($this->secureDatabasePath)) {
            return filesize($this->secureDatabasePath) ?: 0;
        }

        return 0;
    }

    /**
     * Get secure database path.
     */
    public function getSecureDatabasePath(): string
    {
        return $this->secureDatabasePath;
    }
    
    /**
     * Check database integrity.
     *
     * @return array<string, mixed>
     */
    public function checkIntegrity(): array
    {
        try {
            $connection = $this->getConnection();
            
            // Basic connectivity check
            $result = [
                'accessible' => true,
                'size' => $this->getDatabaseSize(),
                'type' => $this->getDatabaseType(),
                'name' => $this->getDatabaseName(),
                'tables' => $this->getTableList(),
                'errors' => [],
            ];
            
            return $result;
        } catch (\Exception $e) {
            return [
                'accessible' => false,
                'size' => 0,
                'type' => $this->getDatabaseType(),
                'name' => $this->getDatabaseName(),
                'tables' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }
    
    /**
     * Get list of tables in database.
     *
     * @return array<string>
     */
    protected function getTableList(): array
    {
        try {
            $connection = $this->getConnection();
            $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            $tables = [];
            if ($stmt !== false) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (is_array($row) && isset($row['name']) && is_string($row['name'])) {
                        $tables[] = $row['name'];
                    }
                }
            }

            return $tables;
        } catch (\Exception) {
            return [];
        }
    }
    
    /**
     * Execute SQL file.
     */
    protected function executeSqlFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("SQL file not found: {$filePath}");
        }
        
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new \RuntimeException("Failed to read SQL file: {$filePath}");
        }
        
        $this->getConnection()->exec($sql);
    }
    
    /**
     * Begin transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction.
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction.
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Execute query with parameters.
     *
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }
    
    /**
     * Close database connection.
     */
    public function close(): void
    {
        $this->connection = null;
    }
    
    /**
     * Destructor - ensure connection is closed.
     */
    public function __destruct()
    {
        $this->close();
    }
}
