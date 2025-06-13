# Article Module

**Status:** 🚧 **Planned for future development**

## Overview

The Article module will provide content management functionality for the MVA Bootstrap application.

## Planned Features

### Core Functionality
- **Article CRUD** - Create, read, update, delete articles
- **Category Management** - Organize articles by categories
- **Tag System** - Tag articles for better organization
- **Content Editor** - Rich text editor for article content
- **Media Management** - Upload and manage images/files

### Advanced Features
- **SEO Optimization** - Meta tags, slugs, sitemap
- **Publishing Workflow** - Draft, review, publish states
- **Multi-language Support** - Localized article content
- **Search & Filtering** - Full-text search and advanced filters
- **Comments System** - User comments and moderation

### API Endpoints
- `GET /api/articles` - List articles with pagination
- `GET /api/articles/{id}` - Get single article
- `POST /api/articles` - Create new article
- `PUT /api/articles/{id}` - Update article
- `DELETE /api/articles/{id}` - Delete article
- `GET /api/categories` - List categories
- `GET /api/tags` - List tags

### Database Schema
```sql
-- Articles table
CREATE TABLE articles (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT,
    excerpt TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    category_id CHAR(36),
    author_id CHAR(36) NOT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Categories table
CREATE TABLE categories (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tags table
CREATE TABLE tags (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Article tags junction table
CREATE TABLE article_tags (
    article_id CHAR(36),
    tag_id CHAR(36),
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

## Implementation Plan

### Phase 1: Basic CRUD
- [ ] Article entity and repository
- [ ] Basic CRUD operations
- [ ] Simple list and detail views
- [ ] Admin interface for management

### Phase 2: Enhanced Features
- [ ] Category and tag system
- [ ] Rich text editor integration
- [ ] Image upload and management
- [ ] SEO optimization features

### Phase 3: Advanced Features
- [ ] Publishing workflow
- [ ] Multi-language support
- [ ] Search and filtering
- [ ] Comments system

## Dependencies

- **Core/User** - For author management
- **Core/Security** - For authentication and authorization
- **Language** - For multi-language support (optional)

## Configuration

```php
// config/article.php (future)
return [
    'pagination' => [
        'per_page' => 10,
        'max_per_page' => 100,
    ],
    'editor' => [
        'type' => 'tinymce', // or 'ckeditor', 'quill'
        'upload_path' => 'uploads/articles/',
    ],
    'seo' => [
        'auto_generate_slug' => true,
        'meta_description_length' => 160,
    ],
];
```

---

**Ready for future implementation when content management is needed!** 📝✨
