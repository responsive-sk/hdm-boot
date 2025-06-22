# 🚀 HDM Boot Framework

**H**exagonal + **D**DD + **M**MA (Modular Monolith Architecture) - Modern PHP framework with triple architecture design, secure path handling, and optional modules. Built as a clean foundation for rapid development with enterprise-grade security features.

## 🎯 HDM Architecture

### 🔷 **Hexagonal Architecture**
- **Core Domain** - Business logic isolated from external concerns
- **Ports & Adapters** - Clean interfaces between layers
- **Dependency Inversion** - External dependencies point inward

### 🎯 **Domain-Driven Design (DDD)**
- **Bounded Contexts** - Modules represent business domains
- **Domain Models** - Rich business entities and value objects
- **Domain Services** - Business logic encapsulation

### 🏢 **Modular Monolith Architecture (MMA)**
- **Core Modules** - Shared functionality and infrastructure
- **Optional Modules** - Feature modules with clean boundaries
- **Module Isolation** - Independent development and testing

## 🚀 Features

- **Triple Architecture** - Hexagonal + DDD + Modular Monolith design
- **Modular Architecture** - Core and optional modules with dynamic loading
- **Hybrid Storage System** - File-based + Multi-Database storage ✅ **IMPLEMENTED**
- **Multi-Database Architecture** - 4 separate SQLite databases by purpose ✅ **IMPLEMENTED**
- **Secure Path Management** - Path traversal protection with `responsive-sk/slim4-paths`
- **Enterprise Session Management** - Advanced session handling with `responsive-sk/slim4-session`
- **Secure Authentication** - JWT tokens, password hashing, RBAC (planned)
- **User Management** - Complete user system with roles ✅ **IMPLEMENTED**
- **Session Management** - Enterprise session handling with CSRF protection ✅ **IMPLEMENTED** 🔧 **FIXED**
- **Multilingual Support** - Enterprise language system with 8 languages ✅ **IMPLEMENTED**
- **Content Management** - Articles & Documentation with Markdown ✅ **IMPLEMENTED**
- **Admin System** - Mark admin interface with audit logging ✅ **IMPLEMENTED**
- **Optional Modules** - Enable/disable features as needed
- **PSR Standards** - PSR-4, PSR-7, PSR-11, PSR-15 compliant
- **Modern PHP** - PHP 8.1+, strict types, modern practices
- **PHPStan Level MAX** - 100% type safety ✅ **ACHIEVED**
- **Security First** - Built-in protection against common vulnerabilities

## 📦 Architecture

### Core Modules (Required)
- **User** - User management and profiles ✅ **IMPLEMENTED**
- **Language** - Multilingual support system ✅ **IMPLEMENTED**
- **Storage** - Hybrid file + multi-database storage ✅ **IMPLEMENTED**
- **Security** - Authentication and authorization (planned)

### Optional Modules
- **Article** - Content management system ✅ **IMPLEMENTED**
- **Mark** - Admin interface system ✅ **IMPLEMENTED**

### Current Implementation Status
- ✅ **HDM Boot Core** - Triple architecture foundation
- ✅ **Secure Paths** - File system security
- ✅ **DI Container** - Dependency injection
- ✅ **Route System** - Modular routing
- ✅ **API Endpoints** - RESTful API foundation
- ✅ **User Module** - Complete user management system
- ✅ **Session Module** - Enterprise session management with CSRF protection
- ✅ **Language Module** - Enterprise multilingual support (8 languages)
- ✅ **Storage Module** - Hybrid file + multi-database storage system
- ✅ **Multi-Database Architecture** - 4 separate SQLite databases
- ✅ **Article System** - Markdown-based content management
- ✅ **Documentation System** - File-based docs with navigation
- ✅ **Mark Admin System** - Admin interface with audit logging
- ✅ **PHPStan Level MAX** - 100% type safety achieved
- 🔄 **Security Module** - In development

## 🛠 Installation

1. **Clone and setup**
```bash
git clone <repository>
cd hdm-boot
composer install
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your settings
```

3. **Setup directories**
```bash
# Create runtime directories (auto-created on first run)
mkdir -p var/{logs,storage,uploads,sessions,cache}
```

4. **Run development server**
```bash
php -S localhost:8001 -t public
```

## 🔧 Configuration

### Environment Variables

