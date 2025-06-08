# Module Development Guide

This guide explains how to create and integrate modules in the MVA Bootstrap Application.

## 📦 Module System Overview

The MVA Bootstrap Application uses a modular architecture that allows for:
- **Core Modules** - Essential functionality (User, Security)
- **Optional Modules** - Feature extensions (Article, Blog, Shop)
- **Dynamic Loading** - Modules loaded based on configuration
- **Isolation** - Modules are self-contained and loosely coupled

## 🏗 Module Structure

### Standard Module Layout

```
modules/
├── Core/                    # Required modules
│   └── ModuleName/
│       ├── config.php       # Module configuration
│       ├── routes.php       # HTTP routes
│       ├── Actions/         # HTTP request handlers
│       │   ├── CreateAction.php
│       │   ├── ListAction.php
│       │   └── ViewAction.php
│       ├── Services/        # Business logic
│       │   └── ModuleService.php
│       ├── Repository/      # Data access layer
│       │   ├── ModuleRepositoryInterface.php
│       │   └── SqliteModuleRepository.php
│       ├── Domain/          # Domain objects
│       │   ├── Entities/
│       │   └── ValueObjects/
│       ├── Infrastructure/  # External integrations
│       └── Middleware/      # Module-specific middleware
└── Optional/               # Feature modules
    └── ModuleName/
        └── [same structure]
```

## 🔧 Creating a New Module

### Step 1: Module Directory Structure

```bash
# Create module directory
mkdir -p modules/Optional/YourModule/{Actions,Services,Repository,Domain,Infrastructure,Middleware}
```

### Step 2: Module Configuration

Create `modules/Optional/YourModule/config.php`:

```php
<?php

declare(strict_types=1);

use DI\Container;
use YourModule\Services\YourModuleService;
use YourModule\Repository\YourModuleRepositoryInterface;
use YourModule\Repository\SqliteYourModuleRepository;

return [
    'name' => 'YourModule',
    'version' => '1.0.0',
    'description' => 'Description of your module',
    
    // Service definitions
    'services' => [
        YourModuleService::class => function (Container $c) {
            return new YourModuleService(
                $c->get(YourModuleRepositoryInterface::class)
            );
        },
        
        YourModuleRepositoryInterface::class => function (Container $c) {
            return new SqliteYourModuleRepository(
                $c->get(PDO::class)
            );
        },
    ],
    
    // Module dependencies
    'dependencies' => [
        'User',     // Requires User module
        'Security', // Requires Security module
    ],
    
    // Middleware (optional)
    'middleware' => [
        YourModule\Middleware\YourModuleMiddleware::class,
    ],
    
    // Database migrations (optional)
    'migrations' => [
        __DIR__ . '/Infrastructure/migrations/',
    ],
];
```

### Step 3: Module Routes

Create `modules/Optional/YourModule/routes.php`:

```php
<?php

declare(strict_types=1);

use Slim\App;
use YourModule\Actions\{CreateAction, ListAction, ViewAction};

return function (App $app): void {
    $app->group('/your-module', function ($group) {
        // List items
        $group->get('', ListAction::class)
            ->setName('your-module.list');
        
        // Create item form
        $group->get('/create', CreateAction::class)
            ->setName('your-module.create.form');
        
        // Create item
        $group->post('/create', CreateAction::class)
            ->setName('your-module.create');
        
        // View single item
        $group->get('/{id}', ViewAction::class)
            ->setName('your-module.view');
    });
};
```

### Step 4: Domain Objects

Create domain entities and value objects:

```php
// modules/Optional/YourModule/Domain/Entities/YourEntity.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Domain\Entities;

use MvaBootstrap\Modules\Optional\YourModule\Domain\ValueObjects\YourEntityId;

final class YourEntity
{
    public function __construct(
        private YourEntityId $id,
        private string $title,
        private string $content,
        private \DateTimeImmutable $createdAt
    ) {
    }

    public function getId(): YourEntityId
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

```php
// modules/Optional/YourModule/Domain/ValueObjects/YourEntityId.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Domain\ValueObjects;

use InvalidArgumentException;

