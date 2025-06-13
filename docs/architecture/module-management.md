# Module Management System

## Overview

The MVA Bootstrap project implements a comprehensive Module Management System that provides standardized module configuration, automatic discovery, dependency resolution, and centralized lifecycle management. This system ensures consistent module structure and enables loose coupling between modules.

## Core Components

### 1. ModuleInterface

The `ModuleInterface` defines the contract that all modules must implement for proper module isolation, configuration management, and communication.

```php
interface ModuleInterface
{
    // Basic module information
    public function getName(): string;
    public function getVersion(): string;
    public function getDescription(): string;
    
    // Configuration management
    public function getConfig(): array;
    public function getSettings(): array;
    public function validateConfig(): array;
    
    // Dependency management
    public function getDependencies(): array;
    
    // Service integration
    public function getServiceDefinitions(): array;
    public function getPublicServices(): array;
    
    // Event system integration
    public function getPublishedEvents(): array;
    public function getEventSubscriptions(): array;
    
    // API and middleware
    public function getApiEndpoints(): array;
    public function getMiddleware(): array;
    
    // Security and database
    public function getPermissions(): array;
    public function getDatabaseTables(): array;
    
    // Lifecycle management
    public function initialize(): void;
    public function isInitialized(): bool;
    public function getHealthStatus(): array;
}
```

### 2. ModuleManager

The `ModuleManager` provides centralized management of module loading, configuration, and lifecycle.

#### Key Features:
- **✅ Automatic Discovery**: Finds all modules with config.php files
- **✅ Dependency Resolution**: Initializes modules in correct order
- **✅ Configuration Validation**: Validates module configurations
- **✅ Health Monitoring**: Tracks module status and health
- **✅ Statistics Tracking**: Provides metrics and analytics

#### Core Methods:
```php
class ModuleManager
{
    public function discoverModules(): void;
    public function registerModule(ModuleInterface $module): void;
    public function initializeModules(): void;
    public function getModule(string $name): ?ModuleInterface;
    public function getModuleConfig(string $moduleName): array;
    public function getStatistics(): array;
    public function getModulesHealthStatus(): array;
}
```

### 3. GenericModule

The `GenericModule` is the default implementation of `ModuleInterface` that loads configuration from module config.php files.

```php
class GenericModule implements ModuleInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly array $config
    ) {}
    
    // Implements all ModuleInterface methods
    // Loads configuration from config array
    // Provides default implementations
}
```

## Module Configuration Structure

### Standard Configuration Format

Every module must have a `config.php` file in its root directory with the following structure:

```php
<?php
// modules/{Category}/{ModuleName}/config.php

return [
    // === MODULE METADATA ===
    'name' => 'ModuleName',
    'version' => '1.0.0',
    'description' => 'Brief description of module functionality',
    'author' => 'Author Name',
    'license' => 'MIT',
    
    // === MODULE DEPENDENCIES ===
    'dependencies' => [
        'RequiredModule1',
        'RequiredModule2',
    ],
    
    // === MODULE SETTINGS ===
    'settings' => [
        'enabled' => true,
        'debug' => false,
        'custom_setting' => 'value',
    ],
    
    // === SERVICE DEFINITIONS ===
    'services' => [
        ServiceInterface::class => ServiceImplementation::class,
        AnotherService::class => \DI\autowire(),
    ],
    
    // === PUBLIC SERVICES ===
    'public_services' => [
        PublicInterface::class => PublicImplementation::class,
    ],
    
    // === EVENT SYSTEM ===
    'published_events' => [
        'module.event_name' => 'Event description',
    ],
    
    'event_subscriptions' => [
        'other.event_name' => [ListenerClass::class, 'handle'],
    ],
    
    // === API ENDPOINTS ===
    'api_endpoints' => [
        'GET /api/module/resource' => 'Description',
        'POST /api/module/resource' => 'Description',
    ],
    
    // === MIDDLEWARE ===
    'middleware' => [
        MiddlewareClass::class => 'Middleware description',
    ],
    
    // === PERMISSIONS ===
    'permissions' => [
        'module.read' => 'Read access',
        'module.write' => 'Write access',
    ],
    
    // === DATABASE ===
    'database_tables' => [
        'module_table1',
        'module_table2',
    ],
    
    // === MODULE STATUS ===
    'status' => [
        'implemented' => [
            'Feature 1',
            'Feature 2',
        ],
        'planned' => [
            'Future Feature 1',
            'Future Feature 2',
        ],
    ],
    
    // === INITIALIZATION ===
    'initialize' => function (): void {
        // Custom initialization logic
    },
    
    // === HEALTH CHECK ===
    'health_check' => function (): array {
        return [
            'custom_check' => true,
            'last_check' => date('Y-m-d H:i:s'),
        ];
    },
];
```

