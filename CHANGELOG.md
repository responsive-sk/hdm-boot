# HDM Boot Protocol - Changelog

All notable changes to HDM Boot Protocol will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2025-06-27 - Paths Service & Directory Structure Optimization

### 🔧 Fixed - Critical Paths Service Issues
- **CRITICAL FIX**: Resolved Paths service config loading - custom configuration now properly respected
- **FIXED**: Database path resolution - all databases correctly resolve to `var/storage/`
- **FIXED**: Container compilation cache path in production environment
- **FIXED**: Removed hardcoded packages directory reference from test scripts

### 📁 Changed - Directory Structure Optimization
- **MOVED**: Storage directory from root `storage/` to `var/storage/` for better organization
- **CONSOLIDATED**: All runtime data under `var/` directory structure
- **UPDATED**: All database managers to use proper Paths service methods
- **ENHANCED**: Production build optimization (1.88 MB vs 12 MB, 84% smaller)

### 🛠️ Enhanced - Paths Service Usage
- **IMPROVED**: Replaced `path('storage/...')` with `storage('...')` method calls throughout codebase
- **UPDATED**: DatabaseManagerFactory to use specific Paths service methods
- **FIXED**: Database managers default path resolution using `storage()` method
- **ENHANCED**: Health checks in Database module to use proper path methods

### 🔍 Added - Development Tools
- **NEW**: Comprehensive paths audit tool (`bin/audit-paths.php`) for detecting hardcoded paths
- **NEW**: Paths cleanup tool (`bin/cleanup-paths.php`) for automated directory structure fixes
- **NEW**: Enhanced audit tool with Paths service call recognition
- **NEW**: Paths Service Guide documentation with best practices

### 📊 Improved - Code Quality Metrics
- **REDUCED**: Hardcoded paths from 46 to 21 (54% improvement)
- **MAINTAINED**: 100% PHPStan Level Max compliance
- **MAINTAINED**: 100% PHP CS Fixer compliance
- **ENHANCED**: Path consistency across all modules

### 🛡️ Security Enhancements
- **ADDED**: `.htaccess` protection to `var/storage/`, `var/logs/`, `config/`, `bin/` directories
- **IMPROVED**: Path traversal protection through proper Paths service usage
- **ENHANCED**: Directory isolation with organized structure

### 📚 Documentation Updates
- **UPDATED**: HDM Boot Protocol with new directory structure guidelines
- **ADDED**: Paths Service Guide with comprehensive usage examples
- **ENHANCED**: Compliance checklist with Paths service requirements
- **ADDED**: Migration guide for Paths service changes

### 🎯 Directory Structure (New)
```
var/
├── storage/          # Database files (NEW LOCATION)
│   ├── mark.db      # Mark system database
│   ├── user.db      # User system database
│   ├── system.db    # Core system database
│   └── blog.db      # Blog database (HDM Boot Protocol)
├── logs/            # Application logs
├── cache/           # Cache files
└── sessions/        # Session data
```

### 🔄 Migration Notes
- **Database Location**: All databases moved from `storage/` to `var/storage/`
- **Paths Service**: Use `storage()`, `logs()`, `cache()` methods instead of `path()` for configured paths
- **Configuration**: Custom `config/paths.php` now properly respected by Paths service
- **Production**: Optimized build excludes development dependencies

## [2.1.0] - 2025-06-26 - Mark System & Three-Database Architecture

### 🔴 Added - Mark System Implementation
- **NEW**: Complete Mark system for super user management
- **NEW**: Separate `/mark` route for mark admin authentication
- **NEW**: Mark module with dedicated authentication service
- **NEW**: MarkSqliteDatabaseManager for mark.db database
- **NEW**: SqliteMarkRepository for mark user data access
- **NEW**: MarkAuthenticationService for mark-specific authentication
- **NEW**: Mark login page with security warnings and branding
- **NEW**: Mark dashboard with super user functionality

