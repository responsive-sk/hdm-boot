---
title: "Multi-Database Architecture"
slug: "multi-database-architecture"
category: "architecture"
order: 6
description: "Learn about MVA Bootstrap's multi-database approach for optimal performance and security"
difficulty: "advanced"
estimated_time: "15 minutes"
tags: ["database", "architecture", "sqlite", "performance", "security"]
author: "MVA Bootstrap Team"
---

# Multi-Database Architecture

MVA Bootstrap implements a **multi-database architecture** that separates SQLite databases by purpose to eliminate read/write conflicts, improve performance, and enhance security isolation.

## 🎯 Philosophy

Instead of using a single SQLite database for everything, we separate databases by **functional purpose**:

- **app.db** - Main application data (users, sessions)
- **mark.db** - Mark admin system (admin users, audit logs)
- **cache.db** - Performance optimization (cache, temp data)
- **analytics.db** - Reporting and metrics (page views, engagement)

## 🏗️ Architecture Overview

```
content/                # Git-friendly content
├── articles/           # File storage (Markdown)
└── docs/              # File storage (Markdown)

var/orbit/             # Runtime databases (gitignored)
├── app.db            # Main application database
├── mark.db           # Mark admin database
├── cache.db          # Cache and temporary data
└── analytics.db      # Analytics and reporting
```

## 🗄️ Database Purposes

### **app.db** - Main Application
**Purpose**: Core application functionality

**Tables**:
- `users` - Application users and authentication
- `user_sessions` - User session management
- `user_preferences` - User settings and preferences
- `user_activity_logs` - User activity tracking
- `notifications` - User notifications

**Use Cases**:
- User registration and login
- Session management
- User preferences
- Application notifications

### **mark.db** - Mark Admin System
**Purpose**: Administrative operations and security

**Tables**:
- `mark_users` - Admin users for Mark system
- `mark_sessions` - Admin session management
- `mark_settings` - Admin configuration settings
- `mark_audit_logs` - Admin action audit trail
- `content_revisions` - Article revision history
- `publishing_queue` - Scheduled content publishing

**Use Cases**:
- Admin authentication
- Content management
- System administration
- Audit trail tracking

### **cache.db** - Performance Optimization
**Purpose**: Caching and temporary data

**Tables**:
- `file_metadata_cache` - File modification times and metadata
- `query_result_cache` - Cached query results
- `search_index_cache` - Search index data
- `temp_uploads` - Temporary file uploads
- `background_jobs` - Background job queue
- `system_metrics` - Performance and system metrics

**Use Cases**:
- File system caching
- Query result caching
- Search optimization
- Background processing

### **analytics.db** - Reporting and Metrics
**Purpose**: Analytics and business intelligence

**Tables**:
- `page_views` - Article and page view tracking
- `user_engagement` - User interaction metrics
- `search_queries` - Search query analytics
- `performance_logs` - Application performance data
- `error_logs` - Application error tracking

**Use Cases**:
- Traffic analytics
- User behavior tracking
- Performance monitoring
- Error reporting

## 🚀 Implementation

### **Database Manager Setup**

```php
use MvaBootstrap\Modules\Core\Storage\Services\DatabaseManager;

// Initialize multi-database system (Orbit-style)
// Databases will be stored in var/orbit/ automatically
DatabaseManager::initialize('/path/to/content');

// Create all database tables
foreach (['app', 'mark', 'cache', 'analytics'] as $db) {
    DatabaseManager::createTables($db);
}
```

### **Model Configuration**

```php
// App User (app.db)
class AppUser extends DatabaseModel 
{
    protected static string $database = 'app';
    protected static string $table = 'users';
}

// Mark Admin (mark.db)
class MarkUser extends DatabaseModel 
{
    protected static string $database = 'mark';
    protected static string $table = 'mark_users';
}

// Cache Entry (cache.db)
class CacheEntry extends DatabaseModel 
{
    protected static string $database = 'cache';
    protected static string $table = 'query_result_cache';
}
```

### **Usage Examples**

```php
// Create app user
$user = AppUser::create([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password_hash' => password_hash('secret', PASSWORD_DEFAULT)
]);

// Create mark admin
$admin = MarkUser::create([
    'username' => 'admin',
    'email' => 'admin@example.com',
    'role' => 'super_admin'
]);

// Log admin action
$admin->logAction('article.create', 'article', 'new-post', [
    'title' => 'New Post',
    'action_type' => 'create'
]);

// Cache query result
CacheEntry::create([
    'cache_key' => 'popular_articles',
    'result_data' => json_encode($articles),
    'expires_at' => time() + 3600
]);
```

