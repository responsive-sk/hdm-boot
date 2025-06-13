# Module Configuration Guide

## Overview

This guide provides comprehensive instructions for configuring modules in the MVA Bootstrap project. It covers the standardized configuration format, best practices, and real-world examples.

## Configuration File Structure

Every module must have a `config.php` file in its root directory that returns a configuration array following the standardized format.

### Basic Structure

```php
<?php
// modules/{Category}/{ModuleName}/config.php

declare(strict_types=1);

return [
    // Module metadata
    'name' => 'ModuleName',
    'version' => '1.0.0',
    'description' => 'Module description',
    
    // Configuration sections
    'dependencies' => [...],
    'settings' => [...],
    'services' => [...],
    // ... other sections
];
```

## Configuration Sections

### 1. Module Metadata

#### Required Fields
```php
'name' => 'User',                    // Module identifier
'version' => '1.0.0',               // Semantic version
'description' => 'User management module with authentication and profiles',
```

#### Optional Fields
```php
'author' => 'MVA Bootstrap Team',    // Module author
'license' => 'MIT',                 // License type
'homepage' => 'https://example.com', // Module homepage
'repository' => 'https://github.com/example/module',
```

### 2. Dependencies

Declare other modules that this module requires:

```php
'dependencies' => [
    'User',        // Requires User module
    'Security',    // Requires Security module
],
```

#### Dependency Guidelines
- **Minimize dependencies**: Only include essential dependencies
- **Avoid circular dependencies**: Design clear module hierarchy
- **Use semantic names**: Use clear, descriptive module names

### 3. Settings

Module-specific configuration settings:

```php
'settings' => [
    // Basic settings
    'enabled' => true,
    'debug' => false,
    
    // Feature flags
    'registration_enabled' => true,
    'email_verification_required' => false,
    
    // Limits and timeouts
    'password_min_length' => 8,
    'session_timeout' => 3600,
    'max_login_attempts' => 5,
    
    // External services
    'api_endpoint' => $_ENV['MODULE_API_ENDPOINT'] ?? 'https://api.example.com',
    'api_key' => $_ENV['MODULE_API_KEY'] ?? null,
    
    // Cache settings
    'cache_enabled' => true,
    'cache_ttl' => 3600,
    
    // Localization
    'default_locale' => 'en_US',
    'supported_locales' => [
        'en_US' => 'English (United States)',
        'sk_SK' => 'Slovenčina (Slovensko)',
    ],
],
```

#### Settings Best Practices
- **Use environment variables** for sensitive data
- **Provide default values** for all settings
- **Validate ranges** for numeric values
- **Document each setting** with comments

### 4. Service Definitions

DI Container service definitions:

```php
'services' => [
    // Simple autowiring
    UserService::class => \DI\autowire(),
    
    // Factory function
    UserRepository::class => function (Container $container): UserRepository {
        return new SqliteUserRepository(
            $container->get(\PDO::class)
        );
    },
    
    // Interface binding
    UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
        return $container->get(UserRepository::class);
    },
    
    // Singleton
    CacheManager::class => \DI\singleton(\DI\autowire()),
    
    // Value injection
    'user.settings' => function (Container $container): array {
        $module = $container->get(ModuleManager::class)->getModule('User');
        return $module->getSettings();
    },
],
```

### 5. Public Services

Services that other modules can use:

```php
'public_services' => [
    // Interface => Implementation
    UserServiceInterface::class => UserService::class,
    AuthenticationServiceInterface::class => AuthenticationService::class,
],
```

### 6. Event System

#### Published Events
Events that this module publishes:

```php
'published_events' => [
    'user.created' => 'Fired when a new user is created',
    'user.updated' => 'Fired when user profile is updated',
    'user.deleted' => 'Fired when user account is deleted',
    'user.login' => 'Fired when user logs in successfully',
    'user.logout' => 'Fired when user logs out',
    'user.password.changed' => 'Fired when user changes password',
],
```

#### Event Subscriptions
Events that this module listens to:

```php
'event_subscriptions' => [
    'security.login_success' => [UserLoginListener::class, 'handle'],
    'email.sent' => [EmailTrackingListener::class, 'handle'],
],
```

### 7. API Endpoints

REST API endpoints provided by this module:

