# 🏛️ HDM Boot Core Architecture Principles

**THESE PRINCIPLES ARE SET IN STONE - DO NOT DEVIATE**

## 🚫 FORBIDDEN TERMS

### **"ADMIN" IS BANNED**
- ❌ **NEVER use "admin" in code, database, or documentation**
- ✅ **ALWAYS use "MARK" for administrative functionality**
- 🎯 **Reason:** Clear separation between user management and mark management

**Examples:**
```php
// ❌ WRONG
$adminUser = new AdminUser();
$adminService = new AdminService();
$adminDatabase = 'admin.db';

// ✅ CORRECT  
$markUser = new MarkUser();
$markService = new MarkService();
$markDatabase = 'mark.db';
```

## 🗄️ THREE DATABASE ARCHITECTURE

### **MANDATORY DATABASE SEPARATION**

#### **1. 🔴 MARK DATABASE (mark.db)**
**Purpose:** Mark system management and administration

**Tables:**
```sql
-- Mark users (administrative users)
mark_users (
    id TEXT PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL, 
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'mark_admin',
    status TEXT DEFAULT 'active',
    last_login_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)

-- Mark sessions
mark_sessions (
    session_id TEXT PRIMARY KEY,
    mark_user_id TEXT NOT NULL,
    session_data TEXT,
    expires_at TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (mark_user_id) REFERENCES mark_users(id)
)

-- Mark audit logs
mark_audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mark_user_id TEXT,
    action TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (mark_user_id) REFERENCES mark_users(id)
)

-- Mark settings
mark_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

#### **2. 🔵 USER DATABASE (user.db)**
**Purpose:** Application user management

**Tables:**
```sql
-- Application users
users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    status TEXT DEFAULT 'active',
    email_verified INTEGER DEFAULT 0,
    last_login_at TEXT,
    login_count INTEGER DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)

-- User sessions
user_sessions (
    session_id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    session_data TEXT,
    expires_at TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)

-- User preferences
user_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    preference_key TEXT NOT NULL,
    preference_value TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, preference_key)
)

-- User activity logs
user_activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT,
    action TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)
```

#### **3. 🟢 APP CORE DATABASE (app.db)**
**Purpose:** Core application modules and shared data

**Tables:**
```sql
-- Blog articles
blog_articles (
    id TEXT PRIMARY KEY,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    author_id TEXT,
    category TEXT,
    tags TEXT, -- JSON array
    status TEXT DEFAULT 'draft',
    published_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)

-- Application cache
app_cache (
    cache_key TEXT PRIMARY KEY,
    cache_value TEXT,
    expires_at INTEGER,
    created_at TEXT NOT NULL
)

-- System logs
system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,
    message TEXT NOT NULL,
    context TEXT, -- JSON
    channel TEXT,
    created_at TEXT NOT NULL
)

-- File metadata cache
file_metadata_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    file_path TEXT UNIQUE NOT NULL,
    modified_time INTEGER,
    file_size INTEGER,
    checksum TEXT,
    metadata TEXT, -- JSON
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

## 🔐 DATABASE ACCESS RULES

### **STRICT SEPARATION**
1. **Mark modules** → ONLY access `mark.db`
2. **User modules** → ONLY access `user.db`  
3. **Core modules** → ONLY access `app.db`
4. **NO cross-database queries** without explicit service layer

### **CONNECTION MANAGEMENT**
```php
// ✅ CORRECT - Separate connections
$markConnection = DatabaseManager::getConnection('mark');
$userConnection = DatabaseManager::getConnection('user');
$appConnection = DatabaseManager::getConnection('app');

// ❌ WRONG - Single connection for everything
$connection = DatabaseManager::getConnection('default');
```

## 🏗️ MODULE ARCHITECTURE

### **MARK MODULES**
- `src/Modules/Mark/` - All mark-related functionality
- Mark authentication, mark users, mark settings
- **NEVER** interact with user.db or app.db directly