### 🔵 Enhanced - User System Separation
- **IMPROVED**: User system now uses dedicated user.db database
- **IMPROVED**: UserSqliteDatabaseManager for user.db database
- **IMPROVED**: Complete separation between user and mark authentication
- **IMPROVED**: User login remains on `/login` route with user.db

### 🗄️ Changed - Database Architecture
- **BREAKING**: Migrated from single app.db to three-database architecture:
  - `storage/mark.db` - Mark users (super admins)
  - `storage/user.db` - Application users
  - `storage/system.db` - System data (cache, logs)
- **BREAKING**: Moved storage from `var/storage/` to `storage/` (root directory)
- **IMPROVED**: Database managers with auto-creation and permission handling
- **IMPROVED**: Debug error reporting for database connection issues

### 🔐 Security Improvements
- **FIXED**: Session cookie_secure auto-detection for HTTPS
- **IMPROVED**: Bcrypt password hashing for all users
- **IMPROVED**: Separate authentication flows for mark vs user systems
- **IMPROVED**: Enhanced error reporting without exposing sensitive data
- **IMPROVED**: Proper file permissions (777/666) for shared hosting

### 🛠️ Technical Improvements
- **FIXED**: Method visibility issues in database managers (private → protected)
- **FIXED**: Path resolution using secureDatabasePath instead of databasePath
- **IMPROVED**: DatabaseManagerFactory for consistent database creation
- **IMPROVED**: AbstractDatabaseManager inheritance properly implemented
- **IMPROVED**: Production build process with database initialization
- **IMPROVED**: Comprehensive debug logging for troubleshooting

### 🐛 Bug Fixes
- **FIXED**: Argon2ID password hashing compatibility issues → Bcrypt
- **FIXED**: Database path resolution in production environments
- **FIXED**: Session cookie security warnings on HTTPS
- **FIXED**: Method visibility inheritance issues
- **FIXED**: Duplicate storage directory confusion
- **FIXED**: Production build database creation failures

### 🔑 Default Credentials

#### Mark Users (storage/mark.db):
- **mark@responsive.sk** / mark123 (role: mark_admin)
- **admin@example.com** / admin123 (role: mark_admin)

#### Application Users (storage/user.db):
- **test@example.com** / password123 (role: user)
- **user@example.com** / user123 (role: user)

### ⚠️ Breaking Changes
- **BREAKING**: Mark admins must now use `/mark` route instead of `/login`
- **BREAKING**: Database structure completely changed (migration required)
- **BREAKING**: Storage directory moved from `var/storage/` to `storage/`
- **BREAKING**: Password hashing changed from Argon2ID to Bcrypt

### 🎯 Route Changes
- **NEW**: `GET /mark` - Mark login page
- **NEW**: `POST /mark/login` - Mark login submission
- **NEW**: `GET /mark/dashboard` - Mark dashboard
- **NEW**: `POST /mark/logout` - Mark logout
- **UNCHANGED**: `GET /login` - User login page (now user.db only)
- **UNCHANGED**: `POST /login` - User login submission (now user.db only)

## [2.0.0] - Previous Architecture