## Implementation Guide

### Creating a New Module

#### Step 1: Create Module Directory Structure
```
modules/
├── Core/
│   └── YourModule/
│       ├── config.php
│       ├── Application/
│       │   ├── Actions/
│       │   ├── Commands/
│       │   └── Queries/
│       ├── Domain/
│       │   ├── Models/
│       │   ├── Services/
│       │   ├── Events/
│       │   └── Contracts/
│       └── Infrastructure/
│           ├── Repositories/
│           ├── Listeners/
│           └── Middleware/
```

#### Step 2: Create Module Configuration
```php
<?php
// modules/Core/YourModule/config.php

declare(strict_types=1);

return [
    'name' => 'YourModule',
    'version' => '1.0.0',
    'description' => 'Your module description',
    
    'dependencies' => [
        // List required modules
    ],
    
    'settings' => [
        'enabled' => true,
        // Your module settings
    ],
    
    'services' => [
        // DI container definitions
        YourService::class => \DI\autowire(),
    ],
    
    'published_events' => [
        'your_module.event_happened' => 'Description of the event',
    ],
    
    'api_endpoints' => [
        'GET /api/your-module' => 'List resources',
        'POST /api/your-module' => 'Create resource',
    ],
    
    'permissions' => [
        'your_module.read' => 'Read access to your module',
        'your_module.write' => 'Write access to your module',
    ],
    
    'database_tables' => [
        'your_module_table',
    ],
    
    'status' => [
        'implemented' => [
            'Basic functionality',
        ],
        'planned' => [
            'Advanced features',
        ],
    ],
];
```

#### Step 3: Module Auto-Discovery
The module will be automatically discovered and loaded when the application starts. No manual registration required.

### Module Dependencies

#### Dependency Declaration
```php
// In config.php
'dependencies' => [
    'User',        // Requires User module
    'Security',    // Requires Security module
],
```

#### Dependency Resolution
The ModuleManager automatically resolves dependencies and initializes modules in the correct order:

1. **Dependency Analysis**: Analyzes all module dependencies
2. **Topological Sort**: Determines initialization order
3. **Circular Detection**: Detects and reports circular dependencies
4. **Sequential Initialization**: Initializes modules in dependency order

#### Example Dependency Chain
```
Language Module (no dependencies) → Initialize first
User Module (no dependencies) → Initialize second  
Security Module (depends on User) → Initialize third
```

### Module Communication

#### Public Services
Modules can expose services for other modules to use:

```php
// Provider module config.php
'public_services' => [
    UserServiceInterface::class => UserService::class,
],

// Consumer module
public function __construct(
    private readonly UserServiceInterface $userService
) {}
```

#### Event-Driven Communication
Modules communicate through domain events:

```php
// Publisher module
'published_events' => [
    'user.created' => 'Fired when user is created',
],

// Subscriber module  
'event_subscriptions' => [
    'user.created' => [UserCreatedListener::class, 'handle'],
],
```

## Module Lifecycle

### 1. Discovery Phase
```
ModuleManager::discoverModules()
├── Scan modules/ directory
├── Find config.php files
├── Load configuration arrays
└── Create GenericModule instances
```

