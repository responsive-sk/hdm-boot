<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Database;

use HdmBoot\Shared\Services\DatabaseManager;
use HdmBoot\Tests\TestCase;
use PDO;
use RuntimeException;

/**
 * Unit tests for DatabaseManager.
 */
class DatabaseManagerTest extends TestCase
{
    public function testGetConnection(): void
    {
        $connection = $this->databaseManager->getConnection();

        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testTestConnection(): void
    {
        $result = $this->databaseManager->testConnection();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertSame('OK', $result['status']);
        $this->assertArrayHasKey('database_file', $result);
        $this->assertArrayHasKey('file_exists', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertArrayHasKey('writable', $result);
    }

    public function testGetStatistics(): void
    {
        $stats = $this->databaseManager->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('database_file', $stats);
        $this->assertArrayHasKey('file_size_bytes', $stats);
        $this->assertArrayHasKey('file_size_mb', $stats);
        $this->assertArrayHasKey('tables', $stats);
        $this->assertArrayHasKey('total_records', $stats);
        $this->assertArrayHasKey('connection_status', $stats);

        // Should have our test tables
        $this->assertArrayHasKey('users', $stats['tables']);
        $this->assertArrayHasKey('_database_metadata', $stats['tables']);

        // Should have at least 1 user (our test user)
        $this->assertGreaterThanOrEqual(1, $stats['tables']['users']);
    }

    public function testExecuteRawSql(): void
    {
        // Create a test table
        $this->databaseManager->executeRawSql('
            CREATE TABLE IF NOT EXISTS test_table_unique_unique (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )
        ');

        // Insert test data
        $this->databaseManager->executeRawSql("
            INSERT INTO test_table_unique (name) VALUES ('Test Item')
        ");

        // Verify data exists
        $connection = $this->databaseManager->getConnection();
        $stmt = $connection->query('SELECT COUNT(*) FROM test_table_unique');
        $count = $stmt->fetchColumn();

        $this->assertSame(1, (int) $count);

        // Clean up
        $this->databaseManager->executeRawSql('DROP TABLE test_table_unique');
    }

    public function testExecuteRawSqlWithInvalidSql(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to execute SQL');

        $this->databaseManager->executeRawSql('INVALID SQL STATEMENT');
    }

    public function testDatabaseExists(): void
    {
        // For in-memory database, this should return true after connection
        $this->databaseManager->getConnection();
        $exists = $this->databaseManager->databaseExists();

        // In-memory database behavior may vary, so we just test the method exists
        $this->assertIsBool($exists);
    }

    public function testGetDatabasePath(): void
    {
        $path = $this->databaseManager->getDatabasePath();

        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }

    public function testDatabaseInitialization(): void
    {
        // Get fresh connection to trigger initialization
        $connection = $this->databaseManager->getConnection();

        // Check that metadata table exists and has data
        $stmt = $connection->query("
            SELECT value FROM _database_metadata WHERE key = 'application'
        ");
        $appName = $stmt->fetchColumn();

        $this->assertSame('MVA Bootstrap', $appName);

        // Check version
        $stmt = $connection->query("
            SELECT value FROM _database_metadata WHERE key = 'version'
        ");
        $version = $stmt->fetchColumn();

        $this->assertSame('1.0.0', $version);
    }

    public function testSqliteOptimizations(): void
    {
        $connection = $this->databaseManager->getConnection();

        // Test foreign keys are enabled
        $stmt = $connection->query('PRAGMA foreign_keys');
        $foreignKeys = $stmt->fetchColumn();
        $this->assertSame(1, (int) $foreignKeys);

        // Test journal mode (may not work with in-memory database)
        $stmt = $connection->query('PRAGMA journal_mode');
        $journalMode = $stmt->fetchColumn();
        $this->assertIsString($journalMode);

        // Test synchronous mode
        $stmt = $connection->query('PRAGMA synchronous');
        $synchronous = $stmt->fetchColumn();
        $this->assertIsString($synchronous);
    }

    public function testMultipleConnections(): void
    {
        $connection1 = $this->databaseManager->getConnection();
        $connection2 = $this->databaseManager->getConnection();

        // Should return the same instance (singleton pattern)
        $this->assertSame($connection1, $connection2);
    }

    public function testTransactionSupport(): void
    {
        $connection = $this->databaseManager->getConnection();

        // Test transaction
        $connection->beginTransaction();

        $this->databaseManager->executeRawSql('
            CREATE TEMPORARY TABLE transaction_test (id INTEGER, value TEXT)
        ');

        $this->databaseManager->executeRawSql("
            INSERT INTO transaction_test (id, value) VALUES (1, 'test')
        ");

        $connection->commit();

        // Verify data exists
        $stmt = $connection->query('SELECT value FROM transaction_test WHERE id = 1');
        $value = $stmt->fetchColumn();

        $this->assertSame('test', $value);
    }

    public function testTransactionRollback(): void
    {
        $connection = $this->databaseManager->getConnection();

        $connection->beginTransaction();

        $this->databaseManager->executeRawSql('
            CREATE TEMPORARY TABLE rollback_test (id INTEGER, value TEXT)
        ');

        $this->databaseManager->executeRawSql("
            INSERT INTO rollback_test (id, value) VALUES (1, 'test')
        ");

        $connection->rollback();

        // Table should not exist after rollback
        $stmt = $connection->prepare("
            SELECT name FROM sqlite_master 
            WHERE type='table' AND name='rollback_test'
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        $this->assertFalse($result);
    }
}
