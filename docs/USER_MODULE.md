# User Module Documentation

The User module is a core module that provides comprehensive user management functionality with secure authentication support.

## 📦 Module Overview

### Purpose
The User module handles all user-related operations including:
- User registration and management
- Password hashing and verification
- Role-based access control
- Email verification system
- Password reset functionality
- User statistics and reporting

### Architecture
The module follows Clean Architecture principles with clear separation of concerns:

```
User Module
├── Domain/              # Business entities and value objects
│   ├── Entities/       # User entity with business logic
│   └── ValueObjects/   # UserId value object
├── Repository/         # Data access layer
├── Services/          # Business logic layer
├── Actions/           # HTTP request handlers
└── Infrastructure/    # External integrations
```

## 🏗 Domain Layer

### UserId Value Object

Type-safe UUID identifier for User entities with built-in validation.

```php
use HdmBoot\Modules\Core\User\Domain\ValueObjects\UserId;

// Generate new ID
$userId = UserId::generate();

// Create from string
$userId = UserId::fromString('123e4567-e89b-12d3-a456-426614174000');

// Get string value
$id = $userId->toString();
```

**Features:**
- UUID v4 generation for security
- Prevents user enumeration attacks
- Type safety and validation
- Immutable value object

### User Entity

Rich domain object with comprehensive business logic.

```php
use HdmBoot\Modules\Core\User\Domain\Entities\User;

// Create new user
$user = User::create(
    email: 'user@example.com',
    name: 'John Doe',
    password: 'SecurePassword123',
    role: 'user'
);

// Business operations
$user->changePassword('NewPassword123');
$user->verifyEmail();
$user->activate();
$user->recordLogin();
```

**Properties:**
- `id` - Unique UserId
- `email` - Email address (unique, validated)
- `name` - Full name
- `passwordHash` - Argon2ID hashed password
- `role` - User role (user, editor, admin)
- `status` - Account status (active, inactive, suspended, pending)
- `emailVerified` - Email verification status
- `loginCount` - Number of logins
- `lastLoginAt` - Last login timestamp
- `createdAt` / `updatedAt` - Timestamps

**Roles:**
- `user` - Regular user with basic permissions
- `editor` - Content editor with extended permissions
- `admin` - Administrator with full permissions

**Statuses:**
- `active` - Account is active and can be used
- `inactive` - Account is temporarily disabled
- `suspended` - Account is suspended due to violations
- `pending` - Account is pending activation

## 🗄 Repository Layer

The User Repository layer handles data persistence with following guarantees:

### Data Format
All repository methods that return user data (`findById`, `findByEmail`, etc.) always include these fields:
- `id`: Unique user identifier (UUID)
- `email`: User's email address
- `name`: User's full name
- `role`: User's role (e.g. 'admin', 'user')
- `status`: User's status (e.g. 'active')

Example response:
```php
[
    'id' => '550e8400-e29b-41d4-a716-446655440000',
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'role' => 'admin',
    'status' => 'active'
]
```

This consistent data format ensures reliability across the application.

### UserRepositoryInterface

Defines the contract for user data persistence operations.

```php
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findAll(array $filters = []): array;
    public function save(User $user): void;
    public function delete(UserId $id): void;
    public function emailExists(string $email): bool;
    public function findWithPagination(int $page, int $limit, array $filters): array;
    public function getStatistics(): array;
}
```

### SqliteUserRepository

SQLite implementation of the UserRepositoryInterface.

**Features:**
- Automatic database initialization
- Performance-optimized indexes
- Pagination support
- Advanced filtering and search
- Statistics and reporting
- Transaction support

**Database Schema:**
```sql
CREATE TABLE users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    status TEXT NOT NULL DEFAULT 'active',
    email_verified BOOLEAN NOT NULL DEFAULT 0,
    email_verification_token TEXT NULL,
    password_reset_token TEXT NULL,
    password_reset_expires TEXT NULL,
    last_login_at TEXT NULL,
    login_count INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
```

## 🔧 Service Layer

### UserService

Handles user business logic and orchestrates operations between repository and domain objects.

```php
use HdmBoot\Modules\Core\User\Services\UserService;

$userService = new UserService($userRepository);

// Create user
$user = $userService->createUser(
    email: 'user@example.com',
    name: 'John Doe',
    password: 'SecurePassword123',
    role: 'user'
);

// Authenticate user
$user = $userService->authenticate('user@example.com', 'SecurePassword123');

// Get users with pagination
$result = $userService->getUsersWithPagination(
    page: 1,
    limit: 20,
    filters: ['role' => 'admin']
);
```

**Key Methods:**
- `createUser()` - Create new user with validation
- `authenticate()` - Authenticate user with email/password
- `getUserById()` - Retrieve user by ID
- `getUserByEmail()` - Retrieve user by email
- `updateUser()` - Update user information
- `changePassword()` - Change user password
- `generatePasswordResetToken()` - Generate password reset token
- `verifyEmail()` - Verify user email with token
- `getStatistics()` - Get user statistics

