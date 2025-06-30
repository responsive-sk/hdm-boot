# Database Integration Guide

Komplexný sprievodca integráciou databázy v HDM Boot aplikácii.

## 🗄️ Database Architecture Overview

HDM Boot používa **Multi-Database Architecture** s týmito databázami:

- **App Database** - Hlavné aplikačné dáta (users, content)
- **Mark Database** - Admin systém a audit logy
- **System Database** - Konfigurácia a cache
- **Analytics Database** - Metriky a reporting (voliteľné)

## 🏗️ Database Structure

```
Database Layer:
┌─────────────────┐    ┌─────────────────┐
│   App Database  │    │  Mark Database  │
│   (app.db)      │    │   (mark.db)     │
│                 │    │                 │
│ • users         │    │ • admin_users   │
│ • articles      │    │ • audit_logs    │
│ • sessions      │    │ • system_config │
└─────────────────┘    └─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│ System Database │    │Analytics Database│
│  (system.db)    │    │ (analytics.db)  │
│                 │    │                 │
│ • cache         │    │ • page_views    │
│ • temp_data     │    │ • user_metrics  │
│ • file_metadata │    │ • performance   │
└─────────────────┘    └─────────────────┘
```

## 🔧 Database Configuration

### Database Service Configuration

```php
<?php
// config/services/database.php

return [
    'connections' => [
        'app' => [
            'driver' => 'sqlite',
            'database' => $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/app.db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        
        'mark' => [
            'driver' => 'sqlite',
            'database' => $_ENV['MARK_DATABASE_URL'] ?? 'sqlite:var/storage/mark.db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        
        'system' => [
            'driver' => 'sqlite',
            'database' => $_ENV['SYSTEM_DATABASE_URL'] ?? 'sqlite:var/storage/system.db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
    
    'default' => 'app',
    
    'migrations' => [
        'table' => 'migrations',
        'path' => 'database/migrations',
    ],
];
```

### Database Manager

```php
<?php
// src/SharedKernel/Infrastructure/Database/DatabaseManager.php

namespace HdmBoot\SharedKernel\Infrastructure\Database;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

final class DatabaseManager
{
    private array $connections = [];
    private array $config;

    public function __construct(
        array $config,
        private readonly LoggerInterface $logger
    ) {
        $this->config = $config;
    }

    public function getConnection(string $name = null): PDO
    {
        $name = $name ?? $this->config['default'];
        
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    public function beginTransaction(string $connection = null): void
    {
        $this->getConnection($connection)->beginTransaction();
    }

    public function commit(string $connection = null): void
    {
        $this->getConnection($connection)->commit();
    }

    public function rollback(string $connection = null): void
    {
        $this->getConnection($connection)->rollback();
    }

    public function transaction(callable $callback, string $connection = null): mixed
    {
        $pdo = $this->getConnection($connection);
        
        $pdo->beginTransaction();
        
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollback();
            
            $this->logger->error('Database transaction failed', [
                'connection' => $connection ?? $this->config['default'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    private function createConnection(string $name): PDO
    {
        if (!isset($this->config['connections'][$name])) {
            throw new \InvalidArgumentException("Database connection '{$name}' not configured");
        }

        $config = $this->config['connections'][$name];
        
        try {
            $dsn = $this->buildDsn($config);
            $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, $config['options'] ?? []);
            
            $this->logger->debug('Database connection established', ['connection' => $name]);
            
            return $pdo;
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'connection' => $name,
                'error' => $e->getMessage(),
            ]);
            
            throw new DatabaseConnectionException("Failed to connect to database '{$name}': " . $e->getMessage(), 0, $e);
        }
    }

    private function buildDsn(array $config): string
    {
        return match ($config['driver']) {
            'sqlite' => $this->buildSqliteDsn($config),
            'mysql' => $this->buildMysqlDsn($config),
            'pgsql' => $this->buildPostgresDsn($config),
            default => throw new \InvalidArgumentException("Unsupported database driver: {$config['driver']}")
        };
    }

    private function buildSqliteDsn(array $config): string
    {
        if (isset($config['database']) && str_starts_with($config['database'], 'sqlite:')) {
            return $config['database'];
        }
        
        return 'sqlite:' . ($config['database'] ?? ':memory:');
    }

    private function buildMysqlDsn(array $config): string
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        if (isset($config['charset'])) {
            $dsn .= ";charset={$config['charset']}";
        }
        
        return $dsn;
    }

    private function buildPostgresDsn(array $config): string
    {
        return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    }
}
```

