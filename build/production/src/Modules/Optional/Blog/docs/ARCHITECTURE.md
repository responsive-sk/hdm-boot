# Blog Module Architecture

**Hexagonal Architecture + Domain-Driven Design implementation for HDM Boot Blog Module**

## 🏛️ Architecture Overview

The Blog module implements **Hexagonal Architecture** with **Domain-Driven Design** principles, providing a clean separation between business logic and infrastructure concerns.

## 📁 Directory Structure

```
src/Modules/Optional/Blog/
├── src/
│   ├── Application/           # Application Layer
│   │   ├── Commands/         # Write operations (Create, Update, Delete)
│   │   ├── Queries/          # Read operations (List, Get, Search)
│   │   └── Services/         # Application services
│   ├── Domain/               # Domain Layer
│   │   ├── Entities/         # Business entities
│   │   ├── ValueObjects/     # Immutable value objects
│   │   ├── Repositories/     # Repository interfaces
│   │   └── Services/         # Domain services
│   └── Infrastructure/       # Infrastructure Layer
│       ├── Persistence/      # Database implementations
│       ├── Web/             # HTTP controllers and routes
│       └── Templates/       # View templates
├── tests/                   # Module-specific tests
├── docs/                    # Module documentation
├── composer.json           # Module dependencies
├── phpunit.xml             # Testing configuration
└── README.md               # Module overview
```

## 🎯 Domain Layer

### **Entities**

#### **Article Entity**
```php
class Article
{
    private ArticleId $id;
    private Title $title;
    private Content $content;
    private Author $author;
    private PublishedAt $publishedAt;
    private Category $category;
    private Tags $tags;
    
    // Rich domain behavior
    public function publish(): void
    public function unpublish(): void
    public function updateContent(Content $content): void
    public function addTag(Tag $tag): void
}
```

#### **Category Entity**
```php
class Category
{
    private CategoryId $id;
    private CategoryName $name;
    private Slug $slug;
    private Description $description;
}
```

### **Value Objects**

#### **ArticleId**
```php
final readonly class ArticleId
{
    public function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('Article ID cannot be empty');
        }
    }
    
    public function toString(): string
    {
        return $this->value;
    }
}
```

#### **Title**
```php
final readonly class Title
{
    public function __construct(
        private string $value
    ) {
        if (strlen($value) < 3 || strlen($value) > 200) {
            throw new InvalidArgumentException('Title must be 3-200 characters');
        }
    }
}
```

### **Repository Interfaces**

#### **ArticleRepositoryInterface**
```php
interface ArticleRepositoryInterface
{
    public function save(Article $article): void;
    public function findById(ArticleId $id): ?Article;
    public function findBySlug(Slug $slug): ?Article;
    public function findPublished(int $limit = 10): array;
    public function findByCategory(CategoryId $categoryId): array;
    public function search(string $query): array;
    public function delete(ArticleId $id): void;
}
```

## 🔄 Application Layer

### **Commands (Write Operations)**

#### **CreateArticleCommand**
```php
final readonly class CreateArticleCommand
{
    public function __construct(
        public string $title,
        public string $content,
        public string $authorId,
        public string $categoryId,
        public array $tags = []
    ) {}
}
```

#### **CreateArticleHandler**
```php
class CreateArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private CategoryRepositoryInterface $categoryRepository
    ) {}
    
    public function handle(CreateArticleCommand $command): ArticleId
    {
        // 1. Validate business rules
        // 2. Create domain entities
        // 3. Save via repository
        // 4. Return result
    }
}
```

### **Queries (Read Operations)**

#### **GetArticleQuery**
```php
final readonly class GetArticleQuery
{
    public function __construct(
        public string $slug
    ) {}
}
```

#### **GetArticleHandler**
```php
class GetArticleHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository
    ) {}
    
    public function handle(GetArticleQuery $query): ?ArticleView
    {
        $article = $this->articleRepository->findBySlug(new Slug($query->slug));
        
        return $article ? ArticleView::fromEntity($article) : null;
    }
}
```

## 🔌 Infrastructure Layer

### **Persistence**

#### **SqliteArticleRepository**
```php
class SqliteArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private PDO $connection
    ) {}
    
    public function save(Article $article): void
    {
        // SQLite-specific implementation
    }
    
    public function findById(ArticleId $id): ?Article
    {
        // Query database and reconstruct entity
    }
}
```

### **Web Layer**

#### **BlogController**
```php
class BlogController
{
    public function __construct(
        private GetArticleHandler $getArticleHandler,
        private ListArticlesHandler $listArticlesHandler
    ) {}
    
    public function show(Request $request, Response $response, array $args): Response
    {
        $query = new GetArticleQuery($args['slug']);
        $article = $this->getArticleHandler->handle($query);
        
        if (!$article) {
            return $response->withStatus(404);
        }
        
        return $this->render($response, 'blog/article.php', ['article' => $article]);
    }
}
```

## 🧪 Testing Strategy

### **Unit Tests**
- **Domain Entities** - Business logic validation
- **Value Objects** - Immutability and validation
- **Domain Services** - Business rule enforcement

### **Integration Tests**
- **Repository Implementations** - Database operations
- **Application Handlers** - Use case execution
- **Web Controllers** - HTTP request/response

### **Functional Tests**
- **End-to-End Scenarios** - Complete user workflows
- **API Endpoints** - REST API functionality
- **Web Pages** - HTML rendering

## 🔄 Data Flow

### **Write Operation Flow**
```
HTTP Request → Controller → Command → Handler → Domain Entity → Repository → Database
```

### **Read Operation Flow**
```
HTTP Request → Controller → Query → Handler → Repository → View Model → Template → HTTP Response
```

## 🎯 Benefits

### **Hexagonal Architecture Benefits**
- ✅ **Testability** - Easy to test business logic in isolation
- ✅ **Flexibility** - Can swap infrastructure components
- ✅ **Maintainability** - Clear separation of concerns

### **Domain-Driven Design Benefits**
- ✅ **Rich Domain Model** - Business logic encapsulated in entities
- ✅ **Ubiquitous Language** - Shared vocabulary between developers and domain experts
- ✅ **Business Focus** - Code reflects business requirements

### **Module Isolation Benefits**
- ✅ **Independent Development** - Teams can work on modules separately
- ✅ **Independent Testing** - Module-specific test suites
- ✅ **Independent Deployment** - Modules can be updated separately

## 🔧 Configuration

### **Dependency Injection**
```php
// Blog module DI configuration
return [
    ArticleRepositoryInterface::class => DI\autowire(SqliteArticleRepository::class),
    CreateArticleHandler::class => DI\autowire(),
    GetArticleHandler::class => DI\autowire(),
    BlogController::class => DI\autowire(),
];
```

### **Route Configuration**
```php
// Blog module routes
$app->group('/blog', function (RouteCollectorProxy $group) {
    $group->get('', BlogController::class . ':index');
    $group->get('/{slug}', BlogController::class . ':show');
});
```

## 📊 Metrics

- **Cyclomatic Complexity**: < 10 per method
- **Test Coverage**: 90%+ target
- **PHPStan Level**: 8 (strict types)
- **Dependencies**: Minimal external dependencies
- **Performance**: < 100ms response time

---

**Blog Module Architecture** - Clean, testable, and maintainable code following enterprise patterns.
