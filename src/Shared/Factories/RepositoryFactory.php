<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Factories;

use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Shared\Contracts\DatabaseManagerInterface;
use PDO;

/**
 * Repository Factory.
 *
 * PROPERLY ABSTRACT approach - decides which repository implementation to use
 * based on configuration, not hardcoded!
 */
final class RepositoryFactory
{
    public function __construct(
        private readonly string $repositoryType = 'sqlite',
        private readonly string $databaseManager = 'pdo'
    ) {
    }

    /**
     * Create User Repository based on configuration.
     */
    public function createUserRepository(
        ?PDO $pdo = null,
        ?DatabaseManagerInterface $databaseManager = null
    ): UserRepositoryInterface {
        return match ($this->repositoryType) {
            'sqlite' => $this->createSqliteUserRepository($pdo),
            'mysql' => $this->createMysqlUserRepository($pdo),
            'doctrine' => $this->createDoctrineUserRepository($databaseManager),
            'cycle' => $this->createCycleUserRepository($databaseManager),
            'cake' => $this->createCakeUserRepository($databaseManager),
            default => throw new \InvalidArgumentException(
                "Unsupported repository type: {$this->repositoryType}"
            ),
        };
    }

    /**
     * Create SQLite User Repository.
     */
    private function createSqliteUserRepository(?PDO $pdo): UserRepositoryInterface
    {
        if ($pdo === null) {
            throw new \InvalidArgumentException('PDO is required for SQLite repository');
        }

        return new SqliteUserRepository($pdo);
    }

    /**
     * Create MySQL User Repository.
     */
    private function createMysqlUserRepository(?PDO $pdo): UserRepositoryInterface
    {
        if ($pdo === null) {
            throw new \InvalidArgumentException('PDO is required for MySQL repository');
        }

        // Future: MysqlUserRepository
        return new SqliteUserRepository($pdo); // Fallback for now
    }

    /**
     * Create Doctrine User Repository.
     */
    private function createDoctrineUserRepository(?DatabaseManagerInterface $databaseManager): UserRepositoryInterface
    {
        if ($databaseManager === null) {
            throw new \InvalidArgumentException('DatabaseManager is required for Doctrine repository');
        }

        // Future: DoctrineUserRepository
        throw new \RuntimeException('Doctrine User Repository not implemented yet');
    }

    /**
     * Create Cycle ORM User Repository.
     */
    private function createCycleUserRepository(?DatabaseManagerInterface $databaseManager): UserRepositoryInterface
    {
        if ($databaseManager === null) {
            throw new \InvalidArgumentException('DatabaseManager is required for Cycle repository');
        }

        // Future: CycleUserRepository
        throw new \RuntimeException('Cycle User Repository not implemented yet');
    }

    /**
     * Create CakePHP Database User Repository.
     */
    private function createCakeUserRepository(?DatabaseManagerInterface $databaseManager): UserRepositoryInterface
    {
        if ($databaseManager === null) {
            throw new \InvalidArgumentException('DatabaseManager is required for CakePHP repository');
        }

        // Future: CakeUserRepository
        throw new \RuntimeException('CakePHP User Repository not implemented yet');
    }

    /**
     * Get supported repository types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return ['sqlite', 'mysql', 'doctrine', 'cycle', 'cake'];
    }

    /**
     * Check if repository type is supported.
     */
    public function isTypeSupported(string $type): bool
    {
        return in_array($type, $this->getSupportedTypes(), true);
    }

    /**
     * Get current repository type.
     */
    public function getRepositoryType(): string
    {
        return $this->repositoryType;
    }

    /**
     * Get current database manager.
     */
    public function getDatabaseManager(): string
    {
        return $this->databaseManager;
    }
}
