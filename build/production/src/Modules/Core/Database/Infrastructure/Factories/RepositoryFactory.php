<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Database\Infrastructure\Factories;

use HdmBoot\Modules\Core\Database\Domain\Contracts\DatabaseManagerInterface;
use HdmBoot\Modules\Core\User\Repository\SqliteUserRepository;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
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
            'mysql'  => $this->createMysqlUserRepository($pdo),
            default  => throw new \InvalidArgumentException(
                "Unsupported repository type: {$this->repositoryType}. Only 'sqlite' and 'mysql' are supported."
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

    // Note: Doctrine, Cycle ORM, and CakePHP repository implementations
    // have been removed as part of PDO-only refactoring.
    // Only SQLite and MySQL (PDO-based) repositories are supported.

    /**
     * Get supported repository types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return ['sqlite', 'mysql'];
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
