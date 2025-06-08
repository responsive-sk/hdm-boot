# Architecture Overview

This document describes the architectural design and patterns used in the MVA Bootstrap Application.

## 🏗 Core Architecture

### Design Principles

1. **Modular Design** - Loosely coupled, highly cohesive modules
2. **Security First** - Built-in security at every layer
3. **Clean Architecture** - Separation of concerns and dependency inversion
4. **PSR Compliance** - Following PHP standards and best practices
5. **Testability** - Designed for easy unit and integration testing

### Application Layers

```
┌─────────────────────────────────────────┐
│              Presentation               │
│         (Routes, Actions, API)          │
├─────────────────────────────────────────┤
│              Application                │
│        (Services, Use Cases)            │
├─────────────────────────────────────────┤
│               Domain                    │
│      (Entities, Value Objects)          │
├─────────────────────────────────────────┤
│            Infrastructure               │
│    (Database, External Services)        │
└─────────────────────────────────────────┘
```

## 📦 Module System

### Module Types

#### Core Modules (Required)
- **Location**: `modules/Core/`
- **Purpose**: Essential application functionality
- **Examples**: User, Security
- **Loading**: Always loaded, application fails if missing

#### Optional Modules
- **Location**: `modules/Optional/`
- **Purpose**: Feature extensions
- **Examples**: Article, Blog, Shop
- **Loading**: Conditionally loaded based on configuration

### Module Structure

```
modules/
├── Core/
│   └── ModuleName/
│       ├── config.php          # Module configuration
│       ├── routes.php          # Module routes
│       ├── Actions/            # HTTP handlers
│       ├── Services/           # Business logic
│       ├── Repository/         # Data access
│       ├── Domain/             # Domain objects
│       └── Infrastructure/     # External integrations
└── Optional/
    └── ModuleName/
        └── [same structure]
```

### Module Configuration

Each module can define:

```php
// modules/Core/User/config.php
return [
    'services' => [
        UserService::class => function(Container $c) {
            return new UserService($c->get(UserRepository::class));
        },
        UserRepository::class => function(Container $c) {
            return new SqliteUserRepository($c->get(PDO::class));
        },
    ],
    'middleware' => [
        AuthenticationMiddleware::class,
    ],
    'dependencies' => [
        'Security', // Depends on Security module
    ],
];
```

## 🔧 Bootstrap Process

### Application Initialization

1. **Environment Loading** - Load `.env` configuration
2. **Container Creation** - Build DI container with core services
3. **Module Discovery** - Scan for available modules
4. **Module Loading** - Load core modules, then optional modules
5. **Route Registration** - Register all module routes
6. **Middleware Setup** - Configure middleware stack
7. **Error Handling** - Setup error handlers

### Module Loading Process

```php
// Simplified module loading flow
foreach ($coreModules as $module) {
    $moduleManager->loadModule($module, 'Core');
}

foreach ($enabledOptionalModules as $module) {
    $moduleManager->loadModule($module, 'Optional');
}
```

## 🔒 Security Architecture

### Path Security

- **SecurePathHelper** - Centralized path validation
- **Whitelist Approach** - Only allowed directories accessible
- **Path Traversal Protection** - Prevents `../` attacks
- **File Type Validation** - Restricted file extensions

### Security Layers

1. **Input Validation** - All inputs validated and sanitized
2. **Authentication** - JWT-based stateless authentication
3. **Authorization** - Role-based access control (RBAC)
4. **Path Security** - Secure file system access
5. **Output Encoding** - XSS prevention

## 🗄 Data Layer

### Repository Pattern

```php
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function save(User $user): void;
    public function findByEmail(string $email): ?User;
}

class SqliteUserRepository implements UserRepositoryInterface
{
    // Implementation
}
```

### Database Strategy

- **Primary**: SQLite for simplicity and portability
- **Configurable**: Easy to switch to MySQL/PostgreSQL
- **Migrations**: Version-controlled schema changes
- **Secure Paths**: Database files in protected directories

## 🌐 HTTP Layer

### Route Organization

```php
// config/routes.php - Main route loader
return function (App $app): void {
    // Core application routes
    $routeFiles = [
        __DIR__ . '/routes/home.php',
        __DIR__ . '/routes/api.php',
        __DIR__ . '/routes/test.php',
    ];

    // Module routes
    $moduleRoutes = [
        __DIR__ . '/../modules/Core/User/routes.php',
        __DIR__ . '/../modules/Core/Security/routes.php',
        __DIR__ . '/../modules/Optional/Article/routes.php',
    ];

    // Load all routes
    foreach (array_merge($routeFiles, $moduleRoutes) as $file) {
        if (file_exists($file)) {
            require $file;
        }
    }
};
```

### API Design

- **RESTful** - Following REST principles
- **JSON** - Consistent JSON responses
- **Versioning** - API version in URL path
- **Error Handling** - Standardized error responses
- **Authentication** - JWT token in Authorization header

## 🔄 Dependency Injection

### Container Configuration

```php
// config/container.php
$containerBuilder->addDefinitions([
    // Core services
    LoggerInterface::class => function(Container $c) {
        return new Logger('app');
    },
    
    // Path security
    SecurePathHelper::class => function(Container $c) {
        return new SecurePathHelper($c->get(Paths::class));
    },
    
    // Module services registered dynamically
]);
```

### Service Registration

- **Core Services** - Defined in main container
- **Module Services** - Registered during module loading
- **Lazy Loading** - Services created only when needed
- **Singleton Pattern** - Single instance per request

## 🧪 Testing Strategy

### Test Types

1. **Unit Tests** - Individual class testing
2. **Integration Tests** - Module interaction testing
3. **API Tests** - HTTP endpoint testing
4. **Security Tests** - Vulnerability testing

### Test Structure

```
tests/
├── Unit/
│   ├── Bootstrap/
│   ├── Shared/
│   └── Modules/
├── Integration/
│   ├── Api/
│   └── Database/
├── Security/
│   ├── PathTraversal/
│   └── Authentication/
└── Fixtures/
    ├── data/
    └── files/
```

## 🚀 Performance Considerations

### Optimization Strategies

1. **Container Compilation** - Pre-compiled DI container in production
2. **Route Caching** - Cached route definitions
3. **Lazy Loading** - Services loaded on demand
4. **Minimal Dependencies** - Only essential packages
5. **Efficient Autoloading** - Optimized Composer autoloader

### Monitoring

- **Logging** - Structured logging with Monolog
- **Error Tracking** - Comprehensive error handling
- **Performance Metrics** - Response time monitoring
- **Security Auditing** - Security event logging

## 🔧 Configuration Management

### Environment-Based Configuration

```php
// .env
APP_ENV=dev
APP_DEBUG=true
DATABASE_URL=sqlite:var/storage/app.db
ENABLED_MODULES=Article,Blog
```

### Configuration Hierarchy

1. **Environment Variables** - Runtime configuration
2. **Config Files** - Application defaults
3. **Module Config** - Module-specific settings
4. **Runtime Settings** - Dynamic configuration

## 📈 Scalability

### Horizontal Scaling

- **Stateless Design** - No server-side sessions
- **Database Separation** - Easy database scaling
- **Module Independence** - Modules can be deployed separately
- **API-First** - Frontend/backend separation ready

### Vertical Scaling

- **Efficient Memory Usage** - Minimal memory footprint
- **Fast Startup** - Quick application initialization
- **Optimized Queries** - Efficient database operations
- **Caching Strategy** - Multiple caching layers
