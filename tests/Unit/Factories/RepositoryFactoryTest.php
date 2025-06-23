<?php

declare(strict_types=1);

namespace HdmBoot\Tests\Unit\Factories;

use HdmBoot\Modules\Core\User\Repository\SqliteUserRepository;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\Database\Infrastructure\Factories\RepositoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test Repository Factory - Properly Abstract Pattern.
 */
#[CoversClass(RepositoryFactory::class)]
final class RepositoryFactoryTest extends TestCase
{
    private RepositoryFactory $factory;
    private \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create in-memory SQLite for testing
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create factory with SQLite type
        $this->factory = new RepositoryFactory('sqlite', 'pdo');
    }

    #[Test]
    public function factoryCreatesCorrectRepositoryType(): void
    {
        $repository = $this->factory->createUserRepository($this->pdo);
        
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository);
        $this->assertInstanceOf(SqliteUserRepository::class, $repository);
    }

    #[Test]
    public function factoryReturnsCorrectRepositoryType(): void
    {
        $this->assertSame('sqlite', $this->factory->getRepositoryType());
    }

    #[Test]
    public function factoryReturnsCorrectDatabaseManager(): void
    {
        $this->assertSame('pdo', $this->factory->getDatabaseManager());
    }

    #[Test]
    public function factoryReturnsSupportedTypes(): void
    {
        $supportedTypes = $this->factory->getSupportedTypes();
        
        $this->assertIsArray($supportedTypes);
        $this->assertContains('sqlite', $supportedTypes);
        $this->assertContains('mysql', $supportedTypes);
        $this->assertContains('doctrine', $supportedTypes);
        $this->assertContains('cycle', $supportedTypes);
        $this->assertContains('cake', $supportedTypes);
    }

    #[Test]
    public function factoryChecksIfTypeIsSupported(): void
    {
        $this->assertTrue($this->factory->isTypeSupported('sqlite'));
        $this->assertTrue($this->factory->isTypeSupported('doctrine'));
        $this->assertFalse($this->factory->isTypeSupported('invalid'));
    }

    #[Test]
    public function factoryThrowsExceptionForUnsupportedType(): void
    {
        $factory = new RepositoryFactory('invalid', 'pdo');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported repository type: invalid');
        
        $factory->createUserRepository($this->pdo);
    }

    #[Test]
    public function factoryRequiresPdoForSqliteRepository(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PDO is required for SQLite repository');
        
        $this->factory->createUserRepository();
    }

    #[Test]
    public function factoryThrowsExceptionForUnimplementedTypes(): void
    {
        $factory = new RepositoryFactory('doctrine', 'doctrine');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Doctrine User Repository not implemented yet');
        
        $factory->createUserRepository(null, $this->createMock(\HdmBoot\Modules\Core\Database\Domain\Contracts\DatabaseManagerInterface::class));
    }

    #[Test]
    public function factoryCanBeConfiguredWithDifferentTypes(): void
    {
        $mysqlFactory = new RepositoryFactory('mysql', 'pdo');
        $this->assertSame('mysql', $mysqlFactory->getRepositoryType());
        
        $doctrineFactory = new RepositoryFactory('doctrine', 'doctrine');
        $this->assertSame('doctrine', $doctrineFactory->getRepositoryType());
        $this->assertSame('doctrine', $doctrineFactory->getDatabaseManager());
    }

    #[Test]
    public function factoryCreatesRepositoryWithProperAbstraction(): void
    {
        // Test that factory properly abstracts repository creation
        $repository = $this->factory->createUserRepository($this->pdo);
        
        // Should be able to use repository through interface
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository);
        
        // Should have all required methods
        $this->assertTrue(method_exists($repository, 'findById'));
        $this->assertTrue(method_exists($repository, 'findByEmail'));
        $this->assertTrue(method_exists($repository, 'save'));
        $this->assertTrue(method_exists($repository, 'delete'));
    }
}