```php
'api_endpoints' => [
    // CRUD operations
    'GET /api/users' => 'List all users with pagination',
    'GET /api/users/{id}' => 'Get specific user by ID',
    'POST /api/users' => 'Create new user account',
    'PUT /api/users/{id}' => 'Update existing user',
    'DELETE /api/users/{id}' => 'Delete user account',
    
    // Special endpoints
    'GET /profile' => 'Get current user profile',
    'PUT /profile' => 'Update current user profile',
    'POST /api/users/{id}/activate' => 'Activate user account',
    'POST /api/users/{id}/deactivate' => 'Deactivate user account',
],
```

### 8. Middleware

Middleware provided by this module:

```php
'middleware' => [
    AuthenticationMiddleware::class => 'Validates user authentication tokens',
    AuthorizationMiddleware::class => 'Checks user permissions for resources',
    RateLimitMiddleware::class => 'Limits API request rate per user',
],
```

### 9. Permissions

Security permissions defined by this module:

```php
'permissions' => [
    // Basic permissions
    'user.read' => 'Read user data and profiles',
    'user.write' => 'Create and update user accounts',
    'user.delete' => 'Delete user accounts',
    
    // Administrative permissions
    'user.admin' => 'Full administrative access to user management',
    'user.impersonate' => 'Impersonate other users',
    
    // Self-service permissions
    'profile.read' => 'Read own profile information',
    'profile.write' => 'Update own profile information',
],
```

### 10. Database Tables

Database tables managed by this module:

```php
'database_tables' => [
    'users',                    // Main users table
    'user_sessions',           // User session data
    'user_preferences',        // User preference settings
    'user_activity_log',       // User activity tracking
],
```

### 11. Module Status

Implementation status and roadmap:

```php
'status' => [
    'implemented' => [
        'User CRUD operations',
        'Profile management',
        'Session handling',
        'SQLite repository implementation',
        'Basic authentication',
        'Password hashing',
    ],
    
    'planned' => [
        'Email verification',
        'Password reset functionality',
        'Two-factor authentication',
        'User roles and permissions',
        'Social login integration',
        'User activity analytics',
    ],
],
```

### 12. Advanced Configuration

#### Routes
Route definitions for automatic registration:

```php
'routes' => [
    [
        'method' => 'GET',
        'pattern' => '/api/users',
        'handler' => ListUsersAction::class,
        'middleware' => [AuthenticationMiddleware::class],
        'name' => 'users.list',
    ],
    [
        'method' => 'POST',
        'pattern' => '/api/users',
        'handler' => CreateUserAction::class,
        'middleware' => [AuthenticationMiddleware::class, AuthorizationMiddleware::class],
        'name' => 'users.create',
    ],
],
```

#### Commands
CLI commands provided by this module:

```php
'commands' => [
    'user:create' => CreateUserCommand::class,
    'user:list' => ListUsersCommand::class,
    'user:delete' => DeleteUserCommand::class,
    'user:cleanup' => CleanupInactiveUsersCommand::class,
],
```

#### Assets
Static assets provided by this module:

```php
'assets' => [
    'css' => [
        'assets/css/user-profile.css',
        'assets/css/user-dashboard.css',
    ],
    'js' => [
        'assets/js/user-profile.js',
        'assets/js/password-strength.js',
    ],
    'images' => [
        'assets/images/default-avatar.png',
    ],
],
```

#### Translations
Translation files for internationalization:

```php
'translations' => [
    'en_US' => 'translations/en_US.php',
    'sk_SK' => 'translations/sk_SK.php',
    'cs_CZ' => 'translations/cs_CZ.php',
    'de_DE' => 'translations/de_DE.php',
],
```

### 13. Lifecycle Hooks

#### Initialization Hook
Custom initialization logic:

```php
'initialize' => function (): void {
    // Create required directories
    if (!file_exists('var/uploads/avatars')) {
        mkdir('var/uploads/avatars', 0755, true);
    }
    
    // Set default session settings
    if (!isset($_SESSION['user_preferences'])) {
        $_SESSION['user_preferences'] = [
            'theme' => 'light',
            'language' => 'en_US',
        ];
    }
    
    // Register global functions
    if (!function_exists('current_user')) {
        function current_user(): ?array {
            return $_SESSION['user'] ?? null;
        }
    }
},
```

#### Health Check Hook
Custom health monitoring:

