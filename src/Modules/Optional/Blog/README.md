# HDM Boot Blog Module

**Standalone blog functionality with Hexagonal Architecture for HDM Boot Framework.**

## 🚀 Features

- ✅ **Hexagonal Architecture** - Clean separation of concerns
- ✅ **Domain-Driven Design** - Rich domain models
- ✅ **RESTful API** - Complete blog API endpoints
- ✅ **Web Interface** - User-friendly blog pages
- ✅ **Full CRUD** - Create, read, update, delete articles
- ✅ **Categories & Tags** - Content organization
- ✅ **Search** - Article search functionality
- ✅ **Pagination** - Efficient content browsing

## 📦 Installation

### As HDM Boot Module
```bash
# Already included in HDM Boot framework
composer create-project responsive-sk/hdm-boot my-project
```

### As Standalone Package
```bash
composer require hdm-boot/blog-module
```

## 🏗️ Architecture

```
src/
├── Application/
│   ├── Commands/     # Use cases (Create, Update, Delete)
│   ├── Queries/      # Read operations
│   └── Services/     # Application services
├── Domain/
│   ├── Entities/     # Article, Category, Tag
│   ├── ValueObjects/ # ArticleId, Title, Content
│   └── Repositories/ # Domain interfaces
├── Infrastructure/
│   ├── Persistence/  # Database implementations
│   ├── Web/         # Controllers, routes
│   └── Templates/   # View templates
└── Tests/
    ├── Unit/        # Domain tests
    ├── Integration/ # Infrastructure tests
    └── Functional/  # End-to-end tests
```

## 🧪 Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Static analysis
composer analyse

# Code style check
composer cs:check

# Fix code style
composer cs:fix

# Full CI pipeline
composer ci
```

## 📊 Test Coverage

- **Unit Tests:** Domain logic, value objects, entities
- **Integration Tests:** Repository implementations, database
- **Functional Tests:** API endpoints, web pages
- **Target Coverage:** 90%+

## 🔧 Configuration

### Module Configuration
```php
// config.php
return [
    'blog' => [
        'articles_per_page' => 10,
        'enable_comments' => false,
        'enable_tags' => true,
        'enable_categories' => true,
    ],
];
```

### Routes
```php
// routes.php
$app->group('/blog', function (RouteCollectorProxy $group) {
    $group->get('', BlogController::class . ':index');
    $group->get('/{slug}', BlogController::class . ':show');
});

$app->group('/api/blog', function (RouteCollectorProxy $group) {
    $group->get('/articles', BlogApiController::class . ':list');
    $group->post('/articles', BlogApiController::class . ':create');
});
```

## 🚀 Usage

### Web Interface
```
GET  /blog              # Blog homepage
GET  /blog/{slug}       # Article detail
GET  /blog/categories   # Categories list
GET  /blog/tags         # Tags list
```

### API Endpoints
```
GET    /api/blog/articles           # List articles
POST   /api/blog/articles           # Create article
GET    /api/blog/articles/{id}      # Get article
PUT    /api/blog/articles/{id}      # Update article
DELETE /api/blog/articles/{id}      # Delete article
```

## 🔌 Module Integration

### HDM Boot Framework
```php
// Automatically loaded via ModuleManager
$modules = ['Blog']; // In .env ENABLED_MODULES
```

### Standalone Usage
```php
// Manual integration
$container->set(BlogModule::class, new BlogModule());
$blogModule = $container->get(BlogModule::class);
$blogModule->initialize();
```

## 🏷️ Versioning

- **Current Version:** 2.0.0
- **HDM Boot Compatibility:** ^0.9.0
- **PHP Requirement:** ^8.2
- **Semantic Versioning:** Yes

## 🤝 Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Run tests: `composer ci`
4. Commit changes: `git commit -m 'Add amazing feature'`
5. Push to branch: `git push origin feature/amazing-feature`
6. Open Pull Request

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🔗 Links

- **HDM Boot Framework:** https://github.com/responsive-sk/hdm-boot
- **Documentation:** https://boot.responsive.sk/docs
- **Live Demo:** https://boot.responsive.sk/blog
- **Issues:** https://github.com/responsive-sk/hdm-boot/issues

---

**HDM Boot Blog Module** - Enterprise-grade blog functionality with clean architecture.
