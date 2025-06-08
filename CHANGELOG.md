# Changelog

All notable changes to the MVA Bootstrap Application will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Security module with JWT and RBAC
- Article module (optional)
- Database migrations system
- Comprehensive test suite
- API documentation with OpenAPI/Swagger
- User module enhancements (update, delete, status management)
- User module with authentication system
- Security module with JWT and RBAC
- Article module (optional)
- Database migrations system
- Comprehensive test suite
- API documentation with OpenAPI/Swagger

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
- selective/basepath for base path handling
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
