# Storage Module

**Hybrid file-based and database storage system for MVA Bootstrap**

Inspired by Laravel's [Orbit package](https://github.com/ryangjchandler/orbit), this module provides a unified API for both file-based content storage and database-based relational data.

## 🎯 Features

- **📝 File Storage** - Markdown with YAML front-matter (like Orbit)
- **🗄️ Database Storage** - SQLite for relational data
- **🔄 Unified API** - Same methods for both storage types
- **⚡ Performance** - Built-in caching and optimization
- **🔧 Flexible** - Easy to extend with custom drivers

## 🚀 Quick Start

```php
use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;
use MvaBootstrap\Modules\Core\Storage\Models\User;

// Setup
$storage = new FileStorageService('./content');
Article::setStorageService($storage);
User::setStorageService($storage);

// File-based model (Article)
$article = Article::create([
    'title' => 'Hello World',
    'slug' => 'hello-world',
    'content' => '# Hello\n\nThis is stored in a file!'
]);

// Database model (User)  
$user = User::create([
    'username' => 'john',
    'email' => 'john@example.com'
]);
```

## 📁 Architecture

```
Storage/
├── Contracts/
│   └── StorageDriverInterface.php    # Common interface
├── Drivers/
│   ├── MarkdownDriver.php           # .md + YAML front-matter
│   ├── JsonDriver.php               # .json files
│   └── SqliteDriver.php             # SQLite database
├── Models/
│   ├── FileModel.php                # Base for file storage
│   ├── DatabaseModel.php            # Base for database storage
│   ├── Article.php                  # File-based content
│   ├── Documentation.php            # File-based docs
│   └── User.php                     # Database-based users
└── Services/
    └── FileStorageService.php       # Storage orchestration
```

## 🗂️ Storage Types

### File Storage (Orbit-style)

**Best for**: Content, documentation, settings

```php
class Article extends FileModel
{
    protected static string $driver = 'markdown';
    
    public static function schema(): array
    {
        return [
            'title' => 'string|required',
            'slug' => 'string|required|unique', 
            'published' => 'boolean|default:false',
            'content' => 'text',
        ];
    }
}
```

**Generated file**:
```markdown
content/articles/hello-world.md
---
title: "Hello World"
slug: "hello-world"
published: true
published_at: "2024-01-01 12:00:00"
---

# Hello

This is stored in a file!
```

### Database Storage

**Best for**: Users, sessions, logs, analytics

```php
class User extends DatabaseModel
{
    protected static string $driver = 'sqlite';
    protected static string $table = 'users';
    
    public static function schema(): array
    {
        return [
            'username' => 'string|required|unique',
            'email' => 'string|required|unique',
            'password_hash' => 'string|required',
            'role' => 'string|default:user',
        ];
    }
}
```

## 🔧 API Reference

### Common Methods (Both Storage Types)

```php
// Create
$model = Model::create($attributes);

// Read
$model = Model::find($id);
$models = Model::all();

// Update
$model->setAttribute('key', 'value');
$model->save();

// Delete
$model->delete();
```

### File-Specific Methods

```php
// Article queries
$published = Article::published();
$featured = Article::featured();
$byCategory = Article::byCategory('news');
$byTag = Article::byTag('tutorial');
$recent = Article::recent(10);
$search = Article::search('query');
```

### Database-Specific Methods

```php
// User queries
$active = User::active();
$admins = User::byRole('admin');
$user = User::findByUsername('john');
$user = User::findByEmail('john@example.com');

// Authentication
$user->setPassword('secret');
$user->verifyPassword('secret');
$user->recordLogin();
```

## 🎛️ Configuration

### Module Registration

```php
// config.php
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

### Service Setup

```php
'services' => [
    FileStorageService::class => function (Container $container): FileStorageService {
        $contentDir = $container->get(Paths::class)->base() . '/content';
        return new FileStorageService($contentDir);
    },
],
```

## 🧪 Testing

Run the included tests:

```bash
# Test file storage
php test_storage.php

# Test hybrid storage
php test_hybrid.php
```

## 📊 Performance

### File Storage
- **Caching**: Automatic file-based caching
- **Performance**: Fast reads, moderate writes
- **Scalability**: Excellent for content

### Database Storage  
- **Caching**: 60-second query cache
- **Performance**: Fast queries with indexes
- **Scalability**: Excellent for relational data

## 🔮 Extending

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

$storageService->registerDriver('yaml', new YamlDriver());
```

### Custom Models

```php
class CustomModel extends FileModel
{
    protected static string $driver = 'yaml';
    
    public static function schema(): array
    {
        return [
            'name' => 'string|required',
            'data' => 'array|nullable',
        ];
    }
}
```

## 📚 Documentation

- **[Hybrid Storage Guide](../../../content/docs/hybrid-storage.md)** - Complete guide
- **[Quick Start](../../../content/docs/storage-quick-start.md)** - 5-minute tutorial
- **[File Models](../../../content/docs/file-models.md)** - File storage details
- **[Database Models](../../../content/docs/database-models.md)** - Database storage details

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure PHPStan passes
5. Submit a pull request

## 📄 License

This module is part of MVA Bootstrap and follows the same license terms.

## 🙏 Credits

- Inspired by [Laravel Orbit](https://github.com/ryangjchandler/orbit) by Ryan Chandler
- Built with ❤️ by the MVA Bootstrap team

---

**Happy coding!** 🚀
