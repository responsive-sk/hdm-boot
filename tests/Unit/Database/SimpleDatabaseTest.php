<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Unit\Database;

use MvaBootstrap\Tests\TestCase;
use PDO;

/**
 * Simple tests for DatabaseManager functionality.
 */
class SimpleDatabaseTest extends TestCase
{
    public function testDatabaseConnection(): void
    {
        $connection = $this->databaseManager->getConnection();
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testDatabaseHasTestUser(): void
    {
        $connection = $this->databaseManager->getConnection();
        $stmt = $connection->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute(['admin@example.com']);
        $count = $stmt->fetchColumn();

        $this->assertSame(1, (int) $count);
    }

    public function testDatabaseMetadata(): void
    {
        $connection = $this->databaseManager->getConnection();
        $stmt = $connection->query("SELECT value FROM _database_metadata WHERE key = 'application'");
        $appName = $stmt->fetchColumn();

        $this->assertSame('MVA Bootstrap', $appName);
    }

    public function testBasicQuery(): void
    {
        $connection = $this->databaseManager->getConnection();
        $stmt = $connection->query('SELECT 1 as test');
        $result = $stmt->fetchColumn();

        $this->assertSame(1, (int) $result);
    }

    public function testTableExists(): void
    {
        $connection = $this->databaseManager->getConnection();
        $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $tableName = $stmt->fetchColumn();

        $this->assertSame('users', $tableName);
    }
}
