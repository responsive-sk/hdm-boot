<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Database\Domain\Contracts;

/**
 * Database Manager Interface.
 *
 * Abstraction for different database implementations:
 * - CakePHP Database (query builder)
 * - Doctrine ORM (full ORM)
 * - Raw PDO (fallback)
 *
 * This interface provides a clear contract that any database manager must implement.
 */
interface DatabaseManagerInterface
{
    /**
     * Get the underlying database connection.
     *
     * @return mixed The connection object (PDO, Cake\Database\Connection, Doctrine\DBAL\Connection, etc.)
     */
    public function getConnection(): mixed;

    /**
     * Execute raw SQL query.
     *
     * @param array<mixed> $params
     *
     * @return mixed Query result
     */
    public function execute(string $sql, array $params = []): mixed;

    /**
     * Begin database transaction.
     */
    public function beginTransaction(): void;

    /**
     * Commit database transaction.
     */
    public function commit(): void;

    /**
     * Rollback database transaction.
     */
    public function rollback(): void;

    /**
     * Execute callback within a transaction.
     * Automatically handles commit/rollback based on success/failure.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transactional(callable $callback): mixed;

    /**
     * Check if table exists in database.
     */
    public function tableExists(string $tableName): bool;

    /**
     * Initialize database schema (create tables, indexes, etc.).
     * Each implementation handles this differently.
     */
    public function initializeDatabase(): void;

    /**
     * Get database manager type/name.
     * Useful for debugging and logging.
     */
    public function getManagerType(): string;

    /**
     * Get database configuration info.
     *
     * @return array<string, mixed>
     */
    public function getConnectionInfo(): array;

    /**
     * Check database connection health.
     */
    public function isConnected(): bool;

    /**
     * Close database connection.
     */
    public function disconnect(): void;
}

/**
 * Query Builder Interface.
 *
 * For database managers that support query builders.
 */
interface QueryBuilderInterface
{
    /**
     * Create SELECT query builder.
     *
     * @return mixed Query builder instance
     */
    public function selectQuery(): mixed;

    /**
     * Create INSERT query builder.
     *
     * @return mixed Query builder instance
     */
    public function insertQuery(): mixed;

    /**
     * Create UPDATE query builder.
     *
     * @return mixed Query builder instance
     */
    public function updateQuery(): mixed;

    /**
     * Create DELETE query builder.
     *
     * @return mixed Query builder instance
     */
    public function deleteQuery(): mixed;
}

/**
 * ORM Interface.
 *
 * For database managers that support full ORM features.
 */
interface OrmInterface
{
    /**
     * Get entity manager or equivalent.
     *
     * @return mixed Entity manager instance
     */
    public function getEntityManager(): mixed;

    /**
     * Find entity by ID.
     *
     * @template T of object
     *
     * @param class-string<T> $entityClass
     *
     * @return T|null
     */
    public function find(string $entityClass, mixed $id): ?object;

    /**
     * Persist entity to database.
     */
    public function persist(object $entity): void;

    /**
     * Remove entity from database.
     */
    public function remove(object $entity): void;

    /**
     * Flush changes to database.
     */
    public function flush(): void;

    /**
     * Create repository for entity.
     *
     * @template T
     *
     * @param class-string<T> $entityClass
     *
     * @return mixed Repository instance
     */
    public function getRepository(string $entityClass): mixed;
}

/**
 * Migration Interface.
 *
 * For database managers that support migrations.
 */
interface MigrationInterface
{
    /**
     * Run pending migrations.
     */
    public function migrate(): void;

    /**
     * Rollback last migration.
     */
    public function rollback(): void;

    /**
     * Get migration status.
     *
     * @return array<string, mixed>
     */
    public function getMigrationStatus(): array;

    /**
     * Create new migration file.
     */
    public function createMigration(string $name): string;
}

/**
 * Combined Database Manager Interface.
 *
 * For managers that support multiple features.
 */
interface FullDatabaseManagerInterface extends
    DatabaseManagerInterface,
    QueryBuilderInterface,
    OrmInterface,
    MigrationInterface
{
    // This interface combines all features
    // Doctrine would implement this
    // CakePHP Database would implement DatabaseManagerInterface + QueryBuilderInterface
}
