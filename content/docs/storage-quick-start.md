---
title: "Storage Quick Start"
slug: "storage-quick-start"
category: "getting-started"
order: 3
description: "Get up and running with MVA Bootstrap's hybrid storage system in 5 minutes"
difficulty: "beginner"
estimated_time: "5 minutes"
tags: ["quickstart", "storage", "tutorial"]
author: "MVA Bootstrap Team"
---

# Storage Quick Start

Get up and running with MVA Bootstrap's hybrid storage system in just 5 minutes!

## 🚀 What You'll Learn

- Create and save articles (file storage)
- Create and manage users (database storage)
- Query and retrieve data
- Understand the hybrid approach

## 📋 Prerequisites

- MVA Bootstrap installed
- PHP 8.1+ with SQLite support
- Basic PHP knowledge

## 🏃‍♂️ Quick Setup

### 1. Initialize Storage

```php
use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;
use MvaBootstrap\Modules\Core\Storage\Models\User;

// Setup storage service
$storageService = new FileStorageService('./content');

// Configure models
Article::setStorageService($storageService);
User::setStorageService($storageService);
```

### 2. Create Your First Article

```php
// Create article (stored in file)
$article = Article::create([
    'title' => 'My First Article',
    'slug' => 'my-first-article',
    'author' => 'Your Name',
    'published' => true,
    'category' => 'tutorial',
    'content' => '# Hello World\n\nThis is my first article!'
]);

echo "Article saved to: content/articles/my-first-article.md";
```

### 3. Create Your First User

```php
// Create user (stored in database)
$user = User::create([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'role' => 'admin'
]);

$user->setPassword('secure123');
$user->save();

echo "User saved to: content/database.sqlite";
```

### 4. Query Your Data

```php
// Query articles (from files)
$allArticles = Article::all();
$publishedArticles = Article::published();
$foundArticle = Article::find('my-first-article');

echo "Found " . count($allArticles) . " articles";

// Query users (from database)
$allUsers = User::all();
$adminUsers = User::byRole('admin');
$foundUser = User::findByUsername('john_doe');

echo "Found " . count($allUsers) . " users";
```

## 📁 File Structure After Setup

```
content/
├── articles/
│   └── my-first-article.md     # Your article
├── docs/
│   └── (documentation files)
└── database.sqlite             # Your users
```

## 📄 Generated Article File

```markdown
content/articles/my-first-article.md
---
title: "My First Article"
slug: "my-first-article"
author: "Your Name"
published: true
category: "tutorial"
reading_time: 1
published_at: "2024-01-01 12:00:00"
---

# Hello World

This is my first article!
```

## 🗄️ Database Schema

```sql
-- content/database.sqlite
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    email TEXT,
    first_name TEXT,
    last_name TEXT,
    role TEXT,
    password_hash TEXT,
    created_at TEXT,
    updated_at TEXT
);
```

## 🔍 Common Operations

### Article Operations

```php
// Create
$article = Article::create(['title' => 'New Post', 'slug' => 'new-post']);

// Read
$article = Article::find('new-post');
$articles = Article::all();
$published = Article::published();

// Update
$article->setAttribute('title', 'Updated Title');
$article->save();

// Delete
$article->delete();
```

### User Operations

```php
// Create
$user = User::create(['username' => 'jane', 'email' => 'jane@example.com']);

// Read
$user = User::find(1);
$user = User::findByEmail('jane@example.com');
$users = User::all();

// Update
$user->setAttribute('role', 'admin');
$user->save();

// Authentication
$user->setPassword('newpassword');
if ($user->verifyPassword('newpassword')) {
    $user->recordLogin();
}

// Delete
$user->delete();
```

## 🎯 Key Concepts

### File Storage (Articles)
- **Storage**: Markdown files with YAML front-matter
- **Location**: `content/articles/`
- **Benefits**: Git-friendly, human-readable, no database
- **Use for**: Content, documentation, settings

### Database Storage (Users)
- **Storage**: SQLite database
- **Location**: `content/database.sqlite`
- **Benefits**: Queries, relations, transactions
- **Use for**: Users, sessions, logs, analytics

### Unified API
- Same methods work for both storage types
- `create()`, `find()`, `all()`, `save()`, `delete()`
- Consistent developer experience

## 🧪 Test Your Setup

Create a test file to verify everything works:

```php
<?php
// test_setup.php

require_once 'vendor/autoload.php';

use MvaBootstrap\Modules\Core\Storage\Services\FileStorageService;
use MvaBootstrap\Modules\Core\Storage\Models\Article;
use MvaBootstrap\Modules\Core\Storage\Models\User;

// Setup
$storage = new FileStorageService('./content');
Article::setStorageService($storage);
User::setStorageService($storage);

// Test article
$article = Article::create([
    'title' => 'Test Article',
    'slug' => 'test',
    'content' => 'Hello World!'
]);

// Test user
$user = User::create([
    'username' => 'test',
    'email' => 'test@example.com'
]);

echo "✅ Article created: " . $article->getAttribute('title') . "\n";
echo "✅ User created: " . $user->getAttribute('username') . "\n";
echo "🎉 Setup successful!\n";
```

Run: `php test_setup.php`

## 🔧 Troubleshooting

### Permission Issues
```bash
# Make content directory writable
chmod 755 content/
chmod 644 content/articles/
chmod 644 content/database.sqlite
```

### SQLite Issues
```php
// Check SQLite support
if (!extension_loaded('sqlite3')) {
    echo "SQLite extension not loaded";
}

// Check database file
if (!file_exists('content/database.sqlite')) {
    echo "Database file not created";
}
```

### File Issues
```php
// Check content directory
if (!is_dir('content')) {
    mkdir('content', 0755, true);
}

if (!is_writable('content')) {
    echo "Content directory not writable";
}
```

## 🎓 Next Steps

1. **Try the [Orbit-Style Example](orbit-example.md)** - Complete working example
2. **Read the full [Hybrid Storage Guide](hybrid-storage.md)**
3. **Explore [Multi-Database Architecture](multi-database-architecture.md)**
4. **Learn about [File Models](file-models.md) in detail**
5. **Check out [Database Models](database-models.md)**

## 💡 Pro Tips

- Use **files** for content that changes infrequently
- Use **database** for data that needs complex queries
- **Cache** is automatic for files, manual for database
- **Backup** files with git, database with dumps
- **Scale** by optimizing each storage type separately

---

You're now ready to build amazing applications with MVA Bootstrap's hybrid storage system! 🚀
