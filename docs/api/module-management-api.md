# Module Management API Reference

## Overview

This document provides a complete API reference for the Module Management System in the MVA Bootstrap project, including interfaces, classes, and methods.

## Core Interfaces

### ModuleInterface

The main contract that all modules must implement.

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
    public function getStatus(): array;
}
```

#### Method Details

##### getName()
```php
public function getName(): string
```
**Returns:** The unique module name identifier.

**Example:**
```php
$module->getName(); // Returns: "User"
```

##### getVersion()
```php
public function getVersion(): string
```
**Returns:** Module version in semantic versioning format (x.y.z).

**Example:**
```php
$module->getVersion(); // Returns: "1.0.0"
```

##### getDescription()
```php
public function getDescription(): string
```
**Returns:** Human-readable description of module functionality.

**Example:**
```php
$module->getDescription(); // Returns: "User management module with authentication"
```

##### getConfig()
```php
public function getConfig(): array
```
**Returns:** Complete module configuration array.

**Example:**
```php
$config = $module->getConfig();
// Returns: ['name' => 'User', 'version' => '1.0.0', 'settings' => [...], ...]
```

##### getSettings()
```php
public function getSettings(): array
```
**Returns:** Module-specific settings array.

**Example:**
```php
$settings = $module->getSettings();
// Returns: ['registration_enabled' => true, 'password_min_length' => 8, ...]
```

##### validateConfig()
```php
public function validateConfig(): array
```
**Returns:** Array of validation error messages (empty if valid).

**Example:**
```php
$errors = $module->validateConfig();
// Returns: [] if valid, or ['Invalid version format'] if invalid
```

##### getDependencies()
```php
public function getDependencies(): array
```
**Returns:** Array of module names this module depends on.

**Example:**
```php
$dependencies = $module->getDependencies();
// Returns: ['User', 'Security']
```

##### getServiceDefinitions()
```php
public function getServiceDefinitions(): array
```
**Returns:** DI container service definitions.

**Example:**
```php
$services = $module->getServiceDefinitions();
// Returns: [UserService::class => \DI\autowire(), ...]
```

##### getPublicServices()
```php
public function getPublicServices(): array
```
**Returns:** Services exposed to other modules (interface => implementation).

**Example:**
```php
$publicServices = $module->getPublicServices();
// Returns: [UserServiceInterface::class => UserService::class]
```

##### getPublishedEvents()
```php
public function getPublishedEvents(): array
```
**Returns:** Events published by this module (event_name => description).

**Example:**
```php
$events = $module->getPublishedEvents();
// Returns: ['user.created' => 'User was created', ...]
```

##### getEventSubscriptions()
```php
public function getEventSubscriptions(): array
```
**Returns:** Events this module subscribes to (event_name => listener_callable).

**Example:**
```php
$subscriptions = $module->getEventSubscriptions();
// Returns: ['security.login' => [UserLoginListener::class, 'handle']]
```

##### getApiEndpoints()
```php
public function getApiEndpoints(): array
```
**Returns:** API endpoints provided by this module (endpoint => description).

**Example:**
```php
$endpoints = $module->getApiEndpoints();
// Returns: ['GET /api/users' => 'List users', ...]
```

##### getMiddleware()
```php
public function getMiddleware(): array
```
**Returns:** Middleware provided by this module (class => description).

**Example:**
```php
$middleware = $module->getMiddleware();
// Returns: [AuthMiddleware::class => 'Authentication middleware']
```

##### getPermissions()
```php
public function getPermissions(): array
```
**Returns:** Security permissions defined by this module (permission => description).

**Example:**
```php
$permissions = $module->getPermissions();
// Returns: ['user.read' => 'Read user data', ...]
```

##### getDatabaseTables()
```php
public function getDatabaseTables(): array
```
**Returns:** Database tables managed by this module.

**Example:**
```php
$tables = $module->getDatabaseTables();
// Returns: ['users', 'user_sessions']
```

##### initialize()
```php
public function initialize(): void
```
**Description:** Initializes the module. Called during application startup.

**Example:**
```php
$module->initialize(); // Runs module initialization logic
```

##### isInitialized()
```php
public function isInitialized(): bool
```
**Returns:** True if module has been initialized.

**Example:**
```php
$initialized = $module->isInitialized(); // Returns: true/false
```

##### getHealthStatus()
```php
public function getHealthStatus(): array
```
**Returns:** Module health status information.

**Example:**
```php
$health = $module->getHealthStatus();
// Returns: ['config_valid' => true, 'initialized' => true, ...]
```

##### getStatus()
```php
public function getStatus(): array
```
**Returns:** Module implementation status (implemented/planned features).

**Example:**
```php
$status = $module->getStatus();
// Returns: ['implemented' => [...], 'planned' => [...]]
```

## ModuleManager Class

Central management class for module operations.

### Constructor

```php
public function __construct(
    LoggerInterface $logger,
    string $modulesPath = 'modules'
)
```

**Parameters:**
- `$logger` - PSR-3 logger for module operations
- `$modulesPath` - Path to modules directory

### Methods

#### discoverModules()
```php
public function discoverModules(): void
```
**Description:** Discovers and loads all modules from the modules directory.

**Example:**
```php
$moduleManager->discoverModules();
```

#### registerModule()
```php
public function registerModule(ModuleInterface $module): void
```
**Parameters:**
- `$module` - Module instance to register

**Throws:** `InvalidArgumentException` if module configuration is invalid

**Example:**
```php
$module = new GenericModule('User', 'modules/Core/User', $config);
$moduleManager->registerModule($module);
```

#### getModule()
```php
public function getModule(string $name): ?ModuleInterface
```
**Parameters:**
- `$name` - Module name

**Returns:** Module instance or null if not found

**Example:**
```php
$userModule = $moduleManager->getModule('User');
if ($userModule) {
    echo $userModule->getVersion();
}
```

#### getAllModules()
```php
public function getAllModules(): array
```
**Returns:** Array of all registered modules (name => ModuleInterface)

**Example:**
```php
$modules = $moduleManager->getAllModules();
foreach ($modules as $name => $module) {
    echo "{$name}: {$module->getVersion()}\n";
}
```

#### getModuleConfig()
```php
public function getModuleConfig(string $moduleName): array
```
**Parameters:**
- `$moduleName` - Module name

**Returns:** Module configuration array

**Example:**
```php
$userConfig = $moduleManager->getModuleConfig('User');
$registrationEnabled = $userConfig['settings']['registration_enabled'];
```

#### getAllModuleConfigs()
```php
public function getAllModuleConfigs(): array
```
**Returns:** All module configurations (module_name => config_array)

**Example:**
```php
$allConfigs = $moduleManager->getAllModuleConfigs();
foreach ($allConfigs as $moduleName => $config) {
    echo "{$moduleName}: {$config['version']}\n";
}
```

#### initializeModules()
```php
public function initializeModules(): void
```
**Description:** Initializes all modules in dependency order.

**Throws:** `RuntimeException` if circular dependency detected

**Example:**
```php
$moduleManager->initializeModules();
```

#### initializeModule()
```php
public function initializeModule(string $moduleName): void
```
**Parameters:**
- `$moduleName` - Name of module to initialize

**Throws:** `RuntimeException` if module not found

**Example:**
```php
$moduleManager->initializeModule('User');
```

#### hasModule()
```php
public function hasModule(string $name): bool
```
**Parameters:**
- `$name` - Module name

**Returns:** True if module is registered

**Example:**
```php
if ($moduleManager->hasModule('User')) {
    // User module is available
}
```

#### isModuleInitialized()
```php
public function isModuleInitialized(string $name): bool
```
**Parameters:**
- `$name` - Module name

**Returns:** True if module is initialized

**Example:**
```php
if ($moduleManager->isModuleInitialized('User')) {
    // User module is ready to use
}
```

#### getModulesHealthStatus()
```php
public function getModulesHealthStatus(): array
```
**Returns:** Health status for all modules (module_name => health_array)

**Example:**
```php
$healthStatus = $moduleManager->getModulesHealthStatus();
foreach ($healthStatus as $moduleName => $health) {
    echo "{$moduleName}: " . ($health['config_valid'] ? 'OK' : 'ERROR') . "\n";
}
```

#### getStatistics()
```php
public function getStatistics(): array
```
**Returns:** Module system statistics

**Example:**
```php
$stats = $moduleManager->getStatistics();
echo "Total modules: {$stats['total_modules']}\n";
echo "Initialized: {$stats['initialized_modules']}\n";
echo "Pending: {$stats['pending_modules']}\n";
```

**Statistics Structure:**
```php
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

