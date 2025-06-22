---
title: "Hybrid Storage System"
slug: "hybrid-storage"
category: "architecture"
order: 5
description: "Learn about MVA Bootstrap's hybrid storage approach combining file-based and database storage"
difficulty: "intermediate"
estimated_time: "10 minutes"
tags: ["storage", "files", "database", "hybrid", "orbit"]
author: "MVA Bootstrap Team"
---

# Hybrid Storage System

MVA Bootstrap implements a **hybrid storage approach** that combines the best of both worlds: file-based storage for content and database storage for relational data.

## 🎯 Philosophy

Inspired by Laravel's [Orbit package](https://github.com/ryangjchandler/orbit), our hybrid approach uses:

- **📝 Files** for content that benefits from version control (articles, documentation, settings)
- **🗄️ Database** for relational data that needs queries and indexes (users, sessions, logs)

## 🏗️ Architecture Overview

```
src/Modules/Core/Storage/
├── Contracts/
│   └── StorageDriverInterface.php    # Common interface
├── Drivers/
│   ├── MarkdownDriver.php           # File-based (.md + YAML)
│   ├── JsonDriver.php               # File-based (.json)
│   └── SqliteDriver.php             # Database-based (SQLite)
├── Models/
│   ├── FileModel.php                # Base for file storage
│   ├── DatabaseModel.php            # Base for database storage
│   ├── Article.php                  # File-based model
│   ├── Documentation.php            # File-based model
│   └── User.php                     # Database-based model
└── Services/
    └── FileStorageService.php       # Storage orchestration
```

## 📁 File-Based Storage

### Features
- **YAML Front-matter** + Markdown content
- **Git-friendly** - version control your content
- **No database required** for content
- **Fast performance** with built-in caching
- **Human-readable** files

### Example: Article Model

```php
use MvaBootstrap\Modules\Core\Storage\Models\Article;

// Create article
$article = Article::create([
    'title' => 'Getting Started',
    'slug' => 'getting-started',
    'author' => 'John Doe',
    'published' => true,
    'category' => 'guides',
    'tags' => ['tutorial', 'basics'],
    'content' => '# Getting Started\n\nWelcome to our platform!'
]);

// Query articles
$published = Article::published();
$featured = Article::featured();
$byCategory = Article::byCategory('guides');
```

### Generated File Structure

```markdown
content/articles/getting-started.md
---
title: "Getting Started"
slug: "getting-started"
author: "John Doe"
published: true
category: "guides"
tags: ["tutorial", "basics"]
reading_time: 2
published_at: "2024-01-01 12:00:00"
---

# Getting Started

Welcome to our platform!
```

## 🗄️ Database Storage

### Features
- **SQLite database** for relational data
- **Auto-schema creation** from model definitions
- **Query capabilities** with indexes
- **ACID transactions**
- **Relational integrity**

### Example: User Model

```php
use MvaBootstrap\Modules\Core\Storage\Models\User;

// Create user
$user = User::create([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'role' => 'admin'
]);

$user->setPassword('secret123');
$user->save();

// Query users
$admins = User::byRole('admin');
$active = User::active();
$user = User::findByEmail('john@example.com');

// Authentication
if ($user->verifyPassword('secret123')) {
    $user->recordLogin();
}
```

## 🔄 Unified API

Both storage types share a common API:

```php
// Same methods work for both file and database models
$model = Model::create($data);
$model->save();
$model->delete();

$all = Model::all();
$found = Model::find($id);
```

## 🚀 Getting Started

### 1. Setup Storage Service

```php
use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;

$storageService = new FileStorageService('/path/to/content');

// Set for file models
Article::setStorageService($storageService);

// Set for database models  
User::setStorageService($storageService);
```

### 2. Define Model Schema

```php
class Article extends FileModel
{
    protected static string $driver = 'markdown';
    
    public static function schema(): array
    {
        return [
            'title' => 'string|required',
            'slug' => 'string|required|unique',
            'author' => 'string|required',
            'published' => 'boolean|default:false',
            'content' => 'text',
        ];
    }
}
```

