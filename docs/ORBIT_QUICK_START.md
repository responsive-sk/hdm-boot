# Orbit Quick Start

**Get started with HDM Boot's Laravel Orbit-inspired CMS in 5 minutes!**

## 🚀 What is Orbit?

**Laravel Orbit** by Ryan Chandler is a flat-file driver for Eloquent models. **HDM Boot Orbit** enhances this concept with:

- **Hybrid Storage** - Files + Database
- **Multi-Database Architecture** - Separate SQLite databases
- **Type Safety** - PHPStan Level MAX
- **Security** - Path traversal protection
- **Framework Agnostic** - Works with any PHP project

## ⚡ Quick Setup

### 1. Initialize Storage System

```php
use HdmBoot\Modules\Core\Storage\Services\DatabaseManager;
use HdmBoot\Modules\Core\Storage\Services\FileStorageService;
use HdmBoot\Modules\Core\Storage\Models\Article;

// Initialize multi-database system
DatabaseManager::initialize('./content');

// Setup file storage
$storage = new FileStorageService('./content');
Article::setStorageService($storage);
```

### 2. Create Your First Article

```php
// Create article programmatically
$article = Article::create([
    'title' => 'My First Post',
    'slug' => 'my-first-post',
    'author' => 'John Doe',
    'published' => true,
    'category' => 'tutorial',
    'tags' => ['php', 'orbit', 'cms'],
    'content' => '# My First Post\n\nWelcome to Orbit CMS!'
]);

// Article is saved as: content/articles/my-first-post.md
```

### 3. Query Articles (Orbit-Style API)

```php
// Get all articles
$articles = Article::all();

// Get published articles only
$published = Article::published();

// Get featured articles
$featured = Article::featured();

// Find specific article
$article = Article::find('my-first-post');

// Get by category
$tutorials = Article::byCategory('tutorial');

// Get by tag
$phpArticles = Article::byTag('php');

// Get categories and tags
$categories = Article::getCategories();
$tags = Article::getTags();
```

## 📁 File Structure

Your content is stored as Markdown files with YAML front-matter:

```
content/
├── articles/
│   └── my-first-post.md
└── docs/

var/orbit/                 # Auto-created databases
├── app.db                # Application data
├── mark.db               # Admin system
├── cache.db              # Performance cache
└── analytics.db          # Metrics
```

## 📝 Article Format

```markdown
---
title: "My First Post"
slug: "my-first-post"
author: "John Doe"
published: true
published_at: "2024-01-20 14:30:00"
category: "tutorial"
tags: ["php", "orbit", "cms"]
featured: false
excerpt: "Learn how to use Orbit CMS"
reading_time: 3
---

# My First Post

Welcome to **Orbit CMS**! This is your first article.

## Features

- File-based storage
- Git-friendly content
- Eloquent-like API
- Type-safe operations

## Getting Started

Create articles by adding Markdown files to `content/articles/`.
```

## 🌐 Web Interface

### Setup Blog Routes

```php
// config/routes.php
use HdmBoot\Modules\Optional\Blog\Controllers\BlogController;

$app->get('/blog', function ($request, $response) {
    $controller = new BlogController();
    $html = $controller->home();
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/blog/article/{slug}', function ($request, $response) {
    $controller = new BlogController();
    $slug = $request->getAttribute('slug');
    $html = $controller->article($slug);
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});
```

### Visit Your Blog

```bash
# Start development server
php -S localhost:8000 -t public

# Visit blog
open http://localhost:8000/blog
```

## 🔧 Advanced Usage

### Custom Queries

```php
// Get recent articles
$recent = array_slice(Article::published(), 0, 5);

// Sort by date
$articles = Article::all();
usort($articles, function($a, $b) {
    return strcmp(
        $b->getAttribute('published_at'),
        $a->getAttribute('published_at')
    );
});

// Filter by multiple criteria
$featured = array_filter(Article::published(), function($article) {
    return $article->getAttribute('featured') === true;
});
```

### Admin Operations

```php
use HdmBoot\Modules\Core\Storage\Models\MarkUser;
use HdmBoot\Modules\Core\Storage\Models\MarkAuditLog;

// Admin authentication
$admin = MarkUser::authenticate($credentials);

// Create article with audit logging
$article = Article::create($data);
MarkAuditLog::logArticleAction($admin->id, 'article.create', $article->slug);

// View audit logs
$logs = MarkAuditLog::recent(10);
```

### Performance Optimization

```php
// Database health check
$health = DatabaseManager::getHealthStatus();

// Create database indexes
DatabaseManager::createIndex('articles', ['category', 'published_at']);

// Cache frequently accessed data
$popularArticles = Article::cached('popular', function() {
    return Article::byTag('popular');
});
```

## 🔒 Security Features

### Path Security

```php
// ✅ Secure file access (automatic)
$article = Article::find('my-post'); // Uses Paths service internally

// ❌ Never do this
$file = $baseDir . '/' . $userInput; // Vulnerable to path traversal
```

### Database Isolation

```php
// Different models use different databases
Article::setDatabase('app');        // content + app.db
MarkUser::setDatabase('mark');      // mark.db (admin only)
CacheEntry::setDatabase('cache');   // cache.db (performance)
```

## 📊 Comparison with Laravel Orbit

| Feature | Laravel Orbit | HDM Boot |
|---------|---------------|---------------|
| **API Style** | `Post::where('published', true)->get()` | `Article::published()` |
| **Storage** | Files only | Files + Database |
| **Framework** | Laravel only | Framework agnostic |
| **Type Safety** | Basic | PHPStan Level MAX |
| **Admin Interface** | None | Mark admin system |
| **Security** | Basic | Path traversal protection |

## 🎯 Next Steps

1. **Read Full Documentation**: [Orbit Implementation](ORBIT_IMPLEMENTATION.md)
2. **Try Examples**: [Orbit Example Guide](../content/docs/orbit-example.md)
3. **Learn Architecture**: [Hybrid Storage](../content/docs/hybrid-storage.md)
4. **Build Admin Interface**: [Mark Admin System](../content/docs/mark-admin.md)
5. **Deploy to Production**: [Deployment Guide](DEPLOYMENT.md)

## 🚀 Production Ready

HDM Boot Orbit is **production-ready** with:

- ✅ **Type Safety** - PHPStan Level MAX compliance
- ✅ **Security** - Path traversal protection
- ✅ **Performance** - Hybrid storage optimization
- ✅ **Scalability** - Multi-database architecture
- ✅ **Maintainability** - Modular design
- ✅ **Documentation** - Complete guides and examples

**Start building amazing content-driven applications today!** 🎉

---

**Need help?** Check out our [complete documentation](ORBIT_IMPLEMENTATION.md) or [examples](../content/docs/orbit-example.md).
