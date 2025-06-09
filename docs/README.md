# MVA Bootstrap Application Documentation

Welcome to the comprehensive documentation for the MVA Bootstrap Application. This documentation covers all aspects of the application from architecture to deployment.

## 📚 Documentation Index

### Getting Started
- [Main README](../README.md) - Project overview and quick start guide
- [Installation Guide](../README.md#installation) - Step-by-step setup instructions
- [Configuration Guide](../README.md#configuration) - Environment and application configuration

### Architecture & Design
- [Architecture Overview](ARCHITECTURE.md) - System design and architectural patterns
- [Module System](MODULES.md) - Modular architecture and module development
- [Security Design](SECURITY.md) - Security architecture and implementation

### Development
- [Module Development Guide](MODULES.md) - Creating and integrating modules
- [User Module Guide](USER_MODULE.md) - Complete user management system ✅
- [User API Guide](USER_API.md) - User API endpoints and examples ✅
- [Database Manager Guide](DATABASE_MANAGER.md) - Database management and operations ✅
- [API Documentation](API.md) - REST API endpoints and usage
- [Contributing Guide](../CONTRIBUTING.md) - How to contribute to the project

### Operations
- [Deployment Guide](DEPLOYMENT.md) - Production deployment strategies
- [Security Guide](SECURITY.md) - Security best practices and configuration
- [Changelog](../CHANGELOG.md) - Version history and changes

## 🏗 Architecture Overview

The MVA Bootstrap Application is built with a modular architecture that emphasizes:

- **Security First** - Built-in protection against common vulnerabilities
- **Modular Design** - Loosely coupled, highly cohesive modules
- **Clean Architecture** - Separation of concerns and dependency inversion
- **Modern PHP** - PHP 8.1+ with strict types and best practices

### Core Components

```
┌─────────────────────────────────────────┐
│              Bootstrap Core             │
│         (App, ModuleManager)            │
├─────────────────────────────────────────┤
│              Core Modules               │
│           (User, Security)              │
├─────────────────────────────────────────┤
│            Optional Modules             │
│              (Article)                  │
├─────────────────────────────────────────┤
│            Shared Services              │
│        (Paths, Logging, DI)             │
└─────────────────────────────────────────┘
```

## 🔒 Security Features

### Implemented Security
- ✅ **Path Traversal Protection** - Prevents directory traversal attacks
- ✅ **Secure File Operations** - Controlled file system access
- ✅ **Input Validation** - Comprehensive input sanitization
- ✅ **Environment Security** - Secure configuration management
- ✅ **Error Handling** - Secure error responses

### Planned Security
- 🔄 **JWT Authentication** - Stateless token-based authentication
- 🔄 **Role-Based Access Control** - Granular permission system
- 🔄 **CSRF Protection** - Cross-site request forgery prevention
- 🔄 **Session Security** - Secure session management

## 📦 Module System

### Core Modules (Required)
- **User** - User management and authentication (planned)
- **Security** - Authorization and security services (planned)

### Optional Modules
- **Article** - Content management system (planned)

### Module Structure
```
modules/
├── Core/
│   └── ModuleName/
│       ├── config.php      # Module configuration
│       ├── routes.php      # HTTP routes
│       ├── Actions/        # Request handlers
│       ├── Services/       # Business logic
│       ├── Repository/     # Data access
│       └── Domain/         # Domain objects
└── Optional/
    └── ModuleName/
        └── [same structure]
```

## 🌐 API Overview

### Current Endpoints
- `GET /` - Application dashboard
- `GET /api/status` - API status check
- `GET /api/info` - Application information
- `GET /test/paths` - Path security testing (dev only)
- `GET /test/env` - Environment information (dev only)

### Planned Endpoints
- `POST /api/auth/login` - User authentication
- `GET /api/users` - User management
- `GET /api/articles` - Article management (optional)

## 🚀 Quick Start

### 1. Installation
```bash
git clone <repository>
cd mva-bootstrap
composer install
cp .env.example .env
```

### 2. Configuration
```bash
# Edit .env file
APP_ENV=dev
APP_DEBUG=true
DATABASE_URL=sqlite:var/storage/app.db
```

### 3. Run Application
```bash
php -S localhost:8001 -t public
```

### 4. Test Security
```bash
curl http://localhost:8001/test/paths
```

## 🧪 Development Workflow

### Code Quality
```bash
composer phpstan      # Static analysis
composer cs-check     # Code style check
composer cs-fix       # Fix code style
composer test         # Run tests (when implemented)
composer quality      # Run all quality checks
```

### Creating a Module
1. Create module directory structure
2. Define module configuration
3. Implement domain objects
4. Create repository layer
5. Implement service layer
6. Add HTTP actions
7. Define routes
8. Write tests

## 📋 Development Status

### ✅ Completed
- Bootstrap application core
- Modular architecture foundation
- Secure path management system
- Basic API endpoints
- Development tools and scripts
- Comprehensive documentation

### 🔄 In Progress
- User module development
- Security module implementation
- Test suite creation

### ⏳ Planned
- Article module (optional)
- Database migrations
- Advanced security features
- Performance optimizations
- Internationalization

## 📞 Support & Resources

### Documentation
- [Architecture Guide](ARCHITECTURE.md) - Detailed system architecture
- [Security Guide](SECURITY.md) - Security implementation and best practices
- [Module Guide](MODULES.md) - Module development and integration
- [API Guide](API.md) - REST API documentation
- [Deployment Guide](DEPLOYMENT.md) - Production deployment

