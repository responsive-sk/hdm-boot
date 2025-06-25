<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Database;

use HdmBoot\Modules\Core\App\Database\AppSqliteDatabaseManager;
use HdmBoot\Modules\Core\User\Database\UserSqliteDatabaseManager;
use HdmBoot\Modules\Mark\Database\MarkSqliteDatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Database Manager Factory.
 * 
 * Creates database managers with secure path resolution using Paths service.
 * Follows the three-database architecture: Mark, User, App.
 */
final class DatabaseManagerFactory
{
    public function __construct(
        private readonly Paths $paths
    ) {
    }
    
    /**
     * Create Mark database manager.
     */
    public function createMarkManager(string $databasePath = 'storage/mark.db'): MarkSqliteDatabaseManager
    {
        return new MarkSqliteDatabaseManager($databasePath, $this->paths);
    }
    
    /**
     * Create User database manager.
     */
    public function createUserManager(string $databasePath = 'storage/user.db'): UserSqliteDatabaseManager
    {
        return new UserSqliteDatabaseManager($databasePath, $this->paths);
    }
    
    /**
     * Create App database manager.
     */
    public function createAppManager(string $databasePath = 'storage/app.db'): AppSqliteDatabaseManager
    {
        return new AppSqliteDatabaseManager($databasePath, $this->paths);
    }
    
    /**
     * Get all database managers.
     * 
     * @return array<string, AbstractDatabaseManager>
     */
    public function getAllManagers(): array
    {
        return [
            'mark' => $this->createMarkManager(),
            'user' => $this->createUserManager(),
            'app' => $this->createAppManager(),
        ];
    }
    
    /**
     * Get database manager by name.
     */
    public function getManager(string $name): AbstractDatabaseManager
    {
        return match ($name) {
            'mark' => $this->createMarkManager(),
            'user' => $this->createUserManager(),
            'app' => $this->createAppManager(),
            default => throw new \InvalidArgumentException("Unknown database manager: {$name}"),
        };
    }
    
    /**
     * Check all databases health.
     * 
     * @return array<string, array<string, mixed>>
     */
    public function checkAllDatabasesHealth(): array
    {
        $health = [];
        
        foreach (['mark', 'user', 'app'] as $dbName) {
            try {
                $manager = $this->getManager($dbName);
                $health[$dbName] = $manager->checkIntegrity();
            } catch (\Exception $e) {
                $health[$dbName] = [
                    'accessible' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $health;
    }
    
    /**
     * Get database paths (for debugging/admin).
     * 
     * @return array<string, string>
     */
    public function getDatabasePaths(): array
    {
        return [
            'mark' => $this->paths->path('storage/mark.db'),
            'user' => $this->paths->path('storage/user.db'),
            'app' => $this->paths->path('storage/app.db'),
        ];
    }
    
    /**
     * Initialize all databases.
     */
    public function initializeAllDatabases(): void
    {
        // Initialize Mark database
        $markManager = $this->createMarkManager();
        $markManager->getConnection(); // This triggers initialization
        
        // Initialize User database
        $userManager = $this->createUserManager();
        $userManager->getConnection(); // This triggers initialization

        // Initialize App database
        $appManager = $this->createAppManager();
        $appManager->getConnection(); // This triggers initialization
    }
    
    /**
     * Clean expired data from all databases.
     * 
     * @return array<string, int>
     */
    public function cleanAllExpiredData(): array
    {
        $results = [];
        
        foreach (['mark', 'user', 'app'] as $dbName) {
            try {
                $manager = $this->getManager($dbName);
                $results[$dbName] = $manager->cleanExpiredData();
            } catch (\Exception $e) {
                $results[$dbName] = 0;
            }
        }
        
        return $results;
    }
}