### 2. Registration Phase
```
ModuleManager::registerModule()
├── Validate module configuration
├── Check for naming conflicts
├── Store module instance
└── Log registration success
```

### 3. Initialization Phase
```
ModuleManager::initializeModules()
├── Resolve dependency order
├── Check for circular dependencies
├── Initialize modules sequentially
└── Track initialization status
```

### 4. Runtime Phase
```
Module Operations
├── Service resolution
├── Event publishing/subscribing
├── Health monitoring
└── Configuration access
```

## Configuration Management

### Centralized Access
```php
// Get module manager
$moduleManager = $container->get(ModuleManager::class);

// Access module configuration
$userConfig = $moduleManager->getModuleConfig('User');
$registrationEnabled = $userConfig['settings']['registration_enabled'];

// Access module instance
$userModule = $moduleManager->getModule('User');
$userSettings = $userModule->getSettings();
```

### Configuration Validation
```php
// Automatic validation during registration
$validationErrors = $module->validateConfig();

if (!empty($validationErrors)) {
    throw new InvalidArgumentException(
        "Module configuration is invalid: " . implode(', ', $validationErrors)
    );
}
```

### Environment-Specific Settings
```php
'settings' => [
    'debug' => $_ENV['APP_DEBUG'] === 'true',
    'api_key' => $_ENV['MODULE_API_KEY'] ?? null,
    'timeout' => (int) ($_ENV['MODULE_TIMEOUT'] ?? 30),
],
```

## Health Monitoring

### Module Health Status
```php
$healthStatus = $moduleManager->getModulesHealthStatus();

// Example output:
[
    'User' => [
        'name' => 'User',
        'version' => '1.0.0',
        'initialized' => true,
        'config_valid' => true,
        'dependencies_count' => 0,
        'services_count' => 3,
        'custom_check' => true,
    ],
    'Security' => [
        'name' => 'Security',
        'version' => '1.0.0',
        'initialized' => true,
        'config_valid' => true,
        'dependencies_count' => 1,
        'services_count' => 9,
    ],
]
```

### Custom Health Checks
```php
// In module config.php
'health_check' => function (): array {
    return [
        'database_connection' => $this->testDatabaseConnection(),
        'external_api' => $this->testExternalApi(),
        'cache_writable' => is_writable('var/cache/module'),
        'last_check' => date('Y-m-d H:i:s'),
    ];
},
```

## Statistics and Metrics

### Module Statistics
```php
$stats = $moduleManager->getStatistics();

// Example output:
[
    'total_modules' => 3,
    'initialized_modules' => 3,
    'pending_modules' => 0,
    'modules_by_status' => [
        'initialized' => ['User', 'Security', 'Language'],
        'registered' => [],
    ],
    'module_names' => ['User', 'Security', 'Language'],
]
```

### Performance Metrics
The system automatically tracks:
- **Module discovery time**
- **Initialization duration**
- **Configuration validation time**
- **Dependency resolution time**
- **Health check execution time**

## Best Practices

### 1. Module Design Principles

#### Single Responsibility
Each module should have a single, well-defined responsibility:
```php
// ✅ Good - focused responsibility
'name' => 'User',
'description' => 'User management with authentication and profiles',

// ❌ Bad - multiple responsibilities
'name' => 'UserEmailNotificationPayment',
'description' => 'User management, email sending, notifications, and payments',
```

#### Loose Coupling
Modules should communicate through well-defined interfaces:
```php
// ✅ Good - interface-based communication
'public_services' => [
    UserServiceInterface::class => UserService::class,
],

// ✅ Good - event-driven communication
'published_events' => [
    'user.created' => 'User was created',
],
```

#### High Cohesion
Related functionality should be grouped together within a module:
```php
// ✅ Good - cohesive structure
modules/Core/User/
├── Application/Actions/     # User-related actions
├── Domain/Models/          # User domain models
├── Domain/Services/        # User business logic
└── Infrastructure/         # User data persistence
```

### 2. Configuration Best Practices

