# MVA Bootstrap Development Plan

**Current Status**: Hybrid Storage System implemented ✅  
**Next Phase**: Multi-Database Architecture + Mark Admin System

## 🎯 Phase 1: Multi-Database Architecture (NEXT)

### **Problem**: Single SQLite file bottlenecks
- Read/write conflicts on single file
- Performance issues with concurrent access
- Security mixing admin + user data
- Backup complexity

### **Solution**: Database Separation by Purpose

```
content/
├── articles/           # File storage (shared)
├── docs/              # File storage (shared)  
├── app.db            # Main application database
├── mark.db           # Mark admin database
└── cache.db          # Cache & temporary data
```

### **Database Mapping**:

#### **app.db** - Main Application
```sql
-- User management
users                 # App users, authentication
user_sessions         # User sessions
user_preferences      # User settings
user_activity_logs    # User actions

-- Application data
notifications         # User notifications
user_favorites        # Bookmarked articles
user_comments         # Article comments (if implemented)
```

#### **mark.db** - Mark Admin System  
```sql
-- Admin management
mark_users            # Admin users (separate from app users)
mark_sessions         # Admin sessions
mark_settings         # Admin configuration
mark_audit_logs       # Admin actions audit trail

-- Content management
article_drafts        # Draft articles before publishing
content_revisions     # Article version history
publishing_queue      # Scheduled publications
```

#### **cache.db** - Performance & Temporary
```sql
-- Caching
file_metadata_cache   # File modification times, sizes
query_result_cache    # Cached query results
search_index_cache    # Search index data

-- Temporary
temp_uploads          # Temporary file uploads
session_temp_data     # Temporary session data
background_jobs       # Job queue (if needed)
```

### **Implementation Tasks**:

1. **Enhance DatabaseModel**:
   ```php
   class DatabaseModel 
   {
       protected static string $database = 'app.db';  # Default
       
       protected static function getPdo(): PDO 
       {
           return DatabaseManager::getConnection(static::$database);
       }
   }
   ```

2. **Database Manager**:
   ```php
   class DatabaseManager 
   {
       private static array $connections = [];
       
       public static function getConnection(string $database): PDO
       {
           if (!isset(self::$connections[$database])) {
               self::$connections[$database] = new PDO("sqlite:content/{$database}");
           }
           return self::$connections[$database];
       }
   }
   ```

3. **Model Examples**:
   ```php
   class User extends DatabaseModel {
       protected static string $database = 'app.db';
   }
   
   class MarkUser extends DatabaseModel {
       protected static string $database = 'mark.db';
   }
   
   class CacheEntry extends DatabaseModel {
       protected static string $database = 'cache.db';
   }
   ```

## 🎯 Phase 2: Mark Admin System

### **Mark Features**:
- **Route Prefix**: `/mark` (not `/admin`)
- **Separate Authentication**: Mark users in `mark.db`
- **Article Management**: CRUD for articles (files)
- **User Oversight**: View/manage app users (read-only from `app.db`)
- **System Monitoring**: Health checks, logs, performance

### **Mark Architecture**:
```
src/Modules/Optional/Mark/
├── Controllers/
│   ├── MarkAuthController.php      # Mark login/logout
│   ├── MarkDashboardController.php # Main dashboard
│   ├── MarkArticleController.php   # Article management
│   ├── MarkUserController.php      # User oversight
│   └── MarkSystemController.php    # System monitoring
├── Models/
│   ├── MarkUser.php               # Admin users (mark.db)
│   ├── MarkSession.php            # Admin sessions
│   ├── MarkAuditLog.php           # Admin actions
│   └── MarkSettings.php           # Admin config
├── Views/
│   ├── auth/                      # Login forms
│   ├── dashboard/                 # Main dashboard
│   ├── articles/                  # Article management
│   └── system/                    # System monitoring
└── config.php                    # Mark module config
```

### **Mark Routes**:
```php
// Authentication
GET  /mark/login       # Mark login form
POST /mark/login       # Mark login process
POST /mark/logout      # Mark logout

// Dashboard
GET  /mark             # Mark dashboard

// Article Management
GET  /mark/articles              # List articles
GET  /mark/articles/create       # Create form
POST /mark/articles              # Store article
GET  /mark/articles/{slug}/edit  # Edit form
PUT  /mark/articles/{slug}       # Update article
DELETE /mark/articles/{slug}     # Delete article

// User Oversight (read-only)
GET  /mark/users                 # View app users
GET  /mark/users/{id}            # View user details

// System Monitoring
GET  /mark/system                # System health
GET  /mark/system/logs           # View logs
GET  /mark/system/cache          # Cache management
```