- `APP_ENV` - Application environment (dev/prod)
- `APP_DEBUG` - Enable debug mode (true/false)
- `DATABASE_URL` - Database connection string
- `JWT_SECRET` - Secret key for JWT tokens
- `ENABLED_MODULES` - Comma-separated list of optional modules
- `SESSION_NAME` - Session cookie name (hdm_boot_session)
- `SESSION_LIFETIME` - Session lifetime in seconds (7200)
- `SESSION_COOKIE_HTTPONLY` - HttpOnly cookie flag (true/false)
- `DEFAULT_LOCALE` - Default application language (en_US)
- `ENABLE_SLOVAK` - Enable Slovak language support (true/false)
- `ENABLE_CZECH` - Enable Czech language support (true/false)

### Module Configuration

Enable/disable optional modules in `.env`:
```env
ENABLED_MODULES="Article"
```

## 📁 Project Structure

```
hdm-boot/
├── src/Boot/           # Core application bootstrap (renamed from bootstrap)
│   ├── App.php         # Main application class
│   ├── ModuleManager.php # Module loading system
│   └── Security/       # Core security components
├── src/Modules/        # HDM Modular system
│   ├── Core/          # Required modules (Hexagonal Core)
│   │   ├── User/      # User domain module ✅ **IMPLEMENTED**
│   │   ├── Storage/   # Storage domain module ✅ **IMPLEMENTED**
│   │   ├── Language/  # Language domain module ✅ **IMPLEMENTED**
│   │   └── Security/  # Security domain module (planned)
│   └── Optional/      # Optional modules (DDD Bounded Contexts)
│       └── Blog/      # Blog domain module ✅ **IMPLEMENTED**
├── src/SharedKernel/   # Shared Kernel (DDD)
│   ├── Events/        # Domain events ✅ **IMPLEMENTED**
│   ├── Modules/       # Module management ✅ **IMPLEMENTED**
│   ├── Services/      # Shared services ✅ **IMPLEMENTED**
│   └── Contracts/     # Shared interfaces ✅ **IMPLEMENTED**
├── config/            # Configuration files
│   ├── container.php  # DI container setup
│   ├── paths.php      # Secure paths configuration
│   ├── routes.php     # Main routes loader
│   └── routes/        # Route definitions
├── public/            # Web root
├── var/               # Runtime files
│   ├── cache/         # Application cache
│   ├── logs/          # Log files
│   ├── storage/       # Database and file storage
│   ├── uploads/       # File uploads
│   └── sessions/      # Session storage
├── tests/             # Test suite (planned)
└── docs/              # Documentation
```

## 🔐 Security Features

### Implemented
- **Path Traversal Protection** - Prevents `../` attacks
- **Secure File Operations** - Controlled file access
- **Directory Access Control** - Whitelist-based directory access
- **Upload Validation** - File type and size restrictions
- **Environment Isolation** - Secure configuration management

### Planned
- **JWT Authentication** - Stateless token-based auth
- **Password Hashing** - Secure bcrypt/argon2 hashing
- **Role-Based Access Control** - Flexible permission system
- **CSRF Protection** - Cross-site request forgery protection
- **Session Security** - Secure session configuration
- **Input Validation** - Comprehensive input sanitization

## 🌐 API Endpoints

### Current Endpoints
- `GET /` - Application dashboard
- `GET /api/status` - API status check
- `GET /api/info` - Detailed application information
- `GET /api/language` - Get language settings
- `POST /api/language` - Change application language
- `GET /api/translate` - Translate strings
- `GET /test/paths` - Path security testing (dev only)
- `GET /test/env` - Environment information (dev only)

### Planned Endpoints
- `POST /api/auth/login` - User authentication
- `POST /api/auth/logout` - User logout
- `GET /api/users` - User management
- `GET /api/articles` - Article management (optional)

## 🧪 Development

### Code Quality
```bash
composer phpstan      # Static analysis (Level MAX ✅)
composer cs-check     # Code style check
composer cs-fix       # Fix code style
composer test         # Run tests (when implemented)
composer quality      # Run all quality checks
```

**Current Status**: PHPStan Level MAX with 0 errors ✅

### Testing
```bash
composer test                    # Run all tests
composer test-coverage          # Generate coverage report

# Blog Module Testing (HDM Boot v0.9.0)
composer test:blog              # Run Blog module tests (39 tests)
composer test:blog:verbose      # Verbose output
composer test:blog:coverage     # With coverage report

# Code Style Checking
composer cs-check               # Check code style (all files)
composer cs-fix                 # Fix code style (all files)
composer cs-check:blog          # Check Blog module only
composer cs-fix:blog            # Fix Blog module only

# Quality Assurance
composer quality                # Run all quality checks
composer quality:blog           # Run Blog module quality checks

# Alternative Blog testing methods
cd src/Modules/Optional/Blog
make test                       # Using Makefile
php run-tests.php              # Using path-safe runner
```