### 3. Use Models

```php
// File-based
$article = Article::create([
    'title' => 'My Article',
    'slug' => 'my-article',
    'content' => '# Hello World'
]);

// Database-based
$user = User::create([
    'username' => 'john',
    'email' => 'john@example.com'
]);
```

## 📊 Storage Decision Matrix

| Data Type | Storage | Reason |
|-----------|---------|---------|
| **Articles** | Files | Version control, easy editing |
| **Documentation** | Files | Git workflow, markdown |
| **Settings** | Files | Configuration as code |
| **Users** | Database | Authentication, relations |
| **Sessions** | Database | Queries, cleanup |
| **Logs** | Database | Indexing, analytics |
| **Cache** | Database | Performance, TTL |

## 🛠️ Advanced Usage

### Custom Drivers

```php
class YamlDriver extends AbstractFileDriver
{
    public function getExtension(): string
    {
        return 'yaml';
    }
    
    public function parseFile(SplFileInfo $file): array
    {
        return yaml_parse_file($file->getPathname());
    }
}

// Register custom driver
$storageService->registerDriver('yaml', new YamlDriver());
```

### Database Migrations

```php
class CreateUsersTable
{
    public function up(SqliteDriver $driver): void
    {
        $driver->addColumn('avatar_url', 'TEXT');
        $driver->addColumn('timezone', 'TEXT DEFAULT "UTC"');
    }
}
```

### Caching

```php
// File cache (automatic)
$articles = Article::all(); // Cached until files change

// Database cache (60 seconds)
$users = User::all(); // Cached for 1 minute

// Clear cache
$storageService->clearCache();
```

## 🔧 Configuration

### Storage Service Setup

```php
// config.php
'services' => [
    FileStorageService::class => function (Container $container): FileStorageService {
        $contentDir = $container->get(Paths::class)->base() . '/content';
        return new FileStorageService($contentDir);
    },
],
```

### Model Registration

```php
// Module config
'content_types' => [
    'articles' => [
        'model' => Article::class,
        'driver' => 'markdown',
        'directory' => 'articles',
    ],
    'users' => [
        'model' => User::class,
        'driver' => 'sqlite',
        'table' => 'users',
    ],
],
```

## 🎯 Benefits

### File Storage Benefits
- ✅ **Git-friendly** - version control content
- ✅ **Human-readable** - edit in any text editor
- ✅ **No database** - simple deployment
- ✅ **Fast reads** - file system performance
- ✅ **Backup-friendly** - just copy files

### Database Storage Benefits
- ✅ **Relational queries** - JOIN, WHERE, ORDER BY
- ✅ **ACID transactions** - data integrity
- ✅ **Indexes** - fast lookups
- ✅ **Concurrent access** - multiple users
- ✅ **Data validation** - constraints

### Hybrid Benefits
- ✅ **Best of both worlds** - right tool for right job
- ✅ **Unified API** - consistent development experience
- ✅ **Flexible** - easy to switch storage types
- ✅ **Scalable** - optimize each data type separately

## 🧪 Testing

```php
// Test file storage
$article = Article::create(['title' => 'Test']);
assert(file_exists('content/articles/test.md'));

// Test database storage  
$user = User::create(['username' => 'test']);
assert(User::findByUsername('test') !== null);

// Test hybrid queries
$articles = Article::all();
$users = User::all();
assert(count($articles) > 0 && count($users) > 0);
```

## 🔮 Future Enhancements

- **Multi-database support** (MySQL, PostgreSQL)
- **Cloud storage drivers** (S3, Google Cloud)
- **Full-text search** integration
- **Real-time sync** between storage types
- **Advanced caching** strategies
- **Content versioning** system

## 📚 Related Documentation

- [File Models Guide](file-models.md)
- [Database Models Guide](database-models.md)
- [Storage Drivers](storage-drivers.md)
- [Performance Optimization](performance.md)

---

The hybrid storage system provides the flexibility to choose the right storage mechanism for each type of data, resulting in better performance, easier maintenance, and improved developer experience.
