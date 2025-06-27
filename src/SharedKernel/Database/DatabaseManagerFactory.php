<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Database;

use HdmBoot\Modules\Core\Database\MarkSqliteDatabaseManager;
use HdmBoot\Modules\Core\Database\SystemSqliteDatabaseManager;
use HdmBoot\Modules\Core\Database\UserSqliteDatabaseManager;
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
    public function createMarkManager(?string $databasePath = null): MarkSqliteDatabaseManager
    {
        $dbPath = $databasePath ?? $this->paths->path('storage/mark.db');
        return new MarkSqliteDatabaseManager($dbPath, $this->paths);
    }

    /**
     * Create User database manager.
     */
    public function createUserManager(?string $databasePath = null): UserSqliteDatabaseManager
    {
        $dbPath = $databasePath ?? $this->paths->path('storage/user.db');
        return new UserSqliteDatabaseManager($dbPath, $this->paths);
    }

    /**
     * Create System database manager.
     */
    public function createSystemManager(?string $databasePath = null): SystemSqliteDatabaseManager
    {
        $dbPath = $databasePath ?? $this->paths->path('storage/system.db');
        return new SystemSqliteDatabaseManager($dbPath, $this->paths);
    }

    /**
     * Get all database managers.
     *
     * @return array<string, AbstractDatabaseManager>
     */
    public function getAllManagers(): array
    {
        return [
            'mark'   => $this->createMarkManager(),
            'user'   => $this->createUserManager(),
            'system' => $this->createSystemManager(),
        ];
    }

    /**
     * Get database manager by name.
     */
    public function getManager(string $name): AbstractDatabaseManager
    {
        return match ($name) {
            'mark'   => $this->createMarkManager(),
            'user'   => $this->createUserManager(),
            'system' => $this->createSystemManager(),
            default  => throw new \InvalidArgumentException("Unknown database manager: {$name}"),
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

        foreach (['mark', 'user', 'system'] as $dbName) {
            try {
                $manager = $this->getManager($dbName);
                $health[$dbName] = $manager->checkIntegrity();
            } catch (\Exception $e) {
                $health[$dbName] = [
                    'accessible' => false,
                    'error'      => $e->getMessage(),
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
            'mark'   => $this->paths->path('storage/mark.db'),
            'user'   => $this->paths->path('storage/user.db'),
            'system' => $this->paths->path('storage/system.db'),
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

        // Initialize System database
        $systemManager = $this->createSystemManager();
        $systemManager->getConnection(); // This triggers initialization
    }

    /**
     * Clean expired data from all databases.
     *
     * @return array<string, int>
     */
    public function cleanAllExpiredData(): array
    {
        $results = [];

        foreach (['mark', 'user', 'system'] as $dbName) {
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