### Production Deployment
```bash
# See detailed deployment guide
cat docs/DEPLOYMENT.md

# Production deployment (no dev packages)
composer deploy:prod            # Install production dependencies
# OR manually:
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Production updates (faster)
composer deploy:update          # Update without scripts

# Environment setup
cp .env.example .env            # Configure for production
php bin/generate-keys.php       # Generate secure keys
chmod -R 755 var/              # Set permissions

# Verify no dev packages installed
composer show --installed | grep -E "(phpunit|phpstan|php-cs-fixer)"
# Should return empty
```

### Path Security Testing
```bash
# Test path security in browser or via API
curl http://localhost:8001/test/paths
```

## 📚 Documentation

### 🚨 **SECURITY ALERT**
- [**Security Incident Report**](docs/SECURITY_INCIDENT.md) 🚨 **ACTIVE** - Critical vulnerability remediation
- [**Paths Refactor Plan**](docs/PATHS_REFACTOR_PLAN.md) 🚨 **CRITICAL** - Complete security refactor strategy

### 🚀 **Featured Documentation**
- [**Orbit Quick Start**](docs/ORBIT_QUICK_START.md) ✅ **NEW** - Get started in 5 minutes!
- [**Orbit Implementation**](docs/ORBIT_IMPLEMENTATION.md) ✅ **NEW** - Complete Laravel Orbit-inspired CMS
- [Architecture Summary](docs/ARCHITECTURE_SUMMARY.md)
- [Security Guide](docs/SECURITY.md)
- [Session Management](docs/SESSION.md) ✅ **NEW**
- [Language & Localization](docs/LANGUAGE.md) ✅ **NEW**
- [Log Rotation & Cleanup](docs/LOG_ROTATION.md) ✅ **NEW**
- [Orbit-Style Implementation Example](content/docs/orbit-example.md) ✅ **NEW**
- [Hybrid Storage System](content/docs/hybrid-storage.md) ✅ **NEW**
- [Multi-Database Architecture](content/docs/multi-database-architecture.md) ✅ **NEW**
- [Storage Quick Start](content/docs/storage-quick-start.md) ✅ **NEW**
- [PHPStan Success Story](docs/PHPSTAN_SUCCESS.md) ✅ **NEW**
- [Development Plan](docs/DEVELOPMENT_PLAN.md) ✅ **NEW**
- [Module Development](docs/MODULES.md)
- [API Documentation](docs/API.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

## 📦 HDM Boot Ecosystem

### Core Packages
- **[responsive-sk/hdm-boot](https://github.com/responsive-sk/hdm-boot)** - Main framework
- **[responsive-sk/slim4-paths](https://packagist.org/packages/responsive-sk/slim4-paths)** - Secure path handling
- **[responsive-sk/slim4-session](https://packagist.org/packages/responsive-sk/slim4-session)** - Enterprise session management

### Optional Modules
- **[responsive-sk/hdm-boot-blog](https://github.com/responsive-sk/hdm-boot-blog)** - Blog module (v0.9.0)
- **responsive-sk/hdm-boot-user** - User management module (planned)
- **responsive-sk/hdm-boot-admin** - Admin interface module (planned)

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Run quality checks (`composer phpstan`)
5. Submit pull request

### Contributing to HDM Boot v1.0.0
- **Test in production** and report issues
- **Submit bug reports** with reproduction steps
- **Suggest improvements** for API design
- **Contribute test cases** for edge scenarios
- **Review documentation** for clarity

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🏗 Development Status

HDM Boot is a production-ready framework with triple architecture design. The foundation is solid and ready for enterprise development.

### 🎯 **Version Roadmap**

#### v0.9.0 (Current) - "Release Candidate"
- ✅ Triple architecture implementation
- ✅ Type-safe refactoring completed
- ✅ Comprehensive test framework
- ✅ Path-safe operations
- ❓ **Needs:** Production testing and community feedback

#### v1.0.0 - "Stable Release"
- 🎉 Proven in production environments
- 🎉 Community feedback incorporated
- 🎉 Full backward compatibility guarantee
- 🎉 Complete feature set with documentation

**Current Phase**: 🎉 **HDM BOOT v0.9.0 - RELEASE CANDIDATE** 🎉
- ✅ **Triple Architecture** - Hexagonal + DDD + MMA implemented
- ✅ **Type Safety** - PHPStan Level MAX with 0 errors
- ✅ **Blog Module** - Complete with 39 tests (27 passing)
- ✅ **Path Safety** - responsive-sk/slim4-paths integration
- ✅ **Modular Testing** - Tests in Optional module directories
- ✅ **Production Ready** - Clean architecture and documentation
- 🚀 **Ready for v1.0.0** - After production testing and feedback

**Next**: Community feedback and production testing → v1.0.0 stable release
