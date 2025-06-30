# Blog Module API Documentation

**RESTful API endpoints for HDM Boot Blog Module**

## 📋 API Overview

The Blog module provides a complete RESTful API for managing blog articles, categories, and tags. All endpoints follow REST conventions and return JSON responses.

## 🔗 Base URL

```
https://boot.responsive.sk/api/blog
```

## 🔐 Authentication

Most read operations are public. Write operations require JWT authentication:

```http
Authorization: Bearer <jwt-token>
```

## 📚 Articles API

### **List Articles**

```http
GET /api/blog/articles
```

**Query Parameters:**
- `page` (int, optional) - Page number (default: 1)
- `limit` (int, optional) - Items per page (default: 10, max: 50)
- `category` (string, optional) - Filter by category slug
- `tag` (string, optional) - Filter by tag
- `search` (string, optional) - Search in title and content
- `status` (string, optional) - Filter by status (published, draft)

**Response:**
```json
{
    "data": [
        {
            "id": "article-123",
            "title": "Getting Started with HDM Boot",
            "slug": "getting-started-hdm-boot",
            "excerpt": "Learn how to build applications with HDM Boot framework...",
            "content": "Full article content...",
            "author": {
                "id": "user-456",
                "name": "John Doe",
                "email": "john@example.com"
            },
            "category": {
                "id": "cat-789",
                "name": "Tutorials",
                "slug": "tutorials"
            },
            "tags": ["php", "framework", "tutorial"],
            "status": "published",
            "published_at": "2025-06-22T10:30:00Z",
            "created_at": "2025-06-20T15:45:00Z",
            "updated_at": "2025-06-22T10:30:00Z",
            "reading_time": 5,
            "view_count": 142
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 25,
        "total_pages": 3,
        "has_next": true,
        "has_prev": false
    },
    "links": {
        "self": "/api/blog/articles?page=1",
        "next": "/api/blog/articles?page=2",
        "prev": null,
        "first": "/api/blog/articles?page=1",
        "last": "/api/blog/articles?page=3"
    }
}
```

### **Get Single Article**

```http
GET /api/blog/articles/{id}
GET /api/blog/articles/slug/{slug}
```

**Response:**
```json
{
    "data": {
        "id": "article-123",
        "title": "Getting Started with HDM Boot",
        "slug": "getting-started-hdm-boot",
        "content": "Full article content in Markdown...",
        "excerpt": "Article excerpt...",
        "author": {
            "id": "user-456",
            "name": "John Doe",
            "email": "john@example.com"
        },
        "category": {
            "id": "cat-789",
            "name": "Tutorials",
            "slug": "tutorials"
        },
        "tags": ["php", "framework", "tutorial"],
        "status": "published",
        "published_at": "2025-06-22T10:30:00Z",
        "created_at": "2025-06-20T15:45:00Z",
        "updated_at": "2025-06-22T10:30:00Z",
        "reading_time": 5,
        "view_count": 142,
        "meta": {
            "word_count": 1250,
            "character_count": 7500,
            "seo_score": 85
        }
    }
}
```

### **Create Article** 🔐

```http
POST /api/blog/articles
Content-Type: application/json
Authorization: Bearer <jwt-token>
```

**Request Body:**
```json
{
    "title": "New Article Title",
    "content": "Article content in Markdown...",
    "excerpt": "Optional excerpt...",
    "category_id": "cat-789",
    "tags": ["php", "tutorial"],
    "status": "draft",
    "published_at": "2025-06-25T10:00:00Z"
}
```

**Response:**
```json
{
    "data": {
        "id": "article-124",
        "title": "New Article Title",
        "slug": "new-article-title",
        "status": "draft",
        "created_at": "2025-06-22T12:00:00Z"
    },
    "message": "Article created successfully"
}
```

### **Update Article** 🔐

```http
PUT /api/blog/articles/{id}
Content-Type: application/json
Authorization: Bearer <jwt-token>
```

**Request Body:** (same as create, all fields optional)

### **Delete Article** 🔐

```http
DELETE /api/blog/articles/{id}
Authorization: Bearer <jwt-token>
```

