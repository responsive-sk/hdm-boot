# Database Manager Documentation

## 📋 Overview

The **DatabaseManager** is a core component of the MVA Bootstrap Application that provides secure, efficient, and reliable SQLite database management. It handles database connections, initialization, performance optimization, and provides comprehensive database operations with proper path security.

## 🏗️ Architecture

### Design Principles
- **Security First** - Database files are stored outside the public directory
- **Performance Optimized** - WAL mode, memory caching, and optimized SQLite settings
- **Path Security** - Integration with Paths system for secure file handling
- **Auto-initialization** - Automatic database setup and metadata tracking
- **Error Handling** - Comprehensive error handling and logging

### Integration Points
- **Paths System** - Uses `ResponsiveSk\Slim4Paths\Paths` for secure path resolution
- **DI Container** - Fully integrated with PHP-DI container
- **PDO Integration** - Provides PDO instances for repositories and services
- **Module System** - Each module can extend database schema

## 🔧 Configuration

### Container Registration
```php
// In config/container.php
DatabaseManager::class => function (Container $c): DatabaseManager {
    return new DatabaseManager($c->get(Paths::class));
},

PDO::class => function (Container $c): PDO {
    return $c->get(DatabaseManager::class)->getConnection();
},
```

### Database Location
- **Path**: `{application_root}/var/storage/app.db`
- **Security**: Outside public directory
- **Permissions**: 666 for file, 755 for directory
- **Backup**: Recommended to backup `var/storage/` directory

## 🚀 Usage Examples

### Basic Usage
```php
use MvaBootstrap\Database\DatabaseManager;
use ResponsiveSk\Slim4Paths\Paths;

// Initialize
$paths = new Paths($basePath);
$dbManager = new DatabaseManager($paths);

// Get connection
$pdo = $dbManager->getConnection();

// Execute queries
$stmt = $pdo->prepare('SELECT * FROM users WHERE role = ?');
$stmt->execute(['admin']);
$users = $stmt->fetchAll();
```

### Dependency Injection
```php
// In your service
class UserService
{
    public function __construct(
        private readonly PDO $pdo  // Automatically injected
    ) {}
    
    public function getUsers(): array
    {
        return $this->pdo->query('SELECT * FROM users')->fetchAll();
    }
}
```

### Database Testing
```php
// Test connection
$testResult = $dbManager->testConnection();
if ($testResult['status'] === 'OK') {
    echo "Database is working!";
}

// Get statistics
$stats = $dbManager->getStatistics();
echo "Tables: " . count($stats['tables']);
echo "File size: " . $stats['file_size_mb'] . " MB";
```

## 📊 Features

### 1. **Connection Management**
- **Singleton Pattern** - One connection per request
- **Lazy Loading** - Connection created only when needed
- **Auto-reconnect** - Handles connection failures gracefully
- **Performance Settings** - Optimized SQLite configuration

### 2. **Database Initialization**
- **Auto-creation** - Database file created automatically
- **Metadata Table** - Tracks database version and application info
- **Schema Ready** - Prepared for module table creation
- **Migration Support** - Foundation for future migration system

### 3. **Performance Optimization**
```sql
-- Applied automatically
PRAGMA foreign_keys = ON;        -- Enforce foreign key constraints
PRAGMA journal_mode = WAL;       -- Write-Ahead Logging for better concurrency
PRAGMA synchronous = NORMAL;     -- Balanced performance/safety
PRAGMA cache_size = 10000;       -- 10MB cache
PRAGMA temp_store = MEMORY;      -- Store temp tables in memory
```

### 4. **Statistics & Monitoring**
- **Table Counts** - Number of records per table
- **File Size** - Database file size in bytes and MB
- **Connection Status** - Health check capabilities
- **Performance Metrics** - Query execution monitoring

## 🔒 Security Features

### Path Security
- **Outside Public** - Database stored in `var/storage/`, not accessible via web
- **Path Validation** - Uses Paths system for secure path resolution
- **Directory Permissions** - Proper file system permissions
- **Access Control** - Only application can access database files

### Data Security
- **Foreign Keys** - Enforced referential integrity
- **Prepared Statements** - SQL injection protection (when used properly)
- **Transaction Support** - ACID compliance
- **Backup Ready** - Easy backup and restore procedures

## 📈 Performance Characteristics

### SQLite Optimizations
- **WAL Mode** - Better concurrency, faster writes
- **Memory Cache** - 10MB cache for frequently accessed data
- **Temp Storage** - Temporary tables stored in memory
- **Normal Sync** - Balanced durability vs performance