## 🏛️ Repository Pattern

### Base Repository

```php
<?php
// src/SharedKernel/Infrastructure/Repository/AbstractRepository.php

namespace HdmBoot\SharedKernel\Infrastructure\Repository;

use PDO;
use Psr\Log\LoggerInterface;

abstract class AbstractRepository
{
    protected PDO $pdo;
    protected LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    protected function execute(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->debug('Database query executed', [
                'sql' => $sql,
                'params' => $params,
                'rows_affected' => $stmt->rowCount(),
            ]);
            
            return $stmt;
        } catch (\PDOException $e) {
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);
            
            throw new DatabaseQueryException("Query failed: " . $e->getMessage(), 0, $e);
        }
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn();
    }

    protected function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[":{$key}"] = $value;
        }
        
        $this->execute($sql, $params);
        
        return $this->pdo->lastInsertId();
    }

    protected function update(string $table, array $data, array $where): int
    {
        $setClause = array_map(fn($col) => "{$col} = :{$col}", array_keys($data));
        $whereClause = array_map(fn($col) => "{$col} = :where_{$col}", array_keys($where));
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[":{$key}"] = $value;
        }
        foreach ($where as $key => $value) {
            $params[":where_{$key}"] = $value;
        }
        
        $stmt = $this->execute($sql, $params);
        
        return $stmt->rowCount();
    }

    protected function delete(string $table, array $where): int
    {
        $whereClause = array_map(fn($col) => "{$col} = :{$col}", array_keys($where));
        
        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $table,
            implode(' AND ', $whereClause)
        );
        
        $params = [];
        foreach ($where as $key => $value) {
            $params[":{$key}"] = $value;
        }
        
        $stmt = $this->execute($sql, $params);
        
        return $stmt->rowCount();
    }
}
```

### Domain Repository Implementation

```php
<?php
// src/Modules/Core/User/Infrastructure/Repository/UserRepository.php

namespace HdmBoot\Modules\Core\User\Infrastructure\Repository;

use HdmBoot\SharedKernel\Infrastructure\Repository\AbstractRepository;
use HdmBoot\Modules\Core\User\Domain\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\User\Domain\Entity\User;
use Ramsey\Uuid\UuidInterface;

final class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function save(User $user): void
    {
        $data = [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
            'active' => $user->isActive() ? 1 : 0,
            'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        $existing = $this->findById($user->getId());
        
        if ($existing) {
            $this->update('users', $data, ['id' => $user->getId()->toString()]);
        } else {
            $this->insert('users', $data);
        }
    }

    public function findById(UuidInterface $id): ?User
    {
        $data = $this->fetchOne(
            'SELECT * FROM users WHERE id = :id',
            [':id' => $id->toString()]
        );

        return $data ? $this->hydrate($data) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $data = $this->fetchOne(
            'SELECT * FROM users WHERE email = :email',
            [':email' => $email]
        );

        return $data ? $this->hydrate($data) : null;
    }

    public function findActive(): array
    {
        $results = $this->fetchAll(
            'SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC'
        );

        return array_map([$this, 'hydrate'], $results);
    }

    public function delete(UuidInterface $id): void
    {
        $this->delete('users', ['id' => $id->toString()]);
    }

    public function findWithPagination(int $page, int $limit, array $filters = []): array
    {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['1 = 1'];
        $params = [];

        // Apply filters
        if (!empty($filters['role'])) {
            $whereConditions[] = 'role = :role';
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['active'])) {
            $whereConditions[] = 'active = :active';
            $params[':active'] = $filters['active'] ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = '(name LIKE :search OR email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get total count
        $totalSql = "SELECT COUNT(*) FROM users WHERE {$whereClause}";
        $total = (int) $this->fetchColumn($totalSql, $params);

        // Get paginated results
        $dataSql = "SELECT * FROM users WHERE {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $results = $this->fetchAll($dataSql, $params);
        $users = array_map([$this, 'hydrate'], $results);

        return [
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    private function hydrate(array $data): User
    {
        return User::fromArray([
            'id' => $data['id'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'name' => $data['name'],
            'role' => $data['role'],
            'active' => (bool) $data['active'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at'],
        ]);
    }
}
```

