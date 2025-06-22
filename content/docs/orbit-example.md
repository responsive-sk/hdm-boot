---
title: "Orbit-Style Implementation Example"
slug: "orbit-example"
category: "examples"
order: 2
description: "Complete example of Laravel Orbit-inspired file-based content management in MVA Bootstrap"
difficulty: "beginner"
estimated_time: "10 minutes"
tags: ["orbit", "example", "tutorial", "laravel", "content"]
author: "MVA Bootstrap Team"
---

# Orbit-Style Implementation Example

This guide shows how MVA Bootstrap implements **Laravel Orbit-inspired** file-based content management with the same elegant API and workflow.

## 🎯 What is Orbit?

[Laravel Orbit](https://github.com/ryangjchandler/orbit) by Ryan Chandler is a flat-file driver for Eloquent models. It allows you to use Eloquent models with Markdown files instead of a database.

**MVA Bootstrap** implements the same concept with our own **Hybrid Storage System**.

## 🚀 Complete Working Example

### **Step 1: Create Your First Article**

Create a new article file:

```bash
# Create the file
touch content/articles/getting-started.md
```

Add content with YAML front-matter:

```markdown
---
title: "Getting Started with MVA Bootstrap"
slug: "getting-started"
author: "John Doe"
published: true
published_at: "2024-01-15 10:00:00"
category: "tutorial"
tags: ["tutorial", "getting-started", "php"]
featured: true
excerpt: "Learn how to build amazing applications with MVA Bootstrap"
reading_time: 5
---

# Getting Started with MVA Bootstrap

Welcome to **MVA Bootstrap** - the modern PHP framework that makes development a joy!

## What You'll Learn

In this tutorial, you'll discover:

- How to set up your first project
- Working with the hybrid storage system
- Building your first API endpoints
- Managing users and authentication

## Installation

```bash
composer create-project mva-bootstrap/bootstrap my-app
cd my-app
php -S localhost:8000 -t public
```

## Your First Model

```php
use MvaBootstrap\Modules\Core\Storage\Models\Article;

// Create article
$article = Article::create([
    'title' => 'My First Post',
    'slug' => 'my-first-post',
    'content' => '# Hello World!'
]);

// Query articles
$published = Article::published();
$featured = Article::featured();
```

That's it! You're ready to build amazing things! 🚀
```

### **Step 2: Use the Orbit-Style API**

Now you can work with your article using the familiar Eloquent-like API:

```php
<?php

require_once 'vendor/autoload.php';

use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;

// Setup (one-time)
$storage = new FileStorageService('./content');
Article::setStorageService($storage);

// 🔍 Query articles (just like Eloquent!)
$articles = Article::all();
echo "Found " . count($articles) . " articles\n";

// 📝 Get specific article
$article = Article::find('getting-started');
echo "Title: " . $article->getAttribute('title') . "\n";
echo "Author: " . $article->getAttribute('author') . "\n";
echo "Published: " . ($article->getAttribute('published') ? 'Yes' : 'No') . "\n";

// 🎯 Query published articles
$published = Article::published();
echo "Published articles: " . count($published) . "\n";

// ⭐ Get featured articles
$featured = Article::featured();
echo "Featured articles: " . count($featured) . "\n";

// 📂 Get by category
$tutorials = Article::byCategory('tutorial');
echo "Tutorial articles: " . count($tutorials) . "\n";

// 🏷️ Get by tag
$phpArticles = Article::byTag('php');
echo "PHP articles: " . count($phpArticles) . "\n";

// 🔍 Search articles
$results = Article::search('bootstrap');
echo "Search results: " . count($results) . "\n";

// 📊 Get recent articles
$recent = Article::recent(5);
echo "Recent articles: " . count($recent) . "\n";
```

### **Step 3: Create Articles Programmatically**

```php
// ✨ Create new article (Orbit-style)
$newArticle = Article::create([
    'title' => 'Advanced PHP Techniques',
    'slug' => 'advanced-php-techniques',
    'author' => 'Jane Smith',
    'published' => true,
    'category' => 'advanced',
    'tags' => ['php', 'advanced', 'techniques'],
    'featured' => false,
    'content' => "# Advanced PHP Techniques\n\nLearn advanced PHP patterns and best practices.\n\n## Topics Covered\n\n- Design Patterns\n- Performance Optimization\n- Security Best Practices"
]);

echo "✅ Created: " . $newArticle->getAttribute('title') . "\n";
echo "📄 File: content/articles/" . $newArticle->getAttribute('slug') . ".md\n";

// 🔄 Update article
$newArticle->setAttribute('reading_time', 8);
$newArticle->save();

echo "✅ Updated reading time\n";
```

### **Step 4: Generated File Structure**

After running the code above, you'll have:

```
content/
├── articles/
│   ├── getting-started.md
│   └── advanced-php-techniques.md
└── docs/
    └── (documentation files)
```

**getting-started.md** contains:
```markdown
---
title: "Getting Started with MVA Bootstrap"
slug: "getting-started"
author: "John Doe"
published: true
published_at: "2024-01-15 10:00:00"
category: "tutorial"
tags: ["tutorial", "getting-started", "php"]
featured: true
excerpt: "Learn how to build amazing applications with MVA Bootstrap"
reading_time: 5
---

# Getting Started with MVA Bootstrap
(... your content ...)
```

## 🔄 Orbit vs MVA Bootstrap Comparison

### **Laravel Orbit**
```php
// Orbit example
use App\Models\Post;

$posts = Post::all();
$post = Post::find('my-first-post');
$published = Post::where('published', true)->get();
```

### **MVA Bootstrap**
```php
// MVA Bootstrap equivalent
use MvaBootstrap\Modules\Core\Storage\Models\Article;

$articles = Article::all();
$article = Article::find('my-first-post');
$published = Article::published();
```

### **Key Differences**

| Feature | Laravel Orbit | MVA Bootstrap |
|---------|---------------|---------------|
| **Framework** | Laravel only | Framework agnostic |
| **Storage** | Files only | Hybrid (Files + Database) |
| **Query API** | Eloquent methods | Custom methods |
| **Performance** | File-based | File + Database optimized |
| **Type Safety** | Basic | PHPStan Level MAX |

## 🎨 Advanced Usage Examples

### **Content Management**

```php
// 📊 Get content statistics
$categories = Article::getCategories();
$tags = Article::getTags();

echo "Categories: " . implode(', ', $categories) . "\n";
echo "Tags: " . implode(', ', $tags) . "\n";

// 📈 Content analytics
$totalArticles = count(Article::all());
$publishedCount = count(Article::published());
$featuredCount = count(Article::featured());

echo "Total: {$totalArticles}, Published: {$publishedCount}, Featured: {$featuredCount}\n";
```

### **Content Workflow**

```php
// 📝 Draft workflow
$draft = Article::create([
    'title' => 'Work in Progress',
    'slug' => 'work-in-progress',
    'author' => 'Writer',
    'published' => false, // Draft
    'content' => '# Coming Soon...'
]);

// ✅ Publish when ready
$draft->setAttribute('published', true);
$draft->setAttribute('published_at', date('Y-m-d H:i:s'));
$draft->save();

echo "✅ Article published!\n";
```

### **SEO and Metadata**

```php
// 🔍 SEO-friendly URLs
$article = Article::find('getting-started');
$url = $article->getUrl(); // /articles/getting-started

// 📊 Reading time calculation (automatic)
echo "Reading time: " . $article->getAttribute('reading_time') . " minutes\n";

// 🏷️ Meta tags
$title = $article->getAttribute('title');
$excerpt = $article->getAttribute('excerpt');
$tags = $article->getAttribute('tags');

echo "<title>{$title}</title>\n";
echo "<meta name='description' content='{$excerpt}'>\n";
echo "<meta name='keywords' content='" . implode(', ', $tags) . "'>\n";
```

## 🚀 Building a Simple Blog

Here's a complete example of building a simple blog:

```php
<?php
// blog.php - Simple blog implementation

require_once 'vendor/autoload.php';

use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;

// Setup
$storage = new FileStorageService('./content');
Article::setStorageService($storage);

// Get published articles
$articles = Article::published();

// Sort by published date (newest first)
usort($articles, function($a, $b) {
    $aDate = $a->getAttribute('published_at') ?? '';
    $bDate = $b->getAttribute('published_at') ?? '';
    return strcmp($bDate, $aDate);
});

// Display blog
echo "<h1>My Blog</h1>\n";

foreach ($articles as $article) {
    $title = $article->getAttribute('title');
    $author = $article->getAttribute('author');
    $publishedAt = $article->getAttribute('published_at');
    $excerpt = $article->getAttribute('excerpt');
    $url = $article->getUrl();
    
    echo "<article>\n";
    echo "  <h2><a href='{$url}'>{$title}</a></h2>\n";
    echo "  <p>By {$author} on {$publishedAt}</p>\n";
    echo "  <p>{$excerpt}</p>\n";
    echo "  <a href='{$url}'>Read more →</a>\n";
    echo "</article>\n\n";
}
```

## 🎯 Benefits of Our Orbit Implementation

### **1. Git-Friendly Content**
- Content stored in Markdown files
- Version control your content
- Easy collaboration with writers
- Diff-friendly changes

### **2. Performance Optimized**
- File-based caching
- Lazy loading
- Optimized queries
- Multi-database support

### **3. Developer Experience**
- Familiar API (like Eloquent)
- Type-safe operations
- Rich query methods
- Comprehensive documentation

### **4. Flexibility**
- Hybrid storage (files + database)
- Custom fields support
- Extensible drivers
- Framework agnostic

## 📚 Next Steps

1. **Try the examples** - Copy and run the code above
2. **Create your content** - Add your own articles
3. **Build your blog** - Use the blog example as starting point
4. **Explore advanced features** - Check out the full documentation

## 🔗 Related Documentation

- [Hybrid Storage System](hybrid-storage.md)
- [Storage Quick Start](storage-quick-start.md)
- [Multi-Database Architecture](multi-database-architecture.md)

---

**MVA Bootstrap brings the elegance of Laravel Orbit to any PHP project with enhanced performance and type safety!**