### Benchmarks
- **Small Database** (< 1MB): Excellent performance
- **Medium Database** (1-100MB): Very good performance
- **Large Database** (100MB+): Good performance, consider optimization

### Scaling Considerations
- **Read Heavy** - Excellent performance with WAL mode
- **Write Heavy** - Good performance, consider connection pooling
- **Concurrent Access** - WAL mode supports multiple readers
- **Migration Path** - Easy to migrate to PostgreSQL/MySQL later

## 🛠️ API Reference

### DatabaseManager Methods

#### `__construct(Paths $paths, string $filename = 'app.db')`
Creates new DatabaseManager instance.

#### `getConnection(): PDO`
Returns database connection (creates if needed).

#### `testConnection(): array`
Tests database connectivity and returns status.

#### `getStatistics(): array`
Returns comprehensive database statistics.

#### `executeRawSql(string $sql): void`
Executes raw SQL (for migrations, schema changes).

#### `getDatabasePath(): string`
Returns full path to database file.

#### `databaseExists(): bool`
Checks if database file exists.

### Response Formats

#### Test Connection Response
```php
[
    'status' => 'OK',                    // OK or ERROR
    'database_file' => '/path/to/db',    // Full database path
    'file_exists' => true,               // File existence
    'file_size' => 73728,                // Size in bytes
    'writable' => true,                  // Directory writable
    'error' => null                      // Error message if failed
]
```

#### Statistics Response
```php
[
    'database_file' => '/path/to/db',
    'file_size_bytes' => 73728,
    'file_size_mb' => 0.07,
    'tables' => [
        'users' => 2,                    // Table name => record count
        'security_login_attempts' => 5,
        '_database_metadata' => 2
    ],
    'total_records' => 9,
    'connection_status' => 'OK'
]
```

## 🔧 Troubleshooting

### Common Issues

#### Database File Not Found
```bash
# Check permissions
ls -la var/storage/
chmod 755 var/storage/
chmod 666 var/storage/app.db
```

#### Connection Errors
```php
// Test connection
$result = $dbManager->testConnection();
if ($result['status'] === 'ERROR') {
    echo "Error: " . $result['error'];
}
```

#### Performance Issues
```sql
-- Check database size
SELECT page_count * page_size as size FROM pragma_page_count(), pragma_page_size();

-- Analyze query performance
EXPLAIN QUERY PLAN SELECT * FROM users WHERE email = ?;

-- Vacuum database (maintenance)
VACUUM;
```

### Debug Mode
```php
// Enable PDO error reporting
$pdo = $dbManager->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

## 🚀 Best Practices

### 1. **Use Prepared Statements**
```php
// ✅ Good
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);

// ❌ Bad
$result = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### 2. **Handle Transactions**
```php
try {
    $pdo->beginTransaction();
    
    // Multiple operations
    $pdo->exec("INSERT INTO users ...");
    $pdo->exec("INSERT INTO profiles ...");
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollback();
    throw $e;
}
```

### 3. **Use Repository Pattern**
```php
class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}
    
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $data = $stmt->fetch();
        
        return $data ? User::fromArray($data) : null;
    }
}
```

### 4. **Monitor Performance**
```php
// Regular statistics check
$stats = $dbManager->getStatistics();
if ($stats['file_size_mb'] > 100) {
    // Consider optimization or archiving
}
```

## 🔄 Migration Path

### To PostgreSQL
```php
// Future: PostgreSQL adapter
class PostgreSqlDatabaseManager implements DatabaseManagerInterface
{
    // Same interface, different implementation
}
```

### To MySQL
```php
// Future: MySQL adapter
class MySqlDatabaseManager implements DatabaseManagerInterface
{
    // Same interface, different implementation
}
```

## 📝 Changelog

### Version 1.0.0 (Current)
- ✅ SQLite database management
- ✅ Path security integration
- ✅ Performance optimization
- ✅ Statistics and monitoring
- ✅ Auto-initialization
- ✅ Container integration

### Planned Features
- 🔄 Migration system
- 🔄 Connection pooling
- 🔄 Query logging
- 🔄 Performance profiling
- 🔄 Database adapters (PostgreSQL, MySQL)
- 🔄 Backup/restore utilities

---

**The DatabaseManager provides a solid, secure, and performant foundation for all database operations in the MVA Bootstrap Application.** 🗄️