### Added
- **Dependency Injection & IoC Implementation** - Comprehensive DI/IoC system with interface-based bindings
- **Interface-based Service Bindings** - UserRepositoryInterface, LoggerInterface, SessionInterface bindings
- **Auto-wired Services** - Automatic dependency resolution for services and middleware
- **AuthorizationMiddleware** - Proper DI-based authorization middleware with injected dependencies
- **AuthorizationService** - Array-based authorization service with proper logging
- **Domain-Driven Design Implementation** - Pure business logic separated from framework concerns
- **Clean Architecture** - Clear layer boundaries with dependency inversion
- **DTOs and Domain Models** - Type-safe data transfer objects and domain entities
- **Domain Services** - Pure business logic without framework dependencies
- **Application Layer** - HTTP/CLI adapters that delegate to domain services
- **Module Isolation Implementation** - Contract-based communication between modules
- **Event-Driven Architecture** - Asynchronous inter-module communication through events
- **Module Registry** - Centralized module lifecycle management and dependency resolution
- **Module Contracts** - Public APIs for User, Security, and Language modules
- **Module Event Bus** - Event-driven communication infrastructure
- **RFC 7807 Problem Details** - Standardized error format for API responses
- **Error Handler Middleware** - Centralized exception handling and error response generation
- **Standardized Exceptions** - ProblemDetailsException hierarchy for consistent error handling
- **Error Helper Utilities** - Convenient validation and error throwing methods
- **Module-Specific Exceptions** - Domain-specific exceptions for User and Security modules
- **Centralized Logging System** - Multiple specialized loggers with environment-specific configuration
- **Health Check System** - Database, filesystem, and application health checks with /_status endpoint
- **Performance Monitoring** - Timers, counters, memory tracking, and automatic performance alerts
- **Web Documentation Viewer** - Built-in web interface for viewing project documentation at /docs
- **DDD Documentation** - Comprehensive documentation for domain-driven design and clean architecture
- **Module Isolation Documentation** - Complete guide for module isolation and event-driven patterns
- **Error Handling Documentation** - Complete guide for RFC 7807 Problem Details and standardized error handling
- **Monitoring & Logging Documentation** - Complete guide for centralized logging, health checks, and performance monitoring

### Changed
- **Container Configuration** - Added interface bindings file for proper DI/IoC
- **Service Layer** - All services now use constructor injection instead of direct instantiation
- **Middleware Layer** - All middleware auto-wired with proper dependency injection
- **AuthorizationService** - Updated to use array-based user data and injected logger
- **Module Structure** - Added Domain and Application layers for clean architecture
- **LoginSubmitAction** - Refactored to HTTP adapter pattern delegating to domain services
- **Business Logic** - Extracted pure business logic to domain services
- **Module Communication** - Replaced direct dependencies with contract-based interfaces
- **Service Implementations** - Updated UserService, AuthenticationService, LocaleService to implement module contracts
- **Event Publishing** - Added event publishing capabilities to domain services
- **Exception Handling** - Replaced InvalidArgumentException with standardized ProblemDetailsException
- **Error Responses** - All API errors now return RFC 7807 Problem Details format
- **Validation Logic** - Updated services to use ErrorHelper for consistent validation
- **Logging Infrastructure** - Implemented centralized logging with specialized loggers for different purposes
- **Health Check Endpoints** - Added /_status, /health, /healthz, /ping endpoints for monitoring
- **Performance Metrics** - Added comprehensive performance monitoring with automatic alerting
- **Documentation Web Interface** - Created /docs endpoint for viewing project documentation in browser

### Improved
- **SOLID Principles Compliance** - Full implementation of dependency inversion principle
- **Testability** - Easy mocking and unit testing through interface injection
- **Code Quality** - Eliminated direct instantiation (`new` operators) in services
- **Architecture** - Proper abstraction layers with interface-based design
- **Separation of Concerns** - Clear boundaries between business logic and framework code
- **Reusability** - Domain services can be used across HTTP, CLI, and API contexts
- **Maintainability** - Changes in outer layers don't affect inner business logic
- **Module Independence** - Modules communicate only through well-defined contracts
- **Event-Driven Communication** - Loose coupling through asynchronous event handling
- **Contract Validation** - Automatic validation of module interface compliance
- **Consistent Error Responses** - All API errors follow RFC 7807 Problem Details standard
- **Machine-Readable Errors** - Structured error format for better client integration
- **Centralized Error Handling** - Single middleware handles all exception types consistently
- **Production-Ready Monitoring** - Comprehensive logging, health checks, and performance monitoring
- **Observability** - Complete visibility into application behavior, performance, and health status
- **Documentation Accessibility** - Project documentation available through web interface for easy access