## 🔧 Database Manager API

### **Connection Management**

```php
// Get specific database connection
$appPdo = DatabaseManager::getConnection('app');
$markPdo = DatabaseManager::getConnection('mark');

// Get database health status
$health = DatabaseManager::getHealthStatus();
foreach ($health as $dbName => $status) {
    echo "{$dbName}: " . ($status['connected'] ? 'OK' : 'ERROR') . "\n";
}
```

### **Database Operations**

```php
// Get database path
$path = DatabaseManager::getDatabasePath('app');

// Get all registered databases
$databases = DatabaseManager::getDatabases();

// Close all connections
DatabaseManager::closeAll();
```

## 📊 Benefits

### **Performance Benefits**
- ✅ **No Lock Conflicts** - Each database handles different operations
- ✅ **Parallel Access** - Multiple databases can be accessed simultaneously
- ✅ **Optimized Queries** - Each database optimized for its purpose
- ✅ **Reduced Contention** - No competition for single database file

### **Security Benefits**
- ✅ **Data Isolation** - Admin data separated from user data
- ✅ **Access Control** - Different permissions for different databases
- ✅ **Audit Trail** - Complete admin action logging in separate database
- ✅ **Breach Containment** - Compromise of one database doesn't affect others

### **Maintenance Benefits**
- ✅ **Selective Backup** - Backup only necessary databases
- ✅ **Independent Scaling** - Scale each database based on usage
- ✅ **Easier Debugging** - Issues isolated to specific database
- ✅ **Clear Separation** - Logical separation of concerns

## 🔍 Monitoring and Health Checks

### **Health Check Example**

```php
$health = DatabaseManager::getHealthStatus();

foreach ($health as $dbName => $status) {
    echo "Database: {$dbName}\n";
    echo "  Status: " . ($status['connected'] ? 'Connected' : 'Disconnected') . "\n";
    echo "  Size: " . number_format($status['size']) . " bytes\n";
    echo "  Tables: {$status['table_count']}\n";
    echo "  SQLite Version: {$status['sqlite_version']}\n\n";
}
```

### **Performance Monitoring**

```php
// Log performance metrics
PerformanceLog::create([
    'database' => 'app',
    'operation' => 'user_login',
    'duration_ms' => 45,
    'query_count' => 3
]);

// Monitor database sizes
foreach (['app', 'mark', 'cache', 'analytics'] as $db) {
    $path = DatabaseManager::getDatabasePath($db);
    $size = file_exists($path) ? filesize($path) : 0;
    echo "{$db}.db: " . number_format($size) . " bytes\n";
}
```

## 🛠️ Advanced Configuration

### **Custom Database Registration**

```php
// Register custom database
DatabaseManager::registerDatabase('custom', [
    'filename' => 'custom.db',
    'description' => 'Custom application database',
    'tables' => [
        'custom_data' => 'Custom data storage',
        'custom_logs' => 'Custom logging'
    ]
]);

// Create tables for custom database
DatabaseManager::createTables('custom');
```

### **Performance Optimization**

```php
// SQLite optimizations are automatically applied:
// - WAL mode for better concurrency
// - NORMAL synchronous mode for performance
// - Memory temp store for speed
// - Optimized cache size
// - Foreign key constraints enabled
```

## 🔮 Future Enhancements

### **Planned Features**:
- **Database Sharding** - Split large databases across multiple files
- **Read Replicas** - Separate read/write databases for scaling
- **Automatic Backup** - Scheduled database backups
- **Migration System** - Database schema versioning
- **Connection Pooling** - Optimize connection management
- **Cross-Database Queries** - JOIN operations across databases

### **Monitoring Improvements**:
- **Real-time Metrics** - Live database performance monitoring
- **Alert System** - Notifications for database issues
- **Query Analysis** - Slow query detection and optimization
- **Capacity Planning** - Automatic database size monitoring

## 📚 Related Documentation

- [Hybrid Storage System](hybrid-storage.md)
- [Database Models Guide](database-models.md)
- [Performance Optimization](performance.md)
- [Security Best Practices](security.md)

---

The multi-database architecture provides optimal performance, security, and maintainability by separating concerns into purpose-built databases while maintaining a unified development experience.
