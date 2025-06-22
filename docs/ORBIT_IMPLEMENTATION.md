# HDM Boot Orbit Implementation

**Complete documentation of our Laravel Orbit-inspired content management system**

## 🎯 Overview

HDM Boot implements a **Laravel Orbit-inspired** file-based content management system that combines the elegance of Eloquent-like APIs with the performance and flexibility of hybrid storage.

### What is Laravel Orbit?

[Laravel Orbit](https://github.com/ryangjchandler/orbit) by Ryan Chandler is a flat-file driver for Eloquent models, allowing you to use Eloquent models with Markdown files instead of a database.

### HDM Boot Enhancement

Our implementation **enhances** the Orbit concept with:

- **Hybrid Storage** - Files + Database for optimal performance
- **Multi-Database Architecture** - Separate SQLite databases by purpose
- **Type Safety** - PHPStan Level MAX compliance
- **Security** - Paths service integration for secure file access
- **Modular Design** - Framework-agnostic architecture

## 🏗️ Architecture

### File Structure

```
content/                    # Git-friendly content
├── articles/              # Article files (.md)
│   ├── getting-started.md
│   ├── web-interface-demo.md
│   └── advanced-techniques.md
└── docs/                  # Documentation files (.md)
    ├── orbit-example.md
    ├── hybrid-storage.md
    └── storage-quick-start.md

var/orbit/                 # Runtime databases (gitignored)
├── app.db                # Application database
├── mark.db               # Mark admin database  
├── cache.db              # Cache database
└── analytics.db          # Analytics database

src/Modules/
├── Core/
│   ├── Storage/          # Hybrid storage system
│   │   ├── Models/       # Article, MarkUser, etc.
│   │   └── Services/     # FileStorageService, DatabaseManager
│   └── Template/         # Template rendering
└── Optional/
    └── Blog/             # Blog web interface
        └── Controllers/  # BlogController
```

### Core Components

#### 1. **FileStorageService**
```php
// Core storage service for file-based operations
class FileStorageService
{
    public function loadArticles(): array
    public function saveArticle(Article $article): void
    public function findArticle(string $slug): ?Article
}
```

#### 2. **DatabaseManager**
```php
// Multi-database management
class DatabaseManager
{
    public static function initialize(string $contentDir): void
    public static function getConnection(string $database): PDO
    public static function createTables(string $database): void
}
```

#### 3. **Article Model**
```php
// Orbit-style model with Eloquent-like API
class Article
{
    public static function all(): array
    public static function find(string $slug): ?Article
    public static function published(): array
    public static function featured(): array
    public static function byCategory(string $category): array
    public static function byTag(string $tag): array
}
```

## 🚀 Usage Examples

### Basic Operations

```php
use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Models\Article;

// Setup
$storage = new FileStorageService('./content');
Article::setStorageService($storage);

// Query articles (Orbit-style API)
$articles = Article::all();
$published = Article::published();
$featured = Article::featured();

// Find specific article
$article = Article::find('getting-started');

// Get by category/tag
$tutorials = Article::byCategory('tutorial');
$phpArticles = Article::byTag('php');
```

### Creating Articles

```php
// Create new article
$article = Article::create([
    'title' => 'My New Post',
    'slug' => 'my-new-post',
    'author' => 'John Doe',
    'published' => true,
    'category' => 'tutorial',
    'tags' => ['php', 'tutorial'],
    'content' => '# My New Post\n\nContent here...'
]);

// Article is automatically saved as:
// content/articles/my-new-post.md
```

### File Format

Articles are stored as Markdown files with YAML front-matter:

```markdown
---
title: "Getting Started with HDM Boot"
slug: "getting-started"
author: "John Doe"
published: true
published_at: "2024-01-15 10:00:00"
category: "tutorial"
tags: ["tutorial", "getting-started", "php"]
featured: true
excerpt: "Learn how to build amazing applications"
reading_time: 5
---

# Getting Started with HDM Boot

Your article content here using **Markdown** syntax.

## Features

- File-based storage
- Git-friendly
- Type-safe operations
```

## 🔄 Orbit vs HDM Boot Comparison

| Feature | Laravel Orbit | HDM Boot |
|---------|---------------|---------------|
| **Framework** | Laravel only | Framework agnostic |
| **Storage** | Files only | Hybrid (Files + Database) |
| **Query API** | Eloquent methods | Custom Orbit-like methods |
| **Performance** | File-based | File + Database optimized |
| **Type Safety** | Basic | PHPStan Level MAX |
| **Security** | Basic | Paths service integration |
| **Multi-tenancy** | No | Multi-database support |
| **Admin Interface** | No | Mark admin system |

### API Compatibility

```php
// Laravel Orbit
$posts = Post::all();
$post = Post::find('my-post');
$published = Post::where('published', true)->get();

// HDM Boot (similar API)
$articles = Article::all();
$article = Article::find('my-post');
$published = Article::published();
```

## 🎨 Advanced Features

### Multi-Database Architecture

```php
// Different models use different databases
Article::setDatabase('app');        // content + app.db
MarkUser::setDatabase('mark');      // mark.db (admin)
CacheEntry::setDatabase('cache');   // cache.db (performance)
Analytics::setDatabase('analytics'); // analytics.db (metrics)
```

### Admin Interface (Mark)

```php
// Admin operations with audit logging
$admin = MarkUser::authenticate($credentials);
$article = Article::create($data);

// Automatically logged to mark.db
MarkAuditLog::logArticleAction($admin->id, 'article.create', $article->slug);
```

### Performance Optimization

```php
// File caching
$articles = Article::cached('published', function() {
    return Article::published();
});

// Database indexing
DatabaseManager::createIndex('articles', ['category', 'published_at']);

// Lazy loading
$article = Article::find('slug'); // Only loads metadata
$content = $article->getContent(); // Loads full content when needed
```

## 🔧 Configuration

### Database Configuration

```php
// config/database.php
return [
    'orbit' => [
        'driver' => 'sqlite',
        'databases' => [
            'app' => 'var/orbit/app.db',
            'mark' => 'var/orbit/mark.db',
            'cache' => 'var/orbit/cache.db',
            'analytics' => 'var/orbit/analytics.db',
        ]
    ]
];
```

### Storage Configuration

```php
// Initialize storage system
DatabaseManager::initialize('./content');

// Setup models
$storage = new FileStorageService('./content');
Article::setStorageService($storage);
MarkUser::setStorageService($storage);
```

### Paths Integration (Security)

```php
// Secure file access using Paths service
class FileStorageService
{
    public function __construct(
        private readonly string $contentDir,
        private readonly Paths $paths
    ) {}
    
    private function getArticlePath(string $slug): string
    {
        // Secure path resolution
        return $this->paths->getPath($this->contentDir, 'articles', $slug . '.md');
    }
}
```

## 🚀 Web Interface

### Blog Controller

```php
// Optional/Blog module
class BlogController
{
    public function home(): string
    {
        $articles = Article::published();
        return $this->renderBlogHome($articles);
    }
    
    public function article(string $slug): string
    {
        $article = Article::find($slug);
        return $this->renderArticle($article);
    }
}
```

### Routes

```php
// Blog routes
$app->get('/blog', [BlogController::class, 'home']);
$app->get('/blog/article/{slug}', [BlogController::class, 'article']);
$app->get('/blog/categories', [BlogController::class, 'categories']);
$app->get('/blog/tags', [BlogController::class, 'tags']);
```

## 📊 Performance Benefits

### File-Based Advantages

- **Git-friendly** - Version control your content
- **Human-readable** - Direct file editing
- **Fast reads** - No database overhead for content
- **Backup-friendly** - Simple file copying

### Database Advantages

- **ACID transactions** - Data integrity
- **Complex queries** - Relationships and joins
- **Indexing** - Fast lookups
- **Concurrent access** - Multiple users

### Hybrid Benefits

- **Best of both worlds** - Right tool for right job
- **Performance optimization** - Files for content, DB for metadata
- **Scalability** - Can migrate to full DB when needed
- **Flexibility** - Easy to extend and modify

## 🔒 Security Features

### Path Traversal Protection

```php
// ❌ Vulnerable
$file = $baseDir . '/' . $userInput;

// ✅ Secure with Paths
$file = $this->paths->getPath($baseDir, $userInput);
```

### Database Isolation

- **Separate databases** - Prevent data mixing
- **Read/write separation** - Optimize performance
- **Access control** - Different permissions per database

### Audit Logging

```php
// All admin actions logged
MarkAuditLog::logArticleAction($userId, 'article.create', $slug, $details);
MarkAuditLog::logUserAction($userId, 'user.login', $ip);
```

## 🧪 Testing

### Unit Tests

```php
class ArticleTest extends TestCase
{
    public function testCreateArticle(): void
    {
        $article = Article::create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => '# Test'
        ]);
        
        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->getAttribute('title'));
    }
}
```

### Integration Tests

```php
class BlogControllerTest extends TestCase
{
    public function testBlogHomepage(): void
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);
        $response->assertSee('HDM Boot Blog');
    }
}
```

## 📚 Related Documentation

- [Orbit-Style Implementation Example](../content/docs/orbit-example.md)
- [Hybrid Storage System](../content/docs/hybrid-storage.md)
- [Multi-Database Architecture](../content/docs/multi-database-architecture.md)
- [Storage Quick Start](../content/docs/storage-quick-start.md)
- [Path Security](PATH_SECURITY.md)

## 🎯 Migration from Laravel Orbit

### Step 1: Install HDM Boot

```bash
composer create-project hdm-boot/bootstrap my-app
```

### Step 2: Migrate Content

```bash
# Copy your Orbit content files
cp -r /path/to/orbit/content/* ./content/articles/
```

### Step 3: Update Models

```php
// Laravel Orbit
class Post extends Model
{
    use Orbit;
}

// HDM Boot
class Article extends BaseModel
{
    // Automatic Orbit-like functionality
}
```

### Step 4: Update Queries

```php
// Laravel Orbit
$posts = Post::where('published', true)->get();

// HDM Boot
$articles = Article::published();
```

## 🚀 Future Roadmap

### Planned Features

- **Full-text search** - Elasticsearch integration
- **Content versioning** - Git-based versioning
- **Multi-language support** - i18n content management
- **Real-time collaboration** - WebSocket-based editing
- **Content scheduling** - Automated publishing
- **SEO optimization** - Meta tags and sitemaps
- **Performance monitoring** - Analytics and metrics
- **API endpoints** - RESTful content API

### Performance Improvements

- **Lazy loading** - Load content on demand
- **Caching layers** - Redis/Memcached integration
- **CDN integration** - Static asset optimization
- **Database sharding** - Horizontal scaling

---

**HDM Boot Orbit implementation brings the elegance of Laravel Orbit to any PHP project with enhanced performance, security, and scalability!** 🚀
