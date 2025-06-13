# API Documentation

This document describes the REST API endpoints available in the MVA Bootstrap Application.

## 🌐 API Overview

The MVA Bootstrap Application provides a RESTful API with JSON responses. The API is designed to be:
- **RESTful** - Following REST principles and conventions
- **Consistent** - Standardized request/response formats
- **Secure** - Authentication and authorization protected
- **Versioned** - API versioning for backward compatibility
- **Well-Documented** - Comprehensive documentation and examples

### Base URL

```
Development: http://localhost:8001
Production: https://your-domain.com
```

### Content Type

All API requests and responses use JSON:
```
Content-Type: application/json
```

## 📊 Monitoring API

### Application Status
Get current application status and system information.

```http
GET /api/status
```

#### Response
```json
{
    "status": "OK",
    "timestamp": 1686557452,
    "version": "1.0.0",
    "app": {
        "name": "MVA Bootstrap",
        "environment": "production",
        "debug": false,
        "timezone": "UTC"
    },
    "php": {
        "version": "8.3.0",
        "memory_limit": "128M",
        "timezone": "UTC"
    }
}
```

### Health Check Endpoints

Multiple health check endpoints are available for different monitoring systems:

```http
GET /_status    # Primary health check
GET /health     # Alternative health check
GET /healthz    # Kubernetes-style health check
GET /ping       # Simple ping endpoint
```

#### Response
```json
{
    "status": "healthy",
    "timestamp": 1686557452
}
```

## 🔐 Authentication

### Current Status
- **Status**: Not yet implemented
- **Planned**: JWT-based authentication

### Planned Authentication Flow

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 3600,
    "user": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "email": "user@example.com",
        "role": "user"
    }
}
```

### Using Authentication Token

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

## 📊 Current API Endpoints

### System Status

#### Get API Status
```http
GET /api/status
```

**Response:**
```json
{
    "status": "ok",
    "app": "MVA Bootstrap",
    "version": "1.0.0",
    "timestamp": "2025-06-08T14:02:57+00:00",
    "environment": "dev"
}
```

#### Get Application Information
```http
GET /api/info
```

**Response:**
```json
{
    "app": {
        "name": "MVA Bootstrap Application",
        "version": "1.0.0",
        "description": "Modular PHP application with secure paths"
    },
    "features": {
        "modular_architecture": true,
        "secure_paths": true,
        "dependency_injection": true,
        "environment_config": true,
        "logging": true,
        "sessions": true,
        "database": true
    },
    "security": {
        "allowed_directories": [
            "var", "logs", "cache", "uploads", "storage", "sessions"
        ],
        "path_traversal_protection": true,
        "file_upload_validation": true
    },
    "timestamp": "2025-06-08T14:02:57+00:00"
}
```

### Development & Testing

#### Path Security Tests
```http
GET /test/paths
```

**Note**: Only available in development environment (`APP_ENV=dev`)

**Response:**
```json
{
    "title": "Path Security Tests",
    "timestamp": "2025-06-08T14:03:08+00:00",
    "tests": [
        {
            "test": "Valid path",
            "result": "PASS",
            "path": "/var/test.txt"
        },
        {
            "test": "Path traversal protection",
            "result": "PASS",
            "blocked": "Path traversal detected in '../../../etc/passwd'"
        },
        {
            "test": "Forbidden path protection",
            "result": "PASS",
            "blocked": "Access to 'config/container.php' is forbidden"
        }
    ],
    "allowed_directories": ["var", "logs", "cache", "uploads", "storage"]
}
```

#### Environment Information
```http
GET /test/env
```

**Note**: Only available in development environment

**Response:**
```json
{
    "php_version": "8.1.0",
    "app_env": "dev",
    "app_debug": true,
    "loaded_extensions": ["Core", "PDO", "json", "..."],
    "memory_limit": "128M",
    "max_execution_time": "30"
}
```

## 🔮 Planned API Endpoints

### Authentication

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {refresh_token}
```

### User Management

#### Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

#### Update User Profile
```http
PUT /api/user
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com"
}
```

#### Change Password
```http
POST /api/user/password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "oldpassword",
    "new_password": "newpassword"
}
```

### User Administration (Admin Only)