### Added
- 🍪 **Enterprise Session Management System**
  - SessionStartMiddleware for automatic session initialization
  - Environment-driven session configuration with security settings
  - CSRF protection with token validation for forms
  - Flash message system for user notifications
  - Session persistence across requests with proper cookie handling
  - Security features: HttpOnly, Secure, SameSite cookie protection
  - Session regeneration on login/logout for security
  - Comprehensive documentation in `docs/SESSION.md`
  - Based on samuelgfeller's session pattern with enterprise enhancements

- 🌍 **Enterprise Language & Localization System**
  - Support for 8 languages (English, Slovak, Czech, German, French, Spanish, Italian, Polish)
  - Automatic language detection from browser, session, cookies
  - LocaleMiddleware for automatic language setting on every request
  - REST API endpoints for language management (`/api/language`, `/api/translate`)
  - Session and cookie persistence for language preferences
  - Environment-driven configuration with `.env` variables
  - Translation template with 100+ common strings
  - Comprehensive documentation in `docs/LANGUAGE.md`
  - Based on samuelgfeller's localization pattern with enterprise enhancements

### Technical Details

#### Session Management
- **SessionStartMiddleware** - Automatic session initialization middleware
- **SessionInterface** - Session data management with odan/session
- **SessionManagerInterface** - Session lifecycle management
- **CsrfService** - CSRF token generation and validation
- **FlashInterface** - Flash message system for user notifications
- **Session Security** - HttpOnly, Secure, SameSite cookie protection
- **Environment Configuration** - SESSION_NAME, SESSION_LIFETIME, cookie settings
- **Login/Logout Integration** - Session regeneration and destruction