## GenericModule Class

Default implementation of ModuleInterface.

### Constructor

```php
public function __construct(
    string $name,
    string $path,
    array $config
)
```

**Parameters:**
- `$name` - Module name
- `$path` - Module directory path
- `$config` - Module configuration array

### Additional Methods

#### getPath()
```php
public function getPath(): string
```
**Returns:** Module directory path

**Example:**
```php
$path = $module->getPath(); // Returns: "modules/Core/User"
```

#### hasConfig()
```php
public function hasConfig(string $key): bool
```
**Parameters:**
- `$key` - Configuration key

**Returns:** True if configuration key exists

**Example:**
```php
if ($module->hasConfig('settings')) {
    $settings = $module->getConfigValue('settings');
}
```

#### getConfigValue()
```php
public function getConfigValue(string $key, $default = null)
```
**Parameters:**
- `$key` - Configuration key
- `$default` - Default value if key not found

**Returns:** Configuration value or default

**Example:**
```php
$version = $module->getConfigValue('version', '1.0.0');
$settings = $module->getConfigValue('settings', []);
```

## Usage Examples

### Basic Module Management

```php
// Initialize module manager
$logger = $container->get(LoggerInterface::class);
$moduleManager = new ModuleManager($logger, 'modules');

// Discover and initialize modules
$moduleManager->discoverModules();
$moduleManager->initializeModules();

// Get module statistics
$stats = $moduleManager->getStatistics();
echo "Loaded {$stats['total_modules']} modules\n";

// Access specific module
$userModule = $moduleManager->getModule('User');
if ($userModule) {
    echo "User module version: {$userModule->getVersion()}\n";
    
    // Get module configuration
    $config = $userModule->getConfig();
    $registrationEnabled = $config['settings']['registration_enabled'];
    
    // Check module health
    $health = $userModule->getHealthStatus();
    if ($health['config_valid']) {
        echo "User module is healthy\n";
    }
}
```