#### List Users
```http
GET /api/admin/users?page=1&limit=20
Authorization: Bearer {admin_token}
```

#### Get User by ID
```http
GET /api/admin/users/{id}
Authorization: Bearer {admin_token}
```

#### Create User
```http
POST /api/admin/users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "password123",
    "role": "user"
}
```

#### Update User
```http
PUT /api/admin/users/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "name": "Jane Smith",
    "role": "admin"
}
```

#### Delete User
```http
DELETE /api/admin/users/{id}
Authorization: Bearer {admin_token}
```

### Article Management (Optional Module)

#### List Articles
```http
GET /api/articles?page=1&limit=10&status=published
```

#### Get Article by ID
```http
GET /api/articles/{id}
```

#### Create Article (Authenticated)
```http
POST /api/articles
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Article Title",
    "content": "Article content...",
    "status": "draft"
}
```

#### Update Article
```http
PUT /api/articles/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Updated Title",
    "content": "Updated content...",
    "status": "published"
}
```

#### Delete Article
```http
DELETE /api/articles/{id}
Authorization: Bearer {token}
```

## 📝 Response Formats

### Success Response

```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation completed successfully"
}
```

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "email": ["Email is required"],
            "password": ["Password must be at least 8 characters"]
        }
    }
}
```

### Pagination Response

```json
{
    "success": true,
    "data": [
        // Array of items
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "total_pages": 8,
        "has_next": true,
        "has_prev": false
    }
}
```

## 🚨 Error Codes

### HTTP Status Codes

- `200` - OK
- `201` - Created
- `204` - No Content
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity
- `500` - Internal Server Error

### Application Error Codes

- `VALIDATION_ERROR` - Input validation failed
- `AUTHENTICATION_FAILED` - Invalid credentials
- `AUTHORIZATION_FAILED` - Insufficient permissions
- `RESOURCE_NOT_FOUND` - Requested resource not found
- `DUPLICATE_RESOURCE` - Resource already exists
- `RATE_LIMIT_EXCEEDED` - Too many requests
- `MAINTENANCE_MODE` - Application in maintenance

## 🔧 Request/Response Examples

### Successful Resource Creation

**Request:**
```http
POST /api/articles
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "title": "Getting Started with MVA Bootstrap",
    "content": "This article explains how to use the MVA Bootstrap Application...",
    "status": "published"
}
```

**Response:**
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
    "success": true,
    "data": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "title": "Getting Started with MVA Bootstrap",
        "content": "This article explains how to use the MVA Bootstrap Application...",
        "status": "published",
        "author_id": "456e7890-e89b-12d3-a456-426614174000",
        "created_at": "2025-06-08T14:30:00+00:00",
        "updated_at": "2025-06-08T14:30:00+00:00"
    },
    "message": "Article created successfully"
}
```

### Validation Error

**Request:**
```http
POST /api/articles
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "title": "",
    "content": "Short"
}
```

**Response:**
```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "title": ["Title is required"],
            "content": ["Content must be at least 10 characters"],
            "status": ["Status is required"]
        }
    }
}
```

## 🧪 Testing the API

### Using cURL

```bash
# Test API status
curl -X GET http://localhost:8001/api/status

# Test with authentication (when implemented)
curl -X GET http://localhost:8001/api/user \
  -H "Authorization: Bearer your-jwt-token"

# Test POST request
curl -X POST http://localhost:8001/api/articles \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer your-jwt-token" \
  -d '{"title":"Test Article","content":"Test content","status":"draft"}'
```

### Using Postman

1. Import the API collection (when available)
2. Set environment variables for base URL and tokens
3. Use the pre-configured requests

### API Testing Tools

- **Postman** - GUI-based API testing
- **Insomnia** - Alternative to Postman
- **HTTPie** - Command-line HTTP client
- **curl** - Standard command-line tool

## 📚 Additional Resources

- [REST API Best Practices](https://restfulapi.net/)
- [HTTP Status Codes](https://httpstatuses.com/)
- [JWT.io](https://jwt.io/) - JWT token debugging
- [JSON Schema](https://json-schema.org/) - API validation

This API documentation will be updated as new endpoints are implemented and existing ones are modified.
