# MVA Bootstrap Application

Modern PHP application with modular architecture, secure path handling, and optional modules. Built as a clean foundation for rapid development with enterprise-grade security features.

## 🚀 Features

- **Modular Architecture** - Core and optional modules with dynamic loading
- **Secure Path Management** - Path traversal protection with `responsive-sk/slim4-paths`
- **Secure Authentication** - JWT tokens, password hashing, RBAC (planned)
- **User Management** - Complete user system with roles ✅ **IMPLEMENTED**
- **Session Management** - Enterprise session handling with CSRF protection ✅ **IMPLEMENTED** 🔧 **FIXED**
- **Multilingual Support** - Enterprise language system with 8 languages ✅ **IMPLEMENTED**
- **Optional Modules** - Enable/disable features as needed
- **PSR Standards** - PSR-4, PSR-7, PSR-11, PSR-15 compliant
- **Modern PHP** - PHP 8.1+, strict types, modern practices
- **Security First** - Built-in protection against common vulnerabilities

## 📦 Architecture

### Core Modules (Required)
- **User** - User management and profiles ✅ **IMPLEMENTED**
- **Language** - Multilingual support system ✅ **IMPLEMENTED**
- **Security** - Authentication and authorization (planned)

### Optional Modules
- **Article** - Content management system (planned)

### Current Implementation Status
- ✅ **Bootstrap Core** - Application foundation
- ✅ **Secure Paths** - File system security
- ✅ **DI Container** - Dependency injection
- ✅ **Route System** - Modular routing
- ✅ **API Endpoints** - RESTful API foundation
- ✅ **User Module** - Complete user management system
- ✅ **Session Module** - Enterprise session management with CSRF protection
- ✅ **Language Module** - Enterprise multilingual support (8 languages)
- 🔄 **Security Module** - In development
- ⏳ **Article Module** - Planned

## 🛠 Installation

1. **Clone and setup**
```bash
git clone <repository>
cd mva-bootstrap
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
- `SESSION_NAME` - Session cookie name (mva_bootstrap_session)
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
mva-bootstrap/
├── bootstrap/           # Core application bootstrap
│   ├── App.php         # Main application class
│   ├── ModuleManager.php # Module loading system
│   └── Security/       # Core security components
├── modules/            # Modular system
│   ├── Core/          # Required modules
│   │   ├── User/      # User management (planned)
│   │   └── Security/  # Authentication & authorization (planned)
│   └── Optional/      # Optional modules
│       └── Article/   # Article management (planned)
├── config/            # Configuration files
│   ├── container.php  # DI container setup
│   ├── paths.php      # Secure paths configuration
│   ├── routes.php     # Main routes loader
│   └── routes/        # Route definitions
├── src/               # Shared components
│   ├── Domain/        # Domain objects (planned)
│   ├── Infrastructure/ # Database, external services (planned)
│   └── Shared/        # Shared utilities
│       └── Helpers/   # Helper classes
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
composer phpstan      # Static analysis
composer cs-check     # Code style check
composer cs-fix       # Fix code style
composer test         # Run tests (when implemented)
composer quality      # Run all quality checks
```

### Testing
```bash
composer test                    # Run all tests
composer test-coverage          # Generate coverage report
```

### Path Security Testing
```bash
# Test path security in browser or via API
curl http://localhost:8001/test/paths
```

## 📚 Documentation

- [Architecture Overview](docs/ARCHITECTURE.md)
- [Security Guide](docs/SECURITY.md)
- [Session Management](docs/SESSION.md) ✅ **NEW**
- [Language & Localization](docs/LANGUAGE.md) ✅ **NEW**
- [Module Development](docs/MODULES.md)
- [API Documentation](docs/API.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Run quality checks
5. Submit pull request

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🏗 Development Status

This is a bootstrap application currently in active development. The foundation is solid and ready for module development.

**Current Phase**: Core infrastructure complete, ready for User and Security module development.