**Response:**
```json
{
    "message": "Article deleted successfully"
}
```

## 📂 Categories API

### **List Categories**

```http
GET /api/blog/categories
```

**Response:**
```json
{
    "data": [
        {
            "id": "cat-789",
            "name": "Tutorials",
            "slug": "tutorials",
            "description": "Step-by-step tutorials and guides",
            "article_count": 15,
            "created_at": "2025-01-15T10:00:00Z"
        }
    ]
}
```

### **Get Category**

```http
GET /api/blog/categories/{id}
GET /api/blog/categories/slug/{slug}
```

### **Create Category** 🔐

```http
POST /api/blog/categories
Content-Type: application/json
Authorization: Bearer <jwt-token>
```

**Request Body:**
```json
{
    "name": "New Category",
    "description": "Category description..."
}
```

## 🏷️ Tags API

### **List Tags**

```http
GET /api/blog/tags
```

**Response:**
```json
{
    "data": [
        {
            "name": "php",
            "slug": "php",
            "article_count": 25,
            "color": "#777bb4"
        },
        {
            "name": "framework",
            "slug": "framework", 
            "article_count": 18,
            "color": "#28a745"
        }
    ]
}
```

### **Get Tag Articles**

```http
GET /api/blog/tags/{slug}/articles
```

## 📊 Statistics API

### **Blog Statistics**

```http
GET /api/blog/stats
```

**Response:**
```json
{
    "data": {
        "total_articles": 25,
        "published_articles": 20,
        "draft_articles": 5,
        "total_categories": 6,
        "total_tags": 15,
        "total_views": 5420,
        "recent_activity": [
            {
                "type": "article_published",
                "article_id": "article-123",
                "timestamp": "2025-06-22T10:30:00Z"
            }
        ]
    }
}
```

## 🔍 Search API

### **Search Articles**

```http
GET /api/blog/search?q={query}
```

**Query Parameters:**
- `q` (string, required) - Search query
- `in` (string, optional) - Search in: title, content, tags (default: all)
- `limit` (int, optional) - Max results (default: 10)

**Response:**
```json
{
    "data": [
        {
            "id": "article-123",
            "title": "Getting Started with HDM Boot",
            "excerpt": "...highlighted search terms...",
            "relevance_score": 0.95,
            "match_type": "title"
        }
    ],
    "meta": {
        "query": "HDM Boot",
        "total_results": 5,
        "search_time": "0.045s"
    }
}
```

## ❌ Error Responses

### **Error Format**
```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid",
        "details": {
            "title": ["The title field is required"],
            "content": ["The content must be at least 10 characters"]
        }
    },
    "timestamp": "2025-06-22T12:00:00Z",
    "request_id": "req-123456"
}
```

### **HTTP Status Codes**
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## 📝 Rate Limiting

- **Read Operations**: 1000 requests/hour
- **Write Operations**: 100 requests/hour
- **Search Operations**: 500 requests/hour

Rate limit headers:
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## 🧪 Testing

### **Example cURL Requests**

```bash
# List articles
curl -X GET "https://boot.responsive.sk/api/blog/articles"

# Get article by slug
curl -X GET "https://boot.responsive.sk/api/blog/articles/slug/getting-started"

# Create article (requires auth)
curl -X POST "https://boot.responsive.sk/api/blog/articles" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-jwt-token" \
  -d '{
    "title": "Test Article",
    "content": "Test content...",
    "category_id": "cat-123"
  }'

# Search articles
curl -X GET "https://boot.responsive.sk/api/blog/search?q=HDM%20Boot"
```

## 📚 SDKs and Libraries

### **JavaScript/TypeScript**
```javascript
import { BlogApiClient } from '@hdm-boot/blog-client';

const client = new BlogApiClient('https://boot.responsive.sk/api/blog');
const articles = await client.articles.list({ limit: 5 });
```

### **PHP**
```php
use HdmBoot\BlogClient\BlogApiClient;

$client = new BlogApiClient('https://boot.responsive.sk/api/blog');
$articles = $client->articles()->list(['limit' => 5]);
```

---

**Blog Module API** - Complete RESTful API for blog functionality