### **USER MODULES**  
- `src/Modules/Core/User/` - User management
- User authentication, user preferences, user activity
- **NEVER** interact with mark.db or app.db directly

### **CORE MODULES**
- `src/Modules/Core/` - Shared application functionality
- Blog, Template, Storage, Session, etc.
- **NEVER** interact with mark.db or user.db directly

## 🔐 PERMISSION MANAGEMENT PRINCIPLES

### **CENTRALIZED PERMISSION SYSTEM**
All file/directory permissions are managed by **PermissionManager** - NO exceptions.

**Location:** `src/SharedKernel/System/PermissionManager.php`

### **STRICT PERMISSIONS BY DEFAULT**
- ✅ **Directories: 755** (rwxr-xr-x) - production-safe
- ✅ **Files: 644** (rw-r--r--) - production-safe
- ✅ **Logs: 666** (rw-rw-rw-) - need write access
- ✅ **Cache: 755/644** - standard permissions

### **SHARED HOSTING SUPPORT**
- 🏠 **Relaxed mode: 777/666** - for shared hosting environments
- 🔧 **Environment variable: PERMISSIONS_STRICT=false**
- 📜 **Scripts support both modes**

### **SECURE PATH RESOLUTION**
All paths MUST use **ResponsiveSk\Slim4Paths** service:

```php
// ✅ CORRECT - Secure path resolution
$securePath = $this->paths->path('storage/database.db');

// ❌ WRONG - Direct path manipulation
$path = '../storage/database.db';
```

### **PERMISSION TOOLS**
1. **Bash script:** `bin/fix-permissions.sh [shared]`
2. **PHP script:** `bin/fix-permissions.php [shared]`
3. **PermissionManager:** Programmatic access

## 🏗️ DATABASE ARCHITECTURE PRINCIPLES

### **ABSTRACT DATABASE MANAGERS**
All database managers MUST extend **AbstractDatabaseManager**:

```php
// ✅ CORRECT - Proper inheritance
class MarkSqliteDatabaseManager extends AbstractDatabaseManager
{
    protected function createConnection(): PDO { /* SQLite implementation */ }
    protected function initializeDatabase(): void { /* Mark schema */ }
}

// ❌ WRONG - Direct implementation
class MarkDatabaseManager
{
    private PDO $connection; // No inheritance, no standards
}
```

### **DATABASE MANAGER FACTORY**
Use **DatabaseManagerFactory** with Paths injection:

```php
// ✅ CORRECT - Factory with secure paths
$factory = new DatabaseManagerFactory($paths);
$markManager = $factory->createMarkManager();

// ❌ WRONG - Direct instantiation
$markManager = new MarkDatabaseManager('storage/mark.db');
```

### **SECURE DATABASE PATHS**
All database paths are resolved securely:

```php
// ✅ AUTOMATIC - Handled by AbstractDatabaseManager
$this->secureDatabasePath = $this->resolveDatabasePath($databasePath);

// ✅ PATH TRAVERSAL PROTECTION - Built-in
// No ../../../etc/passwd attacks possible
```

## 🚨 VIOLATION CONSEQUENCES

### **IMMEDIATE REJECTION**
Any code that violates these principles will be **immediately rejected**:

1. ❌ Using "admin" terminology
2. ❌ Cross-database access without service layer
3. ❌ Mixing mark/user/app data in single database
4. ❌ Single database for multiple concerns
5. ❌ Direct path manipulation without Paths service
6. ❌ Manual permission setting outside PermissionManager
7. ❌ Database managers not extending AbstractDatabaseManager

### **REFACTORING REQUIRED**
If existing code violates these principles, it **MUST** be refactored:

1. 🔄 Rename all "admin" references to "mark"
2. 🔄 Separate databases according to architecture
3. 🔄 Create proper service layer for cross-database operations
4. 🔄 Update all documentation and comments
5. 🔄 Replace direct paths with Paths service
6. 🔄 Use PermissionManager for all permission operations
7. 🔄 Extend AbstractDatabaseManager for all database managers