```php
'health_check' => function (): array {
    $health = [
        'database_connection' => false,
        'user_table_exists' => false,
        'uploads_writable' => false,
        'last_check' => date('Y-m-d H:i:s'),
    ];
    
    try {
        // Test database connection
        $pdo = new PDO('sqlite:var/storage/database.sqlite');
        $health['database_connection'] = true;
        
        // Check if users table exists
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $health['user_table_exists'] = $result->fetch() !== false;
        
        // Check uploads directory
        $health['uploads_writable'] = is_writable('var/uploads/avatars');
        
    } catch (Exception $e) {
        $health['error'] = $e->getMessage();
    }
    
    return $health;
},
```

## Real-World Examples

### User Module Configuration

```php
<?php
// modules/Core/User/config.php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use MvaBootstrap\Modules\Core\User\Repositories\UserRepository;

return [
    'name' => 'User',
    'version' => '1.0.0',
    'description' => 'User management module with authentication and profiles',
    
    'dependencies' => [],
    
    'settings' => [
        'registration_enabled' => true,
        'email_verification_required' => false,
        'password_min_length' => 8,
        'session_timeout' => 3600,
        'max_login_attempts' => 5,
    ],
    
    'services' => [
        UserService::class => \DI\autowire(),
        UserRepository::class => function (Container $container): UserRepository {
            return new UserRepository($container->get(\PDO::class));
        },
    ],
    
    'published_events' => [
        'user.created' => 'User account was created',
        'user.updated' => 'User profile was updated',
    ],
    
    'api_endpoints' => [
        'GET /api/users' => 'List users',
        'POST /api/users' => 'Create user',
    ],
    
    'permissions' => [
        'user.read' => 'Read user data',
        'user.write' => 'Create and update users',
    ],
];
```

### Language Module Configuration

```php
<?php
// modules/Core/Language/config.php

declare(strict_types=1);

return [
    'name' => 'Language',
    'version' => '1.0.0',
    'description' => 'Internationalization and localization module',
    
    'dependencies' => [],
    
    'settings' => [
        'default_locale' => 'en_US',
        'fallback_locale' => 'en_US',
        'cache_translations' => true,
        'supported_locales' => [
            'en_US' => 'English (United States)',
            'sk_SK' => 'Slovenčina (Slovensko)',
        ],
    ],
    
    'services' => [
        TranslationService::class => \DI\autowire(),
        LocaleMiddleware::class => \DI\autowire(),
    ],
    
    'published_events' => [
        'language.locale_changed' => 'User changed their locale',
        'language.translation_added' => 'New translation was added',
    ],
    
    'middleware' => [
        LocaleMiddleware::class => 'Detects and sets user locale',
    ],
    
    'api_endpoints' => [
        'POST /api/translate' => 'Translate text',
        'GET /api/language' => 'Get language settings',
    ],
];
```

## Configuration Validation

### Automatic Validation

The system automatically validates:
- **Required fields**: name, version
- **Data types**: arrays, strings, booleans
- **Version format**: semantic versioning (x.y.z)
- **Dependencies**: valid module names

### Custom Validation

Add custom validation rules:

```php
'config_schema' => [
    'type' => 'object',
    'properties' => [
        'settings' => [
            'type' => 'object',
            'properties' => [
                'password_min_length' => [
                    'type' => 'integer',
                    'minimum' => 6,
                    'maximum' => 128,
                ],
                'session_timeout' => [
                    'type' => 'integer',
                    'minimum' => 300,
                    'maximum' => 86400,
                ],
            ],
            'required' => ['password_min_length'],
        ],
    ],
    'required' => ['name', 'version', 'settings'],
],
```

## Environment Integration

### Environment Variables

Use environment variables for configuration:

```php
'settings' => [
    'debug' => $_ENV['APP_DEBUG'] === 'true',
    'api_key' => $_ENV['MODULE_API_KEY'] ?? null,
    'database_url' => $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/database.sqlite',
    'cache_driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
],
```

### Environment-Specific Configs

Create environment-specific configurations:

```php
// Load base configuration
$config = require __DIR__ . '/config.base.php';

// Override for specific environments
if ($_ENV['APP_ENV'] === 'production') {
    $config['settings']['debug'] = false;
    $config['settings']['cache_ttl'] = 7200;
} elseif ($_ENV['APP_ENV'] === 'development') {
    $config['settings']['debug'] = true;
    $config['settings']['cache_ttl'] = 60;
}

return $config;
```

This guide provides comprehensive coverage of module configuration in the MVA Bootstrap project, enabling developers to create well-structured, maintainable modules that integrate seamlessly with the system.
