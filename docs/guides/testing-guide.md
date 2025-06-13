# Testing Guide

## 🧪 Prehľad Testovania

MVA Bootstrap používa viacúrovňovú stratégiu testovania:

### 1. Unit Tests [P0]
- Testovanie jednotlivých komponentov
- PHPUnit framework
- Mockované závislosti
- `/tests/Unit`

### 2. Integration Tests [P0]
- Testovanie spolupráce komponentov
- Reálne závislosti
- Database testing
- `/tests/Integration`

### 3. Feature Tests [P1]
- End-to-end testy
- API testy
- Browser testy
- `/tests/Feature`

## 📝 Príklady Testov

### Unit Test
```php
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock dependencies
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        
        // Create service with mocked dependencies
        $this->userService = new UserService($this->userRepository);
    }

    public function testCreateUserWithValidData(): void
    {
        // Arrange
        $email = 'test@example.com';
        $password = 'password123';
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
            
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);

        // Act
        $result = $this->userService->createUser($email, $password);

        // Assert
        $this->assertTrue($result->isSuccess());
    }
}
```

### Integration Test
```php
class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Real database connection
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec(file_get_contents(__DIR__ . '/../../database/schema.sql'));
        
        $this->repository = new UserRepository($this->pdo);
    }

    public function testSaveAndFindUser(): void
    {
        // Arrange
        $user = new User('test@example.com', 'password123');

        // Act
        $this->repository->save($user);
        $found = $this->repository->findByEmail('test@example.com');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($user->getEmail(), $found->getEmail());
    }
}
```

### Feature Test
```php
class AuthenticationTest extends TestCase
{
    public function testUserCanLogin(): void
    {
        // Arrange
        $email = 'test@example.com';
        $password = 'password123';
        
        $this->createUser($email, $password);

        // Act
        $response = $this->post('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token']]);
    }
}
```

## 🛠️ Testovacia Konfigurácia

### 1. PHPUnit Config
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src/</directory>
        </include>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

### 2. Test Database
```php
// tests/bootstrap.php
require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../', '.env.testing');
$dotenv->load();

// Setup test database
$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
```

## 🎯 Best Practices

### 1. Naming Conventions
```php
public function testMethodDoesSpecificThing(): void
public function testMethodFailsUnderSpecificCondition(): void
```

### 2. Test Structure
```php
// Arrange - pripraviť dáta a závislosti
$user = new User('test@example.com');
$service = new UserService($mockRepository);

// Act - vykonať testovanú akciu
$result = $service->createUser($user);

// Assert - overiť výsledok
$this->assertTrue($result->isSuccess());
```

### 3. Mocking
```php
// Mock creation
$repository = $this->createMock(UserRepositoryInterface::class);

// Expectation
$repository->expects($this->once())
    ->method('save')
    ->with($this->isInstanceOf(User::class))
    ->willReturn(true);
```

## 🔍 Coverage & Quality

### 1. Code Coverage
```bash
# Generate coverage report
composer test-coverage

# View report
open var/coverage/index.html
```

### 2. Mutation Testing
```bash
# Run mutation tests
composer test:mutation
```

### 3. Static Analysis
```bash
# Run PHPStan
composer stan
```

## 📊 Test Categories

### 1. Business Logic Tests [P0]
- Domain objects
- Services
- Use cases

### 2. Infrastructure Tests [P0]
- Database repositories
- Cache adapters
- External services

### 3. API Tests [P1]
- Endpoints
- Request/response
- Authentication
- Validation

### 4. Security Tests [P0]
- Authentication
- Authorization
- Input validation
- CSRF protection

## 🚀 Running Tests

### Full Test Suite
```bash
composer test
```

### Specific Tests
```bash
# Unit tests only
composer test:unit

# Integration tests
composer test:integration

# Feature tests
composer test:feature
```

### With Coverage
```bash
composer test:coverage
```

## 🔄 Continuous Integration

### GitHub Actions
```yaml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: composer test
      
    - name: Upload coverage
      uses: codecov/codecov-action@v1
```

## 📝 Documentation

### 1. Test Documentation
```php
/**
 * @test
 * @group user
 * @covers \App\User\UserService::createUser
 */
public function userCreationSucceedsWithValidData(): void
{
    // test implementation
}
```

### 2. Test Data Factories
```php
class UserFactory
{
    public static function make(array $attributes = []): User
    {
        return new User(
            email: $attributes['email'] ?? 'test@example.com',
            password: $attributes['password'] ?? 'password123'
        );
    }
}
```

## ✅ Checklist

### Pred Push
- [ ] Všetky testy prešli
- [ ] Code coverage > 80%
- [ ] PHPStan level 8 prešiel
- [ ] Mutation score > 60%

### Pred Release
- [ ] Integration testy prešli
- [ ] Feature testy prešli
- [ ] Security testy prešli
- [ ] Performance testy prešli
