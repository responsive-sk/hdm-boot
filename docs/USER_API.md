# User API Documentation

This document describes the User Management API endpoints that are currently implemented and functional.

## 🌐 User Management API

### Base URL
```
Development: http://localhost:8001
Production: https://your-domain.com
```

### Authentication
Currently, the User API endpoints are **publicly accessible** for development purposes. Authentication will be implemented in the Security module.

## 📊 Available Endpoints

### 1. List Users
```http
GET /api/users?page=1&limit=20&role=admin&status=active&search=john
```

**Query Parameters:**
- `page` - Page number (default: 1)
- `limit` - Items per page (default: 20, max: 100)
- `role` - Filter by role (user, editor, admin)
- `status` - Filter by status (active, inactive, suspended, pending)
- `search` - Search in name and email

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": "fc7303de-af9f-4743-92f7-0a8d44d03de3",
            "email": "admin@example.com",
            "name": "Admin User",
            "role": "admin",
            "status": "active",
            "email_verified": false,
            "last_login_at": "2025-06-08 14:30:04",
            "login_count": 1,
            "is_active": true,
            "is_admin": true,
            "is_editor": false,
            "created_at": "2025-06-08 14:30:03",
            "updated_at": "2025-06-08 14:30:04"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 2,
        "total_pages": 1,
        "has_next": false,
        "has_prev": false
    }
}
```

### 2. Create User
```http
POST /api/users
Content-Type: application/json

{
    "email": "user@example.com",
    "name": "John Doe",
    "password": "SecurePassword123",
    "role": "user"
}
```

**Request Body:**
- `email` (required) - Valid email address, must be unique
- `name` (required) - Full name, minimum 2 characters
- `password` (required) - Password with specific requirements
- `role` (optional) - User role: user, editor, admin (default: user)

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number

**Success Response (201):**
```json
{
    "success": true,
    "data": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "email": "user@example.com",
        "name": "John Doe",
        "role": "user",
        "status": "active",
        "email_verified": false,
        "last_login_at": null,
        "login_count": 0,
        "is_active": true,
        "is_admin": false,
        "is_editor": false,
        "created_at": "2025-06-08 14:30:00",
        "updated_at": "2025-06-08 14:30:00"
    },
    "message": "User created successfully"
}
```

**Validation Error Response (422):**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "email": ["Email is required"],
            "password": ["Password must be at least 8 characters long"]
        }
    }
}
```

### 3. Get User by ID
```http
GET /api/users/{id}
```

**Path Parameters:**
- `id` - User UUID

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "fc7303de-af9f-4743-92f7-0a8d44d03de3",
        "email": "admin@example.com",
        "name": "Admin User",
        "role": "admin",
        "status": "active",
        "email_verified": false,
        "last_login_at": "2025-06-08 14:30:04",
        "login_count": 1,
        "is_active": true,
        "is_admin": true,
        "is_editor": false,
        "created_at": "2025-06-08 14:30:03",
        "updated_at": "2025-06-08 14:30:04"
    }
}
```

**Not Found Response (404):**
```json
{
    "success": false,
    "error": {
        "code": "USER_NOT_FOUND",
        "message": "User not found"
    }
}
```

### 4. User Statistics (Admin Only)
```http
GET /api/admin/users/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_users": 2,
        "by_status": {
            "active": 2
        },
        "by_role": {
            "admin": 1,
            "user": 1
        },
        "email_verified": {
            "verified": 0,
            "unverified": 2
        },
        "recent_logins": 1
    }
}
```

## 🧪 Testing Examples

### Using cURL

```bash
# List all users
curl http://localhost:8001/api/users

# List users with pagination
curl "http://localhost:8001/api/users?page=1&limit=10"

# Filter users by role
curl "http://localhost:8001/api/users?role=admin"

# Search users
curl "http://localhost:8001/api/users?search=admin"

# Get user by ID
curl http://localhost:8001/api/users/fc7303de-af9f-4743-92f7-0a8d44d03de3

# Create new user
curl -X POST http://localhost:8001/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "name": "Test User",
    "password": "Password123",
    "role": "user"
  }'

# Get user statistics
curl http://localhost:8001/api/admin/users/statistics
```

### Using PHP Test Script

```bash
cd mva-bootstrap
php test-user.php
```

## 🔮 Planned Endpoints

The following endpoints are planned for future implementation:

```http
PUT /api/users/{id}                    # Update user
DELETE /api/users/{id}                 # Delete user
POST /api/users/{id}/activate          # Activate user
POST /api/users/{id}/deactivate        # Deactivate user
POST /api/users/{id}/suspend           # Suspend user
POST /api/users/{id}/change-password   # Change password
GET /api/users/verify-email/{token}    # Verify email
POST /api/users/{id}/send-verification # Send verification email
```

## 🚨 Error Handling

### HTTP Status Codes
- `200` - OK
- `201` - Created
- `400` - Bad Request
- `404` - Not Found
- `422` - Unprocessable Entity (Validation Error)
- `500` - Internal Server Error

### Error Response Format
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human readable error message",
        "details": {
            "field": ["Specific field error messages"]
        }
    }
}
```

### Common Error Codes
- `VALIDATION_ERROR` - Input validation failed
- `USER_NOT_FOUND` - Requested user not found
- `EMAIL_EXISTS` - Email address already in use
- `INTERNAL_ERROR` - Server error

## 🔒 Security Notes

### Current Security Features
- **Password Hashing** - Argon2ID algorithm
- **Input Validation** - Comprehensive validation on all inputs
- **UUID Identifiers** - Prevents user enumeration attacks
- **Email Uniqueness** - Prevents duplicate accounts

### Planned Security Features
- **JWT Authentication** - Token-based authentication
- **Role-based Authorization** - Endpoint access control
- **Rate Limiting** - Prevent abuse
- **CSRF Protection** - Cross-site request forgery protection

## 📚 Integration

### With Security Module
Once the Security module is implemented, these endpoints will require:
- JWT token authentication
- Role-based authorization
- Session management

### Example with Authentication (Future)
```http
GET /api/users
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

This User API provides a solid foundation for user management with comprehensive validation, error handling, and security features.


---

## 🔐 Security API Integration

The User API is now integrated with the **Security Module** that provides JWT-based authentication:

### Authentication Endpoints

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "Password123"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "expires_at": "2025-06-08 17:16:00",
        "user": {
            "id": "fc7303de-af9f-4743-92f7-0a8d44d03de3",
            "email": "admin@example.com",
            "name": "Admin User",
            "role": "admin",
            "status": "active"
        }
    },
    "message": "Login successful"
}
```

#### Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

#### Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {token}
```

#### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### Protected Endpoints

User management endpoints will be protected with JWT authentication in future versions:

```http
GET /api/users
Authorization: Bearer {token}
```

For complete Security API documentation, see [Security Module Documentation](SECURITY_MODULE.md).