### **Mark Security**:
- **Separate authentication** - Mark users ≠ App users
- **Admin permissions** - Role-based access control
- **Audit logging** - All admin actions logged
- **Session isolation** - Mark sessions in mark.db

## 🎯 Phase 3: Advanced Features

### **Content Management**:
- **WYSIWYG Editor** - Markdown editor with preview
- **Image Upload** - File management for articles
- **Draft System** - Save drafts before publishing
- **Version Control** - Article revision history
- **Scheduled Publishing** - Publish articles at specific time

### **User Management**:
- **User Roles** - Admin, Editor, Author, User
- **Permissions** - Granular permission system
- **User Analytics** - Activity tracking, engagement
- **Bulk Operations** - Mass user management

### **System Features**:
- **Search System** - Full-text search across articles
- **Analytics** - Article views, user engagement
- **Backup System** - Automated backups
- **Performance Monitoring** - Query optimization, caching
- **API Endpoints** - REST API for external integrations

## 🎯 Phase 4: Scaling & Optimization

### **Performance**:
- **Database Indexing** - Optimize query performance
- **File Caching** - Advanced file caching strategies
- **CDN Integration** - Static asset delivery
- **Background Jobs** - Async processing

### **Scalability**:
- **Database Sharding** - Split databases by purpose
- **Read Replicas** - Separate read/write databases
- **Microservices** - Split Mark into separate service
- **Load Balancing** - Multiple app instances

## 📋 Current Implementation Status

### ✅ **Completed**:
- **Hybrid Storage System** (Files + Multi-Database) ✅
- **Multi-Database Architecture** (4 separate SQLite databases) ✅
- **Article Model** (File-based with Markdown + YAML) ✅
- **Documentation Model** (File-based with navigation) ✅
- **AppUser Model** (app.db - Application users) ✅
- **MarkUser Model** (mark.db - Admin users) ✅
- **MarkAuditLog Model** (mark.db - Admin action tracking) ✅
- **Storage Drivers** (Markdown, JSON, SQLite) ✅
- **FileModel & DatabaseModel** (Base classes) ✅
- **DatabaseManager** (Multi-database orchestration) ✅
- **Storage Service** (Driver orchestration) ✅
- **Documentation** (Complete guides and API docs) ✅
- **Test Suite** (Working demos and validation) ✅
- **PHPStan Level MAX** (0 errors across entire codebase) ✅

### 🎯 **Major Milestones Achieved**:

#### **Multi-Database Architecture COMPLETED** ✅
- **app.db**: Application users, sessions, preferences
- **mark.db**: Mark admin users, audit logs, content revisions
- **cache.db**: File metadata cache, query cache, temp data
- **analytics.db**: Page views, user engagement, performance logs
- **Benefits**: No read/write conflicts, security isolation, better performance

#### **PHPStan Level MAX: 0 ERRORS** ✅
- Started with: 96+ errors across Storage module
- Final result: 0 errors across entire codebase
- 100% type safety achieved across all modules

### 📅 **Next Up**:
1. **Implement Multi-Database support** (1-2 days)
2. **Create Mark Admin foundation** (2-3 days)
3. **Build Mark Article Management** (3-4 days)
4. **User → Session refactor** (align with existing User module)

## 🎯 Success Metrics

### **Technical**:
- ✅ **PHPStan Level MAX: 0 errors** (ACHIEVED!)
- ✅ **Hybrid Storage System** (ACHIEVED!)
- ✅ **Type Safety** (ACHIEVED!)
- 🔧 Multi-database architecture working
- 🔧 Mark admin system functional
- 🔧 Performance: <100ms response times
- 🔧 Test Coverage: >80%

### **Functional**:
- ✅ **Users can read articles** (file-based) (ACHIEVED!)
- ✅ **Article CRUD operations** (ACHIEVED!)
- ✅ **Documentation system** (ACHIEVED!)
- 🔧 Users can register/login (database)
- 🔧 Mark admins can manage articles
- 🔧 Mark admins can oversee users
- 🔧 System monitoring working

### **User Experience**:
- ✅ **Fast article loading** (file-based) (ACHIEVED!)
- ✅ **Git-friendly content** (ACHIEVED!)
- 🔧 Intuitive Mark admin interface
- 🔧 Responsive design
- 🔧 Good SEO for articles
- 🔧 Reliable authentication

---

**This plan ensures we build a robust, scalable system with proper separation of concerns and excellent performance characteristics.**
