# HDM Boot Framework

**Enterprise PHP framework with Triple Architecture: Hexagonal + DDD + Modular Monolith**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Framework](https://img.shields.io/badge/Framework-Slim%204-green.svg)](https://slimframework.com)
[![Architecture](https://img.shields.io/badge/Architecture-Hexagonal%20%2B%20DDD-orange.svg)](docs/ARCHITECTURE.md)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-39%20passing-brightgreen.svg)](tests/)

## 🚀 Features

### 🏛️ **Triple Architecture**
- **Hexagonal Architecture** - Clean separation of business logic from infrastructure
- **Domain-Driven Design** - Rich domain models with clear boundaries
- **Modular Monolith** - Independent modules with clear interfaces

### 🔐 **Security First**
- **JWT Authentication** - Secure token-based authentication
- **CSRF Protection** - Cross-site request forgery prevention
- **Secure Sessions** - Enterprise-grade session management
- **Path Safety** - Safe file system operations with `responsive-sk/slim4-paths`

### 🌍 **Internationalization**
- **Multi-language Support** - Slovak, Czech, English, and more
- **Automatic Detection** - Browser and user preference detection
- **Gettext Integration** - Professional translation workflow
- **Locale Management** - Dynamic language switching

### 🏗️ **Full Module Isolation**
- **Independent Modules** - Each module can have its own composer.json
- **Module-specific Testing** - Isolated test suites with PHPUnit
- **CI/CD per Module** - GitHub Actions for individual modules
- **Marketplace Ready** - Open-source plugin ecosystem support

### 📊 **Enterprise Ready**
- **Comprehensive Logging** - Monolog with multiple handlers
- **Health Monitoring** - Multiple health check endpoints
- **Database Abstraction** - Multiple storage engines support
- **Production Deployment** - Complete deployment guides and validation

## 📦 Installation

### Quick Start

```bash
composer create-project responsive-sk/hdm-boot my-project
cd my-project
cp .env.example .env
# Configure your .env file
composer install
```

### Development Setup

```bash
# Clone repository
git clone https://github.com/responsive-sk/hdm-boot.git
cd hdm-boot

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php bin/generate-keys.php

# Validate environment
php bin/validate-env.php

# Start development server
php -S localhost:8000 -t public/
```

## 🏗️ Architecture

HDM Boot implements **Triple Architecture** combining three enterprise patterns:

### **1. Hexagonal Architecture (Ports & Adapters)**
```
Application Core
├── Domain/          # Business logic
├── Application/     # Use cases
└── Ports/          # Interfaces

Infrastructure
├── Adapters/       # External integrations
├── Persistence/    # Database implementations
└── Web/           # HTTP controllers
```

### **2. Domain-Driven Design (DDD)**
```
Domain/
├── Entities/       # Business objects
├── ValueObjects/   # Immutable values
├── Repositories/   # Data access interfaces
├── Services/       # Domain services
└── Events/        # Domain events
```

### **3. Modular Monolith**
```
src/Modules/
├── Core/           # Essential modules
│   ├── User/       # User management
│   ├── Security/   # Authentication & authorization
│   ├── Template/   # View rendering
│   └── Session/    # Session management
└── Optional/       # Feature modules
    ├── Blog/       # Blog functionality (Full Module Isolation)
    └── Ecommerce/  # E-commerce features
```

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_NAME="HDM Boot"
APP_ENV=prod
APP_DEBUG=false
APP_TIMEZONE=UTC

# Database
DATABASE_URL="sqlite:var/storage/app.db"

# Security
JWT_SECRET="your-64-character-secret-key"
JWT_EXPIRY=3600

# Modules
ENABLED_MODULES="Blog"

# Sessions
SESSION_NAME="hdm_boot_session"
SESSION_LIFETIME=7200
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true

# Localization
DEFAULT_LOCALE=en_US
DEFAULT_TIMEZONE=Europe/Bratislava
ENABLE_SLOVAK=true
ENABLE_CZECH=true
```

### Module Configuration

Enable/disable modules in `.env`:

```env
ENABLED_MODULES="Blog,Ecommerce,Analytics"
```

## 🧪 Testing

### Framework Tests

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:integration
composer test:functional

# Generate coverage report
composer test:coverage

# Code quality checks
composer cs:check
composer analyse
```

### Module-specific Tests (Full Module Isolation)

```bash
# Blog module tests
cd src/Modules/Optional/Blog
composer test
composer test:coverage

# Run via module test runner
php run-module-tests.php
php run-module-tests.php --coverage
```

### Test Statistics
- **Framework Tests**: 39 tests, 98 assertions
- **Blog Module Tests**: 39 tests, 98 assertions
- **Total Coverage**: 90%+ target
- **Code Quality**: PHPStan Level 8

## 🏗️ Full Module Isolation

HDM Boot supports **Full Module Isolation** for enterprise development:

### **Blog Module Example**

```
src/Modules/Optional/Blog/
├── composer.json              # Independent package
├── vendor/                    # Module dependencies (84KB optimized)
├── phpunit.xml               # Module-specific testing
├── run-module-tests.php      # Paths-powered test runner
├── tests/                    # 39 tests, 98 assertions
├── README.md                 # Module documentation
├── .github/workflows/ci.yml  # Module CI/CD
└── src/                      # Module source code
```

### **Module Development**

```bash
# Create new isolated module
mkdir -p src/Modules/Optional/MyModule
cd src/Modules/Optional/MyModule

# Initialize module
composer init
composer require responsive-sk/slim4-paths

# Add testing
composer require --dev phpunit/phpunit
cp ../Blog/phpunit.xml .
cp ../Blog/run-module-tests.php .

# Run module tests
composer test
```

### **Benefits**

- ✅ **Independent Development** - Teams can work on modules separately
- ✅ **Separate CI/CD** - Module-specific GitHub Actions
- ✅ **Version Management** - Independent module versioning
- ✅ **Marketplace Ready** - Modules can be published as packages
- ✅ **Path Safety** - Uses `responsive-sk/slim4-paths` for secure operations

## 🛠️ Development Tools

### **Code Quality**

```bash
# Route discovery
php bin/route-list.php

# Environment validation
php bin/validate-env.php

# Key generation
php bin/generate-keys.php

# Module isolation check
php -r "
\$moduleManager = \$container->get('ModuleManager');
\$info = \$moduleManager->getModuleIsolationInfo('Blog');
var_dump(\$info);
"
```

### **Debugging**

```bash
# Enable debug mode
echo "APP_DEBUG=true" >> .env

# View logs
tail -f var/logs/app.log

# Check module status
php bin/route-list.php | grep -i blog
```

## 📚 Documentation

- [**Architecture Guide**](docs/ARCHITECTURE.md) - Triple Architecture details
- [**Module Development**](docs/MODULES.md) - Creating and managing modules
- [**Security Guide**](docs/SECURITY.md) - Security best practices
- [**Deployment Guide**](docs/DEPLOYMENT.md) - Production deployment
- [**Full Module Isolation**](docs/MODULE_ISOLATION.md) - Enterprise module development
- [**API Documentation**](docs/API.md) - REST API reference

## 🚀 Production Deployment

### **Quick Deployment**

```bash
# 1. Generate secure keys
php bin/generate-keys.php

# 2. Configure production environment
cp .env.example .env
# Edit .env with production values

# 3. Validate environment
php bin/validate-env.php

# 4. Deploy
composer install --no-dev --optimize-autoloader
composer deploy:prod

# 5. Verify deployment
curl https://yourdomain.com/health
```

### **Production Checklist**

- ✅ **Environment**: `APP_ENV=prod`, `APP_DEBUG=false`
- ✅ **Security**: Strong JWT_SECRET (64+ characters)
- ✅ **Database**: Production database configured
- ✅ **Sessions**: Secure session configuration
- ✅ **HTTPS**: SSL/TLS enabled
- ✅ **Monitoring**: Health endpoints configured
- ✅ **Logging**: Production log levels set

## 🌟 Live Demo

**Production Instance**: [https://boot.responsive.sk/](https://boot.responsive.sk/)

- **Homepage**: Framework overview and features
- **Blog**: [https://boot.responsive.sk/blog](https://boot.responsive.sk/blog)
- **API Status**: [https://boot.responsive.sk/api/status](https://boot.responsive.sk/api/status)
- **Health Check**: [https://boot.responsive.sk/health](https://boot.responsive.sk/health)

## 🤝 Contributing

### **Framework Contributions**

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Run tests: `composer test`
4. Commit changes: `git commit -m 'Add amazing feature'`
5. Push to branch: `git push origin feature/amazing-feature`
6. Open Pull Request

### **Module Contributions**

1. Create isolated module in `src/Modules/Optional/`
2. Add module-specific tests and CI
3. Document module in README.md
4. Submit as separate package or PR

### **Development Guidelines**

- ✅ **PSR-12** code style
- ✅ **PHPStan Level 8** static analysis
- ✅ **90%+ test coverage** requirement
- ✅ **Semantic versioning** for releases
- ✅ **Conventional commits** for changelog

## 📊 Project Statistics

- **Framework Version**: 0.9.0 (Release Candidate)
- **PHP Requirement**: 8.2+
- **Dependencies**: 20+ packages
- **Test Coverage**: 90%+
- **Code Quality**: PHPStan Level 8
- **Modules**: 8 Core + 1 Optional (Blog)
- **Routes**: 29 endpoints
- **Documentation**: 6 guides

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🔗 Links

- **GitHub Repository**: [https://github.com/responsive-sk/hdm-boot](https://github.com/responsive-sk/hdm-boot)
- **Live Demo**: [https://boot.responsive.sk/](https://boot.responsive.sk/)
- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/responsive-sk/hdm-boot/issues)
- **Discussions**: [GitHub Discussions](https://github.com/responsive-sk/hdm-boot/discussions)
- **Packagist**: [responsive-sk/hdm-boot](https://packagist.org/packages/responsive-sk/hdm-boot)

## 🏆 Acknowledgments

- **Slim Framework** - Micro framework foundation
- **PHP-DI** - Dependency injection container
- **Monolog** - Logging library
- **PHPUnit** - Testing framework
- **responsive.sk** - Development team

---

**HDM Boot Framework** - Enterprise PHP development with Triple Architecture

*Built with ❤️ by the HDM Boot Team*
