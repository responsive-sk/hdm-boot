# Three-Database Architecture Documentation

## Overview

HDM Boot Protocol v2.1.0 implements a three-database architecture for improved security, performance, and maintainability. This replaces the previous single-database approach.

**Latest Update (2025-06-28)**: Database module refactored to use PDO-only implementation. CakePHP support has been disabled and moved to backup directory.

## Architecture Diagram

```
HDM Boot Protocol - Three-Database Architecture

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mark System   │    │  User System    │    │ System Services │
│                 │    │                 │    │                 │
│ /mark routes    │    │ /login routes   │    │ Cache, Logs     │
│ Mark admins     │    │ App users       │    │ System data     │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  storage/       │    │  storage/       │    │  storage/       │
│  mark.db        │    │  user.db        │    │  system.db      │
│                 │    │                 │    │                 │
│ • mark_users    │    │ • users         │    │ • system_cache  │
│                 │    │                 │    │ • system_logs   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Database Specifications

### 1. Mark Database (storage/mark.db)

**Purpose**: Super user management and administration

**Tables**:
```sql
CREATE TABLE mark_users (
    id TEXT PRIMARY KEY,
    username TEXT,
    email TEXT UNIQUE,
    password_hash TEXT,
    role TEXT,
    status TEXT,
    last_login_at TEXT,
    login_count INTEGER,
    created_at TEXT,
    updated_at TEXT
);
```

**Access**:
- **Routes**: `/mark/*`
- **Manager**: `MarkSqliteDatabaseManager`
- **Repository**: `SqliteMarkRepository`
- **Service**: `MarkAuthenticationService`

**Default Users**:
- mark@responsive.sk / mark123 (mark_admin)
- admin@example.com / admin123 (mark_admin)

### 2. User Database (storage/user.db)

**Purpose**: Application user management

**Tables**:
```sql
CREATE TABLE users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE,
    name TEXT,
    password_hash TEXT,
    role TEXT,
    status TEXT,
    email_verified INTEGER,
    created_at TEXT,
    updated_at TEXT
);
```

**Access**:
- **Routes**: `/login`, `/register`, `/profile/*`
- **Manager**: `UserSqliteDatabaseManager`
- **Repository**: `SqliteUserRepository`
- **Service**: `UserService`

**Default Users**:
- test@example.com / password123 (user)
- user@example.com / user123 (user)

### 3. System Database (storage/system.db)

**Purpose**: System services and infrastructure

**Tables**:
```sql
CREATE TABLE system_cache (
    cache_key TEXT PRIMARY KEY,
    cache_value TEXT,
    expires_at INTEGER,
    created_at TEXT
);

CREATE TABLE system_logs (
    id INTEGER PRIMARY KEY,
    level TEXT,
    message TEXT,
    context TEXT,
    channel TEXT,
    created_at TEXT
);
```

**Access**:
- **Manager**: `SystemSqliteDatabaseManager`
- **Services**: Cache, Logging, System monitoring

## Database Managers

### DatabaseManagerFactory

**File**: `src/SharedKernel/Database/DatabaseManagerFactory.php`

```php
class DatabaseManagerFactory
{
    public function createMarkManager(): MarkSqliteDatabaseManager
    {
        return new MarkSqliteDatabaseManager(
            $this->paths,
            'storage/mark.db'
        );
    }

    public function createUserManager(): UserSqliteDatabaseManager
    {
        return new UserSqliteDatabaseManager(
            $this->paths,
            'storage/user.db'
        );
    }

    public function createSystemManager(): SystemSqliteDatabaseManager
    {
        return new SystemSqliteDatabaseManager(
            $this->paths,
            'storage/system.db'
        );
    }
}
```

### AbstractDatabaseManager

**File**: `src/SharedKernel/Database/AbstractDatabaseManager.php`

**Features**:
- Secure path resolution
- Auto-creation of databases and directories
- Permission handling (777/666 for shared hosting)
- Connection pooling and management
- Debug error reporting

### Individual Database Managers

1. **MarkSqliteDatabaseManager**
   - Manages mark.db connection
   - Creates mark_users table
   - Handles mark user initialization

2. **UserSqliteDatabaseManager**
   - Manages user.db connection
   - Creates users table
   - Handles user data operations

3. **SystemSqliteDatabaseManager**
   - Manages system.db connection
   - Creates system tables (cache, logs)
   - Handles system data operations

## Configuration

### Paths Configuration

**File**: `config/paths.php`

```php
'storage' => $basePath . '/storage',  // Root storage directory
'logs'    => $basePath . '/var/logs', // Runtime logs
'cache'   => $basePath . '/var/cache', // Runtime cache
```

### Module Configurations

Each module binds to its specific database manager:

```php
// Mark Module
MarkRepositoryInterface::class => function (Container $container) {
    $factory = new DatabaseManagerFactory($paths);
    $markManager = $factory->createMarkManager();
    return new SqliteMarkRepository($markManager->getConnection());
},

// User Module
UserRepositoryInterface::class => function (Container $container) {
    $factory = new DatabaseManagerFactory($paths);
    $userManager = $factory->createUserManager();
    return new SqliteUserRepository($userManager->getConnection());
},
```

## Security Benefits

### 1. Data Isolation
- Mark users completely separated from app users
- System data isolated from user data
- Prevents data leakage between systems

### 2. Access Control
- Different authentication flows
- Separate permission systems
- Route-based access control

### 3. Attack Surface Reduction
- Compromise of one database doesn't affect others
- Different connection strings and credentials
- Isolated backup and recovery

## Performance Benefits

### 1. Optimized Queries
- Smaller tables for faster queries
- Specialized indexes per database
- Reduced lock contention

### 2. Scalability
- Independent scaling of databases
- Separate backup schedules
- Database-specific optimizations

### 3. Maintenance
- Independent maintenance windows
- Targeted performance tuning
- Easier troubleshooting

## Migration Guide

### From Single Database (app.db)

1. **Backup Current Data**:
   ```bash
   cp var/storage/app.db backup/app.db.backup
   ```

2. **Export User Data**:
   ```sql
   -- Export mark users
   SELECT * FROM users WHERE role = 'admin';
   
   -- Export regular users
   SELECT * FROM users WHERE role != 'admin';
   ```

3. **Deploy New Architecture**:
   - Upload new production package
   - Verify three databases are created
   - Check file permissions

4. **Import Data**:
   - Import mark users to mark.db
   - Import regular users to user.db
   - Migrate system data to system.db

5. **Update Workflows**:
   - Direct mark admins to `/mark` route
   - Update documentation and training
   - Test both authentication systems

### Breaking Changes

- **Database Structure**: Complete change from single to three databases
- **Storage Location**: Moved from `var/storage/` to `storage/`
- **Authentication Routes**: Mark admins use `/mark`, users use `/login`
- **Password Hashing**: Changed from Argon2ID to Bcrypt

## Deployment

### Production Deployment

1. **Package Preparation**:
   - Build production package with three databases
   - Set proper permissions (777/666)
   - Include initialization scripts

2. **Server Deployment**:
   ```bash
   # Upload via FTP/FileZilla
   # Verify file structure
   storage/
   ├── mark.db (666)
   ├── user.db (666)
   └── system.db (666)
   ```

3. **Verification**:
   - Test mark login: `/mark`
   - Test user login: `/login`
   - Check database connections
   - Verify permissions

### Shared Hosting Considerations

- **Auto-Creation**: Databases auto-create with proper permissions
- **Path Resolution**: Uses absolute paths for reliability
- **Error Handling**: Comprehensive error reporting
- **Permission Management**: Automatic 777/666 permission setting

## Troubleshooting

### Common Issues

1. **Database Connection Failed**:
   - Check file permissions (666 for .db files, 777 for directories)
   - Verify storage/ directory exists
   - Check absolute path resolution

2. **Authentication Issues**:
   - Verify users exist in correct database
   - Check password hashing format (bcrypt)
   - Verify user status is 'active'

3. **Performance Issues**:
   - Check database file sizes
   - Verify proper indexing
   - Monitor connection pooling

### Debug Information

Database managers provide detailed debug information:

```
Database error: Failed to connect to user database
Path: /full/path/to/storage/user.db
Dir exists: yes
File exists: no
Permissions: 755
```

## Best Practices

### 1. Database Management
- Regular backups of all three databases
- Monitor database sizes and performance
- Use proper indexing strategies

### 2. Security
- Separate credentials for each database (if applicable)
- Regular permission audits
- Monitor access logs

### 3. Development
- Use appropriate database manager for each module
- Follow repository pattern for data access
- Implement proper error handling

### 4. Deployment
- Test all three databases after deployment
- Verify authentication flows work
- Check file permissions on shared hosting

## Future Considerations

### Potential Enhancements

1. **Database Encryption**: Encrypt sensitive databases
2. **Replication**: Implement database replication for high availability
3. **Sharding**: Shard user database for large-scale deployments
4. **Monitoring**: Enhanced database monitoring and alerting

### Scalability Options

1. **Separate Servers**: Move databases to separate servers
2. **Read Replicas**: Implement read replicas for performance
3. **Connection Pooling**: Advanced connection pooling strategies
4. **Caching**: Database-specific caching strategies

## Database Module Refactoring (2025-06-28)

### Changes Made

The Database module was refactored to use **PDO-only implementation**:

1. **Removed CakePHP Support**:
   - `CakePHPDatabaseManager.php` → moved to `_disabled_cakephp/`
   - `DatabaseConnectionManager.php` → moved to `_disabled_cakephp/`
   - Updated configuration to remove CakePHP services

2. **Simplified Architecture**:
   - Single database abstraction layer (PDO)
   - Cleaner service definitions
   - Reduced complexity and dependencies

3. **Maintained Functionality**:
   - All existing PDO-based operations work unchanged
   - Three-database architecture preserved
   - Repository pattern maintained

### Current Implementation

**Active Database Managers**:
- `DatabaseManager.php` - Main PDO implementation
- `MarkSqliteDatabaseManager.php` - Mark database
- `UserSqliteDatabaseManager.php` - User database
- `SystemSqliteDatabaseManager.php` - System database

**Supported Repository Types**:
- `sqlite` - SQLite databases (primary)
- `mysql` - MySQL databases via PDO

### Benefits

- **Performance**: Native PDO without additional abstraction layers
- **Simplicity**: Single approach reduces complexity
- **Maintainability**: Less code to maintain and debug
- **Type Safety**: All code passes PHPStan level 8

### Future CakePHP Support

If CakePHP support is needed:
1. Create separate `Database-CakePHP` module
2. Move files from `_disabled_cakephp/` backup
3. Implement proper module dependencies

For detailed refactoring information, see: `docs/refactoring/database-module-refactoring.md`
