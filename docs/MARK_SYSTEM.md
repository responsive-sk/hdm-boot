# Mark System Documentation

## Overview

The Mark System is HDM Boot Protocol's super user management system, providing secure administrative access through a dedicated authentication flow separate from regular user authentication.

## Architecture

### Route-Based Authentication

The Mark System uses **route-based authentication** instead of email-based detection:

- **Mark System**: `/mark` route → `storage/mark.db`
- **User System**: `/login` route → `storage/user.db`

This ensures complete separation between administrative and user authentication flows.

### Database Architecture

```
storage/
├── mark.db        ← Mark users (super admins)
├── user.db        ← Application users
└── system.db      ← System data (cache, logs)
```

## Mark System Components

### 1. Mark Authentication Service
- **File**: `src/Modules/Core/Mark/Services/MarkAuthenticationService.php`
- **Purpose**: Handles mark user authentication using mark.db
- **Features**: 
  - Bcrypt password verification
  - Session validation
  - User status checking
  - Login tracking

### 2. Mark Repository
- **Interface**: `src/Modules/Core/Mark/Repository/MarkRepositoryInterface.php`
- **Implementation**: `src/Modules/Core/Mark/Repository/SqliteMarkRepository.php`
- **Purpose**: Data access layer for mark users
- **Database**: `storage/mark.db`

### 3. Mark Database Manager
- **File**: `src/Modules/Core/Database/MarkSqliteDatabaseManager.php`
- **Purpose**: Manages mark.db database connection and initialization
- **Features**:
  - Auto-creation of database and tables
  - Permission handling (777/666 for shared hosting)
  - Debug error reporting

### 4. Mark Actions
- **Login Page**: `src/Modules/Core/Mark/Actions/Web/MarkLoginPageAction.php`
- **Login Submit**: `src/Modules/Core/Mark/Actions/Web/MarkLoginSubmitAction.php`
- **Purpose**: Handle mark login workflow

## Routes

### Mark Authentication Routes

```php
GET  /mark              → MarkLoginPageAction (login page)
POST /mark/login        → MarkLoginSubmitAction (login form)
GET  /mark/dashboard    → Mark dashboard
POST /mark/logout       → Mark logout
```

### Security Features

- **Restricted Access Warning**: Login page displays security warnings
- **IP Logging**: All mark login attempts are logged with IP addresses
- **Session Management**: Secure session handling with 24-hour timeout
- **Branding**: Red color scheme to distinguish from user system

## Default Mark Users

### Production Credentials

```
Email: mark@responsive.sk
Password: mark123
Role: mark_admin
Status: active

Email: admin@example.com  
Password: admin123
Role: mark_admin
Status: active
```

### Database Schema

```sql
CREATE TABLE mark_users (
    id TEXT PRIMARY KEY,
    username TEXT,
    email TEXT UNIQUE,
    password_hash TEXT,
    role TEXT,
    status TEXT,
    last_login_at TEXT,
    login_count INTEGER,
    created_at TEXT,
    updated_at TEXT
);
```

## Configuration

### Mark Module Config
- **File**: `src/Modules/Core/Mark/config.php`
- **Services**: MarkRepositoryInterface, MarkAuthenticationService
- **Routes**: Mark authentication routes
- **Settings**: Session timeout, login attempts, lockout duration

### Database Factory
- **File**: `src/SharedKernel/Database/DatabaseManagerFactory.php`
- **Method**: `createMarkManager()`
- **Database**: `storage/mark.db`

## Security Considerations

### 1. Separation of Concerns
- Mark system is completely isolated from user system
- Separate databases prevent data leakage
- Different authentication flows reduce attack surface

### 2. Access Control
- Route-based authentication (`/mark` vs `/login`)
- Role-based permissions (mark_admin role)
- Session-based access control

### 3. Audit Trail
- All mark login attempts logged
- IP address tracking
- Login count and timestamp tracking

## Development

### Adding New Mark Users

```php
// Using MarkRepositoryInterface
$markRepo = $container->get(MarkRepositoryInterface::class);

$userData = [
    'username' => 'newmark',
    'email' => 'newmark@example.com',
    'password_hash' => password_hash('password', PASSWORD_DEFAULT),
    'role' => 'mark_admin',
    'status' => 'active'
];

$userId = $markRepo->create($userData);
```

### Mark Authentication Check

```php
// Using MarkAuthenticationService
$markAuth = $container->get(MarkAuthenticationService::class);

$markUser = $markAuth->authenticate($email, $password);
if ($markUser !== null) {
    // Authentication successful
    $session['mark_user_id'] = $markUser['id'];
    $session['mark_user_role'] = $markUser['role'];
}
```

## Deployment

### Production Deployment

1. **Upload Package**: Deploy production ZIP with mark.db included
2. **Verify Permissions**: Ensure storage/ has 777 and *.db files have 666
3. **Test Access**: Verify `/mark` route is accessible
4. **Test Authentication**: Test mark login with default credentials

### Shared Hosting Considerations

- **Permissions**: Mark system auto-creates directories with proper permissions
- **Database Path**: Uses absolute paths for database files
- **Error Handling**: Comprehensive error reporting for troubleshooting

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check `storage/mark.db` exists and has 666 permissions
   - Verify `storage/` directory has 777 permissions
   - Check error logs for detailed path information

2. **Authentication Failed**
   - Verify mark users exist in mark.db
   - Check password hashing (should be bcrypt)
   - Verify user status is 'active'

3. **Route Not Found**
   - Ensure Mark routes are registered in `config/routes.php`
   - Verify Mark module is loaded in core modules
   - Check web server configuration

### Debug Logging

Mark system includes comprehensive debug logging:

```
🔴 MARK LOGIN ATTEMPT: email@example.com
🔴 MARK AUTH: Starting authentication
🔴 MARK REPO: Finding user by email
🔴 MARK AUTH: Authentication successful
🔴 MARK LOGIN SUCCESS
```

## Migration from Previous Versions

### From Single Database (app.db)

1. **Backup Data**: Export mark users from app.db
2. **Deploy New Version**: Use three-database architecture
3. **Import Data**: Import mark users to mark.db
4. **Update Workflows**: Change mark admin workflows to use `/mark`
5. **Test Authentication**: Verify both user and mark systems work

### Breaking Changes

- Mark admins must use `/mark` route instead of `/login`
- Database structure completely changed
- Password hashing changed from Argon2ID to Bcrypt
- Storage moved from `var/storage/` to `storage/`

## Best Practices

1. **Use Route-Based Access**: Always direct mark admins to `/mark`
2. **Monitor Access**: Review mark login logs regularly
3. **Secure Credentials**: Use strong passwords for mark accounts
4. **Regular Backups**: Backup mark.db regularly
5. **Permission Audits**: Verify file permissions on deployment

## Integration

### With User System
- Mark and User systems are completely separate
- No shared authentication or session data
- Different databases and authentication flows

### With Other Modules
- Mark system can be extended with additional modules
- Use MarkAuthenticationService for mark user verification
- Implement mark-specific permissions and roles