## 📋 IMPLEMENTATION CHECKLIST

### **Database Setup**
- [ ] Create `storage/mark.db` with mark tables
- [ ] Create `storage/user.db` with user tables
- [ ] Create `storage/app.db` with core tables
- [ ] Configure separate PDO connections via DatabaseManagerFactory
- [ ] Test database isolation
- [ ] Verify secure path resolution

### **Permission Management Setup**
- [ ] Initialize PermissionManager in DI container
- [ ] Run `bin/fix-permissions.php` for system setup
- [ ] Configure PERMISSIONS_STRICT environment variable
- [ ] Test both strict and relaxed permission modes
- [ ] Verify log file write permissions

### **Code Refactoring**
- [ ] Rename all "admin" to "mark" in codebase
- [ ] Update database managers to extend AbstractDatabaseManager
- [ ] Replace direct paths with Paths service
- [ ] Use PermissionManager for all permission operations
- [ ] Separate repository implementations
- [ ] Update service layer
- [ ] Fix authentication flows

### **Documentation**
- [ ] Update all documentation
- [ ] Create database migration guides
- [ ] Document permission management procedures
- [ ] Document service layer APIs
- [ ] Update deployment instructions
- [ ] Create shared hosting deployment guide

## 🎯 SUCCESS CRITERIA

### **ARCHITECTURE COMPLIANCE**
1. ✅ Zero "admin" references in codebase
2. ✅ Three separate SQLite databases (mark.db, user.db, app.db)
3. ✅ Proper database access isolation via AbstractDatabaseManager
4. ✅ Clear module boundaries (Mark/, Core/User/, Core/)
5. ✅ Service layer for cross-database operations
6. ✅ Secure path resolution via Paths service
7. ✅ Centralized permission management via PermissionManager

### **SECURITY COMPLIANCE**
1. ✅ No path traversal vulnerabilities (Paths service)
2. ✅ Strict file permissions by default (755/644)
3. ✅ Secure database path resolution
4. ✅ No direct file system access outside PermissionManager
5. ✅ Environment-specific permission modes

### **PRODUCTION READINESS**
1. ✅ Mark users can authenticate independently
2. ✅ App users can authenticate independently
3. ✅ Core modules function without user/mark data
4. ✅ Database migrations work correctly
5. ✅ Backup/restore procedures documented
6. ✅ Permission management tools available
7. ✅ Shared hosting compatibility mode

## 🛠️ DEVELOPMENT TOOLS

### **Permission Management Tools**
```bash
# Production setup (strict permissions)
./bin/fix-permissions.sh
php bin/fix-permissions.php

# Shared hosting setup (relaxed permissions)
./bin/fix-permissions.sh shared
php bin/fix-permissions.php shared
```

### **Database Management Tools**
```php
// Create database managers with secure paths
$factory = new DatabaseManagerFactory($paths);
$markManager = $factory->createMarkManager();
$userManager = $factory->createUserManager();

// Check database health
$health = $factory->checkAllDatabasesHealth();

// Initialize all databases
$factory->initializeAllDatabases();
```

### **Environment Configuration**
```env
# Strict permissions (production)
PERMISSIONS_STRICT=true

# Relaxed permissions (shared hosting)
PERMISSIONS_STRICT=false

# Database paths (auto-resolved securely)
MARK_DATABASE_PATH=storage/mark.db
USER_DATABASE_PATH=storage/user.db
APP_DATABASE_PATH=storage/app.db
```

---

**THESE PRINCIPLES ARE SET IN STONE**
**ANY DEVIATION REQUIRES ARCHITECTURAL REVIEW**
**DOCUMENT VERSION:** 2.0
**LAST UPDATED:** 2025-06-24
**MAJOR ADDITIONS:** Permission Management, Database Architecture, Secure Paths