final class YourEntityId
{
    public function __construct(private readonly string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('YourEntity ID cannot be empty');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Step 5: Repository Layer

```php
// modules/Optional/YourModule/Repository/YourModuleRepositoryInterface.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Repository;

use MvaBootstrap\Modules\Optional\YourModule\Domain\Entities\YourEntity;
use MvaBootstrap\Modules\Optional\YourModule\Domain\ValueObjects\YourEntityId;

interface YourModuleRepositoryInterface
{
    public function findById(YourEntityId $id): ?YourEntity;
    public function findAll(): array;
    public function save(YourEntity $entity): void;
    public function delete(YourEntityId $id): void;
}
```

```php
// modules/Optional/YourModule/Repository/SqliteYourModuleRepository.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Repository;

use MvaBootstrap\Modules\Optional\YourModule\Domain\Entities\YourEntity;
use MvaBootstrap\Modules\Optional\YourModule\Domain\ValueObjects\YourEntityId;
use PDO;

final class SqliteYourModuleRepository implements YourModuleRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(YourEntityId $id): ?YourEntity
    {
        $stmt = $this->pdo->prepare('SELECT * FROM your_entities WHERE id = ?');
        $stmt->execute([$id->getValue()]);
        
        $data = $stmt->fetch();
        if (!$data) {
            return null;
        }

        return $this->mapToEntity($data);
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM your_entities ORDER BY created_at DESC');
        $entities = [];
        
        while ($data = $stmt->fetch()) {
            $entities[] = $this->mapToEntity($data);
        }

        return $entities;
    }

    public function save(YourEntity $entity): void
    {
        $stmt = $this->pdo->prepare('
            INSERT OR REPLACE INTO your_entities (id, title, content, created_at)
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $entity->getId()->getValue(),
            $entity->getTitle(),
            $entity->getContent(),
            $entity->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(YourEntityId $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM your_entities WHERE id = ?');
        $stmt->execute([$id->getValue()]);
    }

    private function mapToEntity(array $data): YourEntity
    {
        return new YourEntity(
            new YourEntityId($data['id']),
            $data['title'],
            $data['content'],
            new \DateTimeImmutable($data['created_at'])
        );
    }
}
```

### Step 6: Service Layer

```php
// modules/Optional/YourModule/Services/YourModuleService.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Services;

use MvaBootstrap\Modules\Optional\YourModule\Domain\Entities\YourEntity;
use MvaBootstrap\Modules\Optional\YourModule\Domain\ValueObjects\YourEntityId;
use MvaBootstrap\Modules\Optional\YourModule\Repository\YourModuleRepositoryInterface;
use Ramsey\Uuid\Uuid;

final class YourModuleService
{
    public function __construct(
        private readonly YourModuleRepositoryInterface $repository
    ) {
    }

    public function createEntity(string $title, string $content): YourEntity
    {
        $entity = new YourEntity(
            new YourEntityId(Uuid::uuid4()->toString()),
            $title,
            $content,
            new \DateTimeImmutable()
        );

        $this->repository->save($entity);
        
        return $entity;
    }

    public function getEntity(string $id): ?YourEntity
    {
        return $this->repository->findById(new YourEntityId($id));
    }

    public function getAllEntities(): array
    {
        return $this->repository->findAll();
    }

    public function deleteEntity(string $id): void
    {
        $this->repository->delete(new YourEntityId($id));
    }
}
```

### Step 7: Action Layer

```php
// modules/Optional/YourModule/Actions/ListAction.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\YourModule\Actions;

use MvaBootstrap\Modules\Optional\YourModule\Services\YourModuleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListAction
{
    public function __construct(
        private readonly YourModuleService $service
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $entities = $this->service->getAllEntities();
        
        $data = [
            'entities' => array_map(function ($entity) {
                return [
                    'id' => $entity->getId()->getValue(),
                    'title' => $entity->getTitle(),
                    'content' => $entity->getContent(),
                    'created_at' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $entities),
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

## 🔄 Module Loading Process

### Automatic Loading

Modules are automatically loaded by the `ModuleManager`:

1. **Core Modules** - Always loaded first
2. **Optional Modules** - Loaded based on `ENABLED_MODULES` environment variable
3. **Dependency Resolution** - Dependencies loaded in correct order
4. **Service Registration** - Module services registered in DI container
5. **Route Registration** - Module routes added to application

### Manual Module Control

```bash
# Enable modules in .env
ENABLED_MODULES="Article,Blog,Shop"

# Or programmatically
$moduleManager->loadModule('YourModule', 'Optional');
```

## 🧪 Module Testing

### Test Structure

```
tests/
├── Unit/
│   └── Modules/
│       └── Optional/
│           └── YourModule/
│               ├── Services/
│               ├── Repository/
│               └── Domain/
├── Integration/
│   └── Modules/
│       └── YourModule/
└── Fixtures/
    └── YourModule/
```

### Example Test

```php
// tests/Unit/Modules/Optional/YourModule/Services/YourModuleServiceTest.php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Unit\Modules\Optional\YourModule\Services;

use MvaBootstrap\Modules\Optional\YourModule\Services\YourModuleService;
use MvaBootstrap\Modules\Optional\YourModule\Repository\YourModuleRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class YourModuleServiceTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $repository = $this->createMock(YourModuleRepositoryInterface::class);
        $service = new YourModuleService($repository);

        $entity = $service->createEntity('Test Title', 'Test Content');

        $this->assertEquals('Test Title', $entity->getTitle());
        $this->assertEquals('Test Content', $entity->getContent());
    }
}
```

## 📚 Best Practices

### Module Design

1. **Single Responsibility** - Each module has one clear purpose
2. **Loose Coupling** - Minimal dependencies between modules
3. **High Cohesion** - Related functionality grouped together
4. **Interface Segregation** - Small, focused interfaces
5. **Dependency Inversion** - Depend on abstractions, not concretions

### Code Organization

1. **Namespace Convention** - `MvaBootstrap\Modules\{Type}\{ModuleName}`
2. **File Naming** - Clear, descriptive file names
3. **Directory Structure** - Consistent across all modules
4. **Documentation** - Comprehensive inline documentation
5. **Type Safety** - Strict types and proper type hints

### Security Considerations

1. **Input Validation** - Validate all inputs at module boundaries
2. **Authorization** - Check permissions for module operations
3. **Path Security** - Use SecurePathHelper for file operations
4. **SQL Injection** - Use prepared statements in repositories
5. **XSS Prevention** - Escape output in templates

### Performance

1. **Lazy Loading** - Load services only when needed
2. **Efficient Queries** - Optimize database queries
3. **Caching** - Cache expensive operations
4. **Memory Management** - Avoid memory leaks
5. **Profiling** - Monitor module performance

## 🔧 Module Configuration

### Environment-Specific Settings

```php
// Module config can access environment
'database_table_prefix' => $_ENV['DB_PREFIX'] ?? '',
'cache_enabled' => ($_ENV['CACHE_ENABLED'] ?? 'true') === 'true',
'debug_mode' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
```

### Feature Flags

```php
// Enable/disable module features
'features' => [
    'advanced_search' => true,
    'export_functionality' => false,
    'real_time_updates' => true,
],
```

This guide provides a comprehensive foundation for developing modules in the MVA Bootstrap Application. Follow these patterns and best practices to create maintainable, secure, and performant modules.