#### Language & Localization
- **LocaleService** - Core language management service
- **LocaleMiddleware** - Automatic language detection middleware
- **TranslateAction** - API endpoint for string translations
- **LanguageSettingsAction** - API endpoint for language management
- **config/language.php** - Comprehensive language configuration
- **resources/translations/** - Translation files structure
- **Detection Priority**: User preference → Session → Cookie → Browser → Default
- **Fallback System** - Graceful handling of unsupported languages
- **Enterprise Logging** - Comprehensive language operation tracking

### Configuration
- Added session-related environment variables to `.env.example`
- Added SessionStartMiddleware to application middleware stack
- Updated container configuration for session services
- Added CSRF protection configuration
- Added language-related environment variables to `.env.example`
- Added `config/language.php` configuration file
- Updated container configuration for language services
- Added LocaleMiddleware to application middleware stack

### API Endpoints
- `GET /api/language` - Get current language settings and available locales
- `POST /api/language` - Change application language
- `GET /api/translate` - Translate strings via query parameters
- `POST /api/translate` - Translate strings via JSON body

### Documentation
- Added comprehensive `docs/SESSION.md` documentation
- Added comprehensive `docs/LANGUAGE.md` documentation
- Updated `README.md` with session and language features
- Added Quick Start guide for language system
- Added troubleshooting sections for common issues
- Added security best practices for session management

### Fixed
- **🔐 Critical Session Fix**: Fixed login page showing even when user is logged in
  - LoginSubmitAction was missing `last_activity` session key
  - SessionService.isLoggedIn() was failing due to session expiration check
  - Added proper session key consistency across authentication flow
  - Fixed session persistence between browser tabs and requests
  - Enhanced session troubleshooting documentation with real-world solutions
- Fixed language detection priority order
- Fixed session cookie configuration for proper persistence
- Fixed LocaleMiddleware integration with session system

### Planned
- Security module with JWT and RBAC
- Article module (optional)
- Database migrations system
- Comprehensive test suite
- API documentation with OpenAPI/Swagger
- User module enhancements (update, delete, status management)

## [1.1.0] - 2025-06-08

### Added
- **User Module Implementation**
  - Complete user management system with CRUD operations
  - User entity with rich domain logic and business rules
  - Repository pattern with SQLite implementation
  - User service with comprehensive business logic
  - Password hashing with Argon2ID algorithm
  - Role-based user system (user, editor, admin)
  - Status management (active, inactive, suspended, pending)
  - Email verification token system
  - Password reset token system
  - User statistics and reporting

- **User API Endpoints**
  - GET /api/users - List users with pagination and filters
  - POST /api/users - Create new user with validation
  - GET /api/users/{id} - Get user by ID
  - GET /api/admin/users/statistics - User statistics

- **Database Schema**
  - Users table with comprehensive fields
  - Performance-optimized indexes
  - Automatic database initialization

- **Security Features**
  - UUID identifiers to prevent enumeration attacks
  - Comprehensive input validation
  - Password strength requirements
  - Email uniqueness constraints

- **Testing**
  - Manual testing script (test-user.php)
  - API endpoint testing
  - User creation, authentication, and statistics testing

- **Documentation**
  - Complete User Module documentation
  - User API documentation with examples
  - Updated main documentation

### Security
- Implemented Argon2ID password hashing
- Added comprehensive input validation
- UUID-based user identification
- Email verification system foundation
- Password reset system foundation

## [1.0.0] - 2025-06-08

### Added
- **Bootstrap Application Core**
  - Main application class with initialization flow
  - Modular architecture with Core and Optional modules
  - Module manager for dynamic module loading
  - DI container configuration with PHP-DI

- **Secure Path Management**
  - Integration with `responsive-sk/slim4-paths` package
  - SecurePathHelper class for safe file operations
  - Path traversal protection and validation
  - Directory whitelisting and access control
  - File upload security with extension validation

- **Route System**
  - Modular route organization
  - Automatic route loading from modules
  - RESTful API endpoints structure
  - Development and testing routes

- **Configuration Management**
  - Environment-based configuration with .env files
  - Paths configuration for security
  - Container service definitions
  - Module configuration system

- **API Endpoints**
  - `/api/status` - Application status check
  - `/api/info` - Detailed application information
  - `/test/paths` - Path security testing (dev only)
  - `/test/env` - Environment information (dev only)

- **Security Features**
  - Path traversal attack prevention
  - Directory access control with whitelisting
  - File upload validation and restrictions
  - Secure file operations with validation
  - Environment variable protection

- **Development Tools**
  - Composer scripts for code quality
  - PHPStan static analysis configuration
  - PHP CodeSniffer for PSR-12 compliance
  - Development server setup

- **Documentation**
  - Comprehensive README with setup instructions
  - Architecture documentation
  - Security guide with best practices
  - Module development guide
  - API documentation
  - Deployment guide for various environments

### Dependencies
- PHP 8.1+ requirement
- Slim Framework 4.x for HTTP handling
- PHP-DI 7.x for dependency injection
- responsive-sk/slim4-paths for secure path management
- Monolog for logging
- Ramsey UUID for unique identifiers
- Odan Session for session management
- Firebase JWT for token handling (ready for implementation)

### Security
- Implemented path traversal protection
- Added file upload security validation
- Environment variable security
- Secure default configurations
- Protection against common web vulnerabilities

## [0.1.0] - 2025-06-08

### Added
- Initial project structure
- Basic Slim Framework setup
- Composer configuration
- Environment configuration template

---

## Version History

### Version Numbering

This project follows [Semantic Versioning](https://semver.org/):
- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

### Release Process

1. **Development** - Features developed in feature branches
2. **Testing** - Comprehensive testing before release
3. **Documentation** - Update documentation for new features
4. **Changelog** - Update this changelog with changes
5. **Tagging** - Create git tag with version number
6. **Release** - Deploy to production environment

### Upgrade Notes

#### From 0.x to 1.0.0
- Complete rewrite with modular architecture
- New configuration system with .env files
- Enhanced security with path protection
- New API endpoint structure
- Updated dependencies to latest versions

### Breaking Changes

#### Version 1.0.0
- Initial stable release - no breaking changes from previous versions
- Established API contract for future compatibility

### Migration Guide

#### Upgrading to 1.0.0
This is the first stable release. Future migration guides will be provided here for major version upgrades.

### Support

- **Current Version**: 1.0.0
- **Supported Versions**: 1.x.x
- **End of Life**: TBD

For support and questions, please refer to the documentation or create an issue in the project repository.