## 🌐 HTTP Layer

### API Endpoints

#### List Users
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
            "id": "123e4567-e89b-12d3-a456-426614174000",
            "email": "user@example.com",
            "name": "John Doe",
            "role": "user",
            "status": "active",
            "email_verified": true,
            "last_login_at": "2025-06-08 14:30:00",
            "login_count": 5,
            "is_active": true,
            "is_admin": false,
            "is_editor": false,
            "created_at": "2025-06-01 10:00:00",
            "updated_at": "2025-06-08 14:30:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 1,
        "total_pages": 1,
        "has_next": false,
        "has_prev": false
    }
}
```

#### Create User
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

**Validation Rules:**
- `email` - Required, valid email format, unique
- `name` - Required, minimum 2 characters
- `password` - Required, minimum 8 characters, must contain uppercase, lowercase, and number
- `role` - Optional, must be one of: user, editor, admin

#### Get User by ID
```http
GET /api/users/{id}
```

#### User Statistics (Admin Only)
```http
GET /api/admin/users/statistics
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_users": 150,
        "by_status": {
            "active": 140,
            "inactive": 8,
            "suspended": 2
        },
        "by_role": {
            "user": 130,
            "editor": 15,
            "admin": 5
        },
        "email_verified": {
            "verified": 145,
            "unverified": 5
        },
        "recent_logins": 45
    }
}
```

## 🔒 Security Features

### Password Security
- **Argon2ID hashing** - Industry-standard password hashing
- **Strong password requirements** - Uppercase, lowercase, numbers
- **Password reset tokens** - Secure token-based password reset
- **Token expiration** - Reset tokens expire after 1 hour

### Email Security
- **Email verification** - Secure token-based email verification
- **Unique email constraint** - Prevents duplicate accounts
- **Email normalization** - Lowercase email storage

### Access Control
- **Role-based permissions** - Granular permission system
- **Status-based access** - Account status controls access
- **UUID identifiers** - Prevents user enumeration attacks

## 🧪 Testing

### Manual Testing Script

```bash
cd hdm-boot
php test-user.php
```

The test script validates:
- User creation (admin and regular users)
- User retrieval by ID and email
- Authentication with correct/incorrect passwords
- User statistics
- Pagination functionality

### API Testing

```bash
# List users
curl http://localhost:8001/api/users

# Get user statistics
curl http://localhost:8001/api/admin/users/statistics

# Create user (requires proper POST data handling)
curl -X POST http://localhost:8001/api/users \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test User","password":"Password123"}'
```

## 📊 Current Implementation Status

### ✅ Implemented Features
- User entity with rich domain logic
- Repository pattern with SQLite implementation
- User service with business logic
- Basic CRUD operations via API
- User listing with pagination and filters
- User creation with comprehensive validation
- User statistics and reporting
- Password hashing with Argon2ID
- Email verification token generation
- Password reset token generation
- Authentication with password verification

### 🔄 Planned Features
- User update action (PUT /api/users/{id})
- User deletion action (DELETE /api/users/{id})
- User status management actions
- Password change action
- Email verification action
- User role management
- Advanced search functionality
- User export/import functionality
- User activity logging
- Integration with Security module for JWT

## 🔧 Configuration

### Module Settings

```php
'settings' => [
    'user' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_symbols' => false,
        'email_verification_required' => false,
        'default_role' => 'user',
        'allowed_roles' => ['user', 'editor', 'admin'],
        'allowed_statuses' => ['active', 'inactive', 'suspended', 'pending'],
    ],
    'pagination' => [
        'default_limit' => 20,
        'max_limit' => 100,
    ],
],
```

### Environment Variables

```bash
# Database configuration
DATABASE_URL="sqlite:var/storage/app.db"

# User module specific settings
USER_PASSWORD_MIN_LENGTH=8
USER_EMAIL_VERIFICATION_REQUIRED=false
USER_DEFAULT_ROLE=user
```

## 🚀 Integration

### With Security Module
The User module is designed to integrate seamlessly with the Security module for:
- JWT token generation and validation
- Session management
- Advanced authorization rules
- API authentication middleware

### With Other Modules
- **Article Module** - User as author/editor
- **Audit Module** - User activity tracking
- **Notification Module** - User notifications

## 📚 Best Practices

### Domain Design
- Use value objects for type safety
- Keep business logic in domain entities
- Implement rich domain models

### Security
- Always hash passwords with Argon2ID
- Use UUIDs to prevent enumeration
- Validate all inputs at service layer
- Implement proper error handling

### Performance
- Use database indexes for queries
- Implement pagination for large datasets
- Cache frequently accessed data
- Optimize database queries

This User module provides a solid foundation for user management with enterprise-grade security and scalability features.