## 🗃️ Database Migrations

### Migration System

```php
<?php
// src/SharedKernel/Infrastructure/Database/Migration/MigrationRunner.php

namespace HdmBoot\SharedKernel\Infrastructure\Database\Migration;

use PDO;
use Psr\Log\LoggerInterface;

final class MigrationRunner
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly LoggerInterface $logger,
        private readonly string $migrationsPath
    ) {}

    public function run(): void
    {
        $this->createMigrationsTable();
        
        $appliedMigrations = $this->getAppliedMigrations();
        $availableMigrations = $this->getAvailableMigrations();
        
        $pendingMigrations = array_diff($availableMigrations, $appliedMigrations);
        
        if (empty($pendingMigrations)) {
            $this->logger->info('No pending migrations');
            return;
        }

        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ';
        
        $this->pdo->exec($sql);
    }

    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM migrations ORDER BY migration');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getAvailableMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.sql');
        }
        
        sort($migrations);
        return $migrations;
    }

    private function runMigration(string $migration): void
    {
        $migrationFile = $this->migrationsPath . '/' . $migration . '.sql';
        
        if (!file_exists($migrationFile)) {
            throw new \RuntimeException("Migration file not found: {$migrationFile}");
        }

        $sql = file_get_contents($migrationFile);
        
        $this->pdo->beginTransaction();
        
        try {
            $this->pdo->exec($sql);
            
            $stmt = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
            $stmt->execute([$migration]);
            
            $this->pdo->commit();
            
            $this->logger->info("Migration applied: {$migration}");
        } catch (\Throwable $e) {
            $this->pdo->rollback();
            
            $this->logger->error("Migration failed: {$migration}", [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
```

### Example Migration

```sql
-- database/migrations/001_create_users_table.sql

CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(active);
```

## 🧪 Database Testing

### Repository Testing

```php
<?php
// tests/Integration/Repository/UserRepositoryTest.php

namespace Tests\Integration\Repository;

use Tests\TestCase;
use HdmBoot\Modules\Core\User\Infrastructure\Repository\UserRepository;
use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = $this->container->get(UserRepository::class);
        $this->setupTestDatabase();
    }

    public function testSaveAndFindUser(): void
    {
        $user = User::create('test@example.com', 'password', 'Test User');
        
        $this->userRepository->save($user);
        
        $foundUser = $this->userRepository->findById($user->getId());
        
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getEmail(), $foundUser->getEmail());
        $this->assertEquals($user->getName(), $foundUser->getName());
    }

    public function testFindByEmail(): void
    {
        $user = User::create('test@example.com', 'password', 'Test User');
        $this->userRepository->save($user);
        
        $foundUser = $this->userRepository->findByEmail('test@example.com');
        
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->getId(), $foundUser->getId());
    }

    public function testPagination(): void
    {
        // Create test users
        for ($i = 1; $i <= 25; $i++) {
            $user = User::create("user{$i}@example.com", 'password', "User {$i}");
            $this->userRepository->save($user);
        }

        $result = $this->userRepository->findWithPagination(1, 10);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['pagination']['total']);
        $this->assertEquals(3, $result['pagination']['pages']);
    }

    private function setupTestDatabase(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
        $this->pdo->exec('
            CREATE TABLE users (
                id VARCHAR(36) PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT "user",
                active BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL
            )
        ');
    }
}
```

## 📋 Database Integration Checklist

### Setup:
- [ ] Database connections nakonfigurované
- [ ] Migration system implementovaný
- [ ] Repository pattern implementovaný
- [ ] Connection pooling nastavený

### Implementation:
- [ ] Repository interfaces definované
- [ ] Repository implementations vytvorené
- [ ] Database transactions implementované
- [ ] Error handling v repositories

### Performance:
- [ ] Database indexy vytvorené
- [ ] Query optimization implementovaná
- [ ] Connection pooling nakonfigurovaný
- [ ] Database monitoring nastavené

### Testing:
- [ ] Repository unit testy
- [ ] Integration testy s databázou
- [ ] Migration testy
- [ ] Performance testy

## 🔗 Ďalšie zdroje

- [Database Architecture](../DATABASE_ARCHITECTURE.md)
- [Module Development](module-development.md)
- [Testing Guide](testing-guide.md)
- [Performance Optimization](../PERFORMANCE.md)