#### Environment Variables
Use environment variables for environment-specific settings:
```php
'settings' => [
    'debug' => $_ENV['APP_DEBUG'] === 'true',
    'api_endpoint' => $_ENV['MODULE_API_ENDPOINT'] ?? 'https://api.example.com',
    'timeout' => (int) ($_ENV['MODULE_TIMEOUT'] ?? 30),
    'cache_enabled' => ($_ENV['MODULE_CACHE_ENABLED'] ?? 'true') === 'true',
],
```

#### Validation Rules
Always validate configuration values:
```php
'settings' => [
    'max_items' => max(1, min(1000, (int) ($_ENV['MODULE_MAX_ITEMS'] ?? 100))),
    'timeout' => max(1, min(300, (int) ($_ENV['MODULE_TIMEOUT'] ?? 30))),
],
```

#### Default Values
Provide sensible defaults for all settings:
```php
'settings' => [
    'enabled' => true,
    'cache_ttl' => 3600,
    'max_retries' => 3,
    'batch_size' => 100,
],
```

### 3. Dependency Management

#### Minimize Dependencies
Keep module dependencies to a minimum:
```php
// ✅ Good - minimal dependencies
'dependencies' => [
    'User',  // Only essential dependency
],

// ❌ Bad - too many dependencies
'dependencies' => [
    'User', 'Security', 'Email', 'Notification', 'Payment', 'Analytics',
],
```

#### Avoid Circular Dependencies
Design modules to avoid circular dependencies:
```php
// ✅ Good - clear hierarchy
User Module (no dependencies)
├── Security Module (depends on User)
└── Profile Module (depends on User)

// ❌ Bad - circular dependency
User Module (depends on Security)
└── Security Module (depends on User)
```

#### Use Interfaces for Decoupling
Define clear interfaces for module communication:
```php
// In shared contracts
interface UserServiceInterface
{
    public function findById(string $id): ?User;
    public function create(array $data): User;
}

// In module config
'public_services' => [
    UserServiceInterface::class => UserService::class,
],
```

### 4. Event System Integration

#### Clear Event Names
Use descriptive, hierarchical event names:
```php
'published_events' => [
    'user.created' => 'User account was created',
    'user.profile.updated' => 'User profile was updated',
    'user.password.changed' => 'User password was changed',
],
```

#### Event Data Standards
Include consistent data in events:
```php
// In event class
public function getEventData(): array
{
    return [
        'user_id' => $this->userId,
        'timestamp' => $this->occurredAt->format('Y-m-d H:i:s'),
        'source_module' => 'User',
        'event_version' => '1.0',
        // ... specific event data
    ];
}
```

### 5. API Design

#### RESTful Endpoints
Follow REST conventions for API endpoints:
```php
'api_endpoints' => [
    'GET /api/users' => 'List all users',
    'GET /api/users/{id}' => 'Get specific user',
    'POST /api/users' => 'Create new user',
    'PUT /api/users/{id}' => 'Update user',
    'DELETE /api/users/{id}' => 'Delete user',
],
```

#### Consistent Response Format
Use consistent response formats across modules:
```php
// Success response
{
    "success": true,
    "data": { ... },
    "meta": {
        "timestamp": "2025-01-11T10:30:00Z",
        "module": "User"
    }
}

// Error response
{
    "success": false,
    "error": {
        "code": "USER_NOT_FOUND",
        "message": "User not found",
        "details": { ... }
    }
}
```

## Troubleshooting

### Common Issues

#### Module Not Discovered
**Problem**: Module is not being discovered by ModuleManager

**Solutions**:
1. Check if `config.php` exists in module directory
2. Verify config.php returns an array
3. Check file permissions
4. Ensure modules directory path is correct

```bash
# Check module structure
ls -la modules/Core/YourModule/
# Should show config.php file

# Check config.php syntax
php -l modules/Core/YourModule/config.php
```

#### Circular Dependency Error
**Problem**: `Circular dependency detected involving module 'ModuleName'`

**Solutions**:
1. Review module dependencies
2. Redesign module architecture
3. Use event-driven communication instead of direct dependencies