### Configuration Access

```php
// Get all module configurations
$allConfigs = $moduleManager->getAllModuleConfigs();

// Access specific module settings
$userConfig = $moduleManager->getModuleConfig('User');
$passwordMinLength = $userConfig['settings']['password_min_length'];

// Check if module has specific configuration
$userModule = $moduleManager->getModule('User');
if ($userModule->hasConfig('settings')) {
    $settings = $userModule->getConfigValue('settings');
}
```

### Health Monitoring

```php
// Check health of all modules
$healthStatus = $moduleManager->getModulesHealthStatus();

foreach ($healthStatus as $moduleName => $health) {
    echo "Module: {$moduleName}\n";
    echo "  Initialized: " . ($health['initialized'] ? 'Yes' : 'No') . "\n";
    echo "  Config Valid: " . ($health['config_valid'] ? 'Yes' : 'No') . "\n";
    echo "  Services: {$health['services_count']}\n";
    echo "  Events: {$health['events_published']}\n";
    echo "\n";
}
```

### Module Dependencies

```php
// Check module dependencies
$securityModule = $moduleManager->getModule('Security');
$dependencies = $securityModule->getDependencies();

echo "Security module depends on: " . implode(', ', $dependencies) . "\n";

// Verify all dependencies are loaded
foreach ($dependencies as $dependency) {
    if (!$moduleManager->hasModule($dependency)) {
        echo "Missing dependency: {$dependency}\n";
    }
}
```

## Error Handling

### Common Exceptions

#### InvalidArgumentException
Thrown when module configuration is invalid:

```php
try {
    $moduleManager->registerModule($invalidModule);
} catch (InvalidArgumentException $e) {
    echo "Module configuration error: " . $e->getMessage();
}
```

#### RuntimeException
Thrown for module operation errors:

```php
try {
    $moduleManager->initializeModule('NonExistentModule');
} catch (RuntimeException $e) {
    echo "Module initialization error: " . $e->getMessage();
}
```

### Validation Errors

```php
$module = $moduleManager->getModule('User');
$errors = $module->validateConfig();

if (!empty($errors)) {
    echo "Configuration errors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}
```

This API reference provides comprehensive documentation for all module management functionality in the MVA Bootstrap project.
