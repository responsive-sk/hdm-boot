# Module Development Guide

Komplexný sprievodca vývojom nových modulov v HDM Boot aplikácii.

## 🏗️ Prehľad modulárnej architektúry

HDM Boot používa **Modular Monolith** architektúru s jasne oddelenými modulmi:

```
src/Modules/
├── Core/           # Základné moduly (User, Security, Language)
├── Optional/       # Voliteľné moduly (Blog, CMS)
└── Custom/         # Vlastné moduly
```

## 📁 Štruktúra modulu

### Základná štruktúra
```
src/Modules/Custom/YourModule/
├── config.php                 # Konfigurácia modulu
├── composer.json              # Závislosti modulu
├── Domain/                    # Doménová logika
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   └── Event/
├── Application/               # Aplikačná vrstva
│   ├── UseCase/
│   ├── Command/
│   ├── Query/
│   └── Handler/
├── Infrastructure/            # Infraštruktúrna vrstva
│   ├── Repository/
│   ├── Service/
│   └── Adapter/
├── Presentation/              # Prezentačná vrstva
│   ├── Controller/
│   ├── Middleware/
│   └── Response/
├── tests/                     # Testy modulu
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
└── docs/                      # Dokumentácia modulu
    ├── README.md
    ├── API.md
    └── TESTING.md
```

## 🚀 Vytvorenie nového modulu

### 1. Použitie template generátora

```bash
# Vytvor nový modul z template
php bin/create-module.php Custom/ProductCatalog

# Alebo manuálne vytvor štruktúru
mkdir -p src/Modules/Custom/ProductCatalog/{Domain,Application,Infrastructure,Presentation}
```

### 2. Konfigurácia modulu (config.php)

```php
<?php
// src/Modules/Custom/ProductCatalog/config.php

return [
    'name' => 'ProductCatalog',
    'version' => '1.0.0',
    'description' => 'Product catalog management module',
    'author' => 'Your Name',
    
    // Závislosti na iných moduloch
    'dependencies' => [
        'Core/User',
        'Core/Security'
    ],
    
    // Služby poskytované modulom
    'services' => [
        'product.repository' => \YourModule\Infrastructure\Repository\ProductRepository::class,
        'product.service' => \YourModule\Domain\Service\ProductService::class,
    ],
    
    // Routy modulu
    'routes' => [
        'api' => __DIR__ . '/routes/api.php',
        'web' => __DIR__ . '/routes/web.php',
    ],
    
    // Middleware
    'middleware' => [
        'product.auth' => \YourModule\Presentation\Middleware\ProductAuthMiddleware::class,
    ],
    
    // Event listenery
    'listeners' => [
        'product.created' => [
            \YourModule\Application\Handler\ProductCreatedHandler::class,
        ],
    ],
    
    // Migrácie
    'migrations' => __DIR__ . '/migrations',
    
    // Templaty
    'templates' => __DIR__ . '/templates',
    
    // Jazykové súbory
    'translations' => __DIR__ . '/translations',
];
```

### 3. Composer.json pre modul

```json
{
    "name": "hdm-boot/product-catalog-module",
    "description": "Product catalog management module for HDM Boot",
    "type": "hdm-boot-module",
    "require": {
        "php": ">=8.3",
        "ramsey/uuid": "^4.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "HdmBoot\\Modules\\Custom\\ProductCatalog\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "HdmBoot\\Modules\\Custom\\ProductCatalog\\Tests\\": "tests/"
        }
    }
}
```

## 🏛️ Domain Layer (Doménová vrstva)

### Entity
```php
<?php
// src/Modules/Custom/ProductCatalog/Domain/Entity/Product.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Domain\Entity;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Product
{
    private function __construct(
        private readonly UuidInterface $id,
        private string $name,
        private string $description,
        private int $price,
        private bool $active = true,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {}

    public static function create(
        string $name,
        string $description,
        int $price
    ): self {
        return new self(
            id: Uuid::uuid4(),
            name: $name,
            description: $description,
            price: $price
        );
    }

    // Getters
    public function getId(): UuidInterface { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): int { return $this->price; }
    public function isActive(): bool { return $this->active; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // Business methods
    public function updatePrice(int $newPrice): void
    {
        if ($newPrice < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
        $this->price = $newPrice;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }
}
```

### Repository Interface
```php
<?php
// src/Modules/Custom/ProductCatalog/Domain/Repository/ProductRepositoryInterface.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Domain\Repository;

use HdmBoot\Modules\Custom\ProductCatalog\Domain\Entity\Product;
use Ramsey\Uuid\UuidInterface;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function findById(UuidInterface $id): ?Product;
    public function findByName(string $name): ?Product;
    public function findActive(): array;
    public function delete(UuidInterface $id): void;
}
```