```php
// Instead of direct dependency
'dependencies' => ['ModuleB'],

// Use event communication
'event_subscriptions' => [
    'module_b.event' => [YourListener::class, 'handle'],
],
```

#### Configuration Validation Failed
**Problem**: Module configuration validation errors

**Solutions**:
1. Check required configuration fields
2. Validate data types and formats
3. Review configuration template

```php
// Check validation errors
$module = $moduleManager->getModule('YourModule');
$errors = $module->validateConfig();
var_dump($errors);
```

#### Module Initialization Failed
**Problem**: Module fails to initialize

**Solutions**:
1. Check dependency availability
2. Review initialization logic
3. Check service definitions
4. Verify database connections

```php
// Debug initialization
try {
    $moduleManager->initializeModule('YourModule');
} catch (Exception $e) {
    echo "Initialization failed: " . $e->getMessage();
}
```

### Debug Mode

Enable debug logging to troubleshoot module issues:

```php
// In .env
APP_DEBUG=true
LOG_LEVEL=debug
```

This will log:
- Module discovery process
- Configuration validation results
- Dependency resolution steps
- Initialization progress
- Health check results

### Performance Issues

#### Slow Module Discovery
**Symptoms**: Application startup is slow

**Solutions**:
1. Reduce number of modules
2. Optimize config.php files
3. Use module caching
4. Profile discovery process

#### Memory Usage
**Symptoms**: High memory consumption

**Solutions**:
1. Lazy load module services
2. Optimize configuration size
3. Use weak references where appropriate
4. Monitor module health status

## Testing

### Unit Testing Modules

```php
class UserModuleTest extends TestCase
{
    public function testModuleConfiguration(): void
    {
        $config = require 'modules/Core/User/config.php';

        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertEquals('User', $config['name']);
    }

    public function testModuleValidation(): void
    {
        $module = new GenericModule('User', 'modules/Core/User', $config);
        $errors = $module->validateConfig();

        $this->assertEmpty($errors);
    }
}
```

### Integration Testing

```php
class ModuleManagerIntegrationTest extends TestCase
{
    public function testModuleDiscoveryAndInitialization(): void
    {
        $moduleManager = new ModuleManager($logger, 'modules');

        $moduleManager->discoverModules();
        $moduleManager->initializeModules();

        $stats = $moduleManager->getStatistics();
        $this->assertEquals(0, $stats['pending_modules']);
    }
}
```

## Migration Guide

### Upgrading Existing Modules

#### Step 1: Add Module Metadata
```php
// Add to existing config.php
'name' => 'YourModule',
'version' => '1.0.0',
'description' => 'Your module description',
```

#### Step 2: Restructure Configuration
```php
// Old format
return [
    ServiceClass::class => \DI\autowire(),
    // ... other services
];

// New format
return [
    'name' => 'YourModule',
    'services' => [
        ServiceClass::class => \DI\autowire(),
        // ... other services
    ],
    // ... other configuration sections
];
```

#### Step 3: Add Module Features
```php
// Add new configuration sections
'published_events' => [...],
'api_endpoints' => [...],
'permissions' => [...],
'status' => [...],
```

### Backward Compatibility

The system maintains backward compatibility with existing modules:
- Old-style config.php files are supported
- Service definitions are automatically extracted
- Missing configuration sections use defaults

## Future Enhancements

### Planned Features
- **✅ Module Marketplace**: Discover and install community modules
- **✅ Hot Reloading**: Reload modules without application restart
- **✅ Module Versioning**: Support for module version constraints
- **✅ Configuration UI**: Web interface for module configuration
- **✅ Module Templates**: Code generators for new modules
- **✅ Performance Profiling**: Detailed module performance analysis

### Integration Opportunities
- **✅ Package Managers**: Composer integration for module distribution
- **✅ CI/CD**: Automated module testing and deployment
- **✅ Monitoring**: Integration with monitoring systems
- **✅ Documentation**: Automatic API documentation generation