### Domain Service
```php
<?php
// src/Modules/Custom/ProductCatalog/Domain/Service/ProductService.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Domain\Service;

use HdmBoot\Modules\Custom\ProductCatalog\Domain\Entity\Product;
use HdmBoot\Modules\Custom\ProductCatalog\Domain\Repository\ProductRepositoryInterface;
use HdmBoot\SharedKernel\Event\EventDispatcherInterface;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function createProduct(string $name, string $description, int $price): Product
    {
        // Business validation
        if ($this->productRepository->findByName($name)) {
            throw new \DomainException('Product with this name already exists');
        }

        $product = Product::create($name, $description, $price);
        $this->productRepository->save($product);

        // Dispatch domain event
        $this->eventDispatcher->dispatch(new ProductCreatedEvent($product));

        return $product;
    }
}
```

## 🎯 Application Layer (Aplikačná vrstva)

### Use Case
```php
<?php
// src/Modules/Custom/ProductCatalog/Application/UseCase/CreateProduct.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Application\UseCase;

use HdmBoot\Modules\Custom\ProductCatalog\Domain\Service\ProductService;

final class CreateProduct
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function execute(CreateProductCommand $command): CreateProductResponse
    {
        $product = $this->productService->createProduct(
            $command->name,
            $command->description,
            $command->price
        );

        return new CreateProductResponse(
            id: $product->getId()->toString(),
            name: $product->getName(),
            price: $product->getPrice()
        );
    }
}
```

### Command & Response
```php
<?php
// src/Modules/Custom/ProductCatalog/Application/Command/CreateProductCommand.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Application\Command;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public int $price
    ) {}
}

// src/Modules/Custom/ProductCatalog/Application/Response/CreateProductResponse.php
final readonly class CreateProductResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public int $price
    ) {}
}
```

## 🔧 Infrastructure Layer (Infraštruktúrna vrstva)

### Repository Implementation
```php
<?php
// src/Modules/Custom/ProductCatalog/Infrastructure/Repository/ProductRepository.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Infrastructure\Repository;

use HdmBoot\Modules\Custom\ProductCatalog\Domain\Entity\Product;
use HdmBoot\Modules\Custom\ProductCatalog\Domain\Repository\ProductRepositoryInterface;
use Ramsey\Uuid\UuidInterface;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly \PDO $pdo
    ) {}

    public function save(Product $product): void
    {
        $sql = 'INSERT OR REPLACE INTO products (id, name, description, price, active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $product->getId()->toString(),
            $product->getName(),
            $product->getDescription(),
            $product->getPrice(),
            $product->isActive() ? 1 : 0,
            $product->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    public function findById(UuidInterface $id): ?Product
    {
        $sql = 'SELECT * FROM products WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id->toString()]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? $this->hydrate($data) : null;
    }

    private function hydrate(array $data): Product
    {
        // Hydrate entity from database data
        // Implementation depends on your entity structure
    }
}
```

## 🎨 Presentation Layer (Prezentačná vrstva)

### Controller
```php
<?php
// src/Modules/Custom/ProductCatalog/Presentation/Controller/ProductController.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Presentation\Controller;

use HdmBoot\Modules\Custom\ProductCatalog\Application\UseCase\CreateProduct;
use HdmBoot\Modules\Custom\ProductCatalog\Application\Command\CreateProductCommand;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProductController
{
    public function __construct(
        private readonly CreateProduct $createProduct
    ) {}

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        $command = new CreateProductCommand(
            name: $data['name'],
            description: $data['description'],
            price: (int) $data['price']
        );

        $result = $this->createProduct->execute($command);

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $result
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

## 🧪 Testing

### Unit Test
```php
<?php
// tests/Unit/Domain/Service/ProductServiceTest.php

namespace HdmBoot\Modules\Custom\ProductCatalog\Tests\Unit\Domain\Service;

use PHPUnit\Framework\TestCase;
use HdmBoot\Modules\Custom\ProductCatalog\Domain\Service\ProductService;

final class ProductServiceTest extends TestCase
{
    private ProductService $productService;
    private ProductRepositoryInterface $productRepository;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->productService = new ProductService($this->productRepository);
    }

    public function testCreateProductSuccess(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('findByName')
            ->willReturn(null);

        $product = $this->productService->createProduct('Test Product', 'Description', 1000);

        $this->assertEquals('Test Product', $product->getName());
        $this->assertEquals(1000, $product->getPrice());
    }
}
```

## 📋 Module Checklist

### Pred vytvorením modulu:
- [ ] Definovať doménu a zodpovednosti
- [ ] Navrhnúť API a rozhrania
- [ ] Identifikovať závislosti
- [ ] Naplánovať migrácie

### Počas vývoja:
- [ ] Dodržať Clean Architecture
- [ ] Implementovať všetky vrstvy
- [ ] Napísať testy (min. 80% coverage)
- [ ] Dokumentovať API
- [ ] Validovať bezpečnosť

### Pred nasadením:
- [ ] Code review
- [ ] Security audit
- [ ] Performance testing
- [ ] Documentation review
- [ ] Integration testing

## 🔗 Ďalšie zdroje

- [Clean Architecture Guide](../architecture/clean-architecture.md)
- [Module Templates](../templates/README.md)
- [Testing Guide](testing-guide.md)
- [Security Best Practices](security-practices.md)
