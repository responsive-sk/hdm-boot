# Coding Standards

## Overview

MVA Bootstrap dodržiava PSR-12 štandard kódovania s niekoľkými dodatočnými pravidlami.

## 📝 Základné Pravidlá

### 1. PHP Verzia
- Minimálna verzia PHP: 8.3
- Používame všetky nové PHP 8.3 features

### 2. Namespace Konvencie
```php
namespace MvaBootstrap\Modules\Core\User;
namespace MvaBootstrap\Modules\Core\Security;
```

### 3. Class Konvencie
```php
final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EventDispatcher $eventDispatcher
    ) {}
}
```

### 4. Interface Konvencie
```php
interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function save(User $user): void;
}
```

## 🔍 Code Style

### 1. Spacing & Indentation
- Používame 4 medzery (nie tabulátory)
- Jeden prázdny riadok medzi metódami
- Žiadne trailing whitespace

### 2. Naming
- Classes: PascalCase
- Methods: camelCase
- Properties: camelCase
- Constants: UPPER_CASE

### 3. Type Hints
```php
public function createUser(
    string $email,
    string $password
): User {
    // implementation
}
```

## 🧪 Testing Standards

### 1. Test Naming
```php
public function testUserCreationWithValidData(): void
public function testUserCreationFailsWithInvalidEmail(): void
```

### 2. Test Organization
```php
class UserServiceTest extends TestCase
{
    private UserService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(UserService::class);
    }
}
```

## 🏗 Architecture Standards

### 1. Clean Architecture
- Dodržiavame vrstvovú architektúru
- Dependency Rule: závislosti smerujú dovnútra
- Interface Segregation: malé, špecifické rozhrania

### 2. SOLID Principles
```php
// Single Responsibility
final class UserPasswordHasher
{
    public function hash(string $password): string
    public function verify(string $password, string $hash): bool
}

// Open/Closed
interface EventHandlerInterface
{
    public function handle(EventInterface $event): void;
}

// Liskov Substitution
abstract class BaseRepository
{
    abstract public function find(string $id): ?Entity;
}

// Interface Segregation
interface ReadableRepositoryInterface
{
    public function find(string $id): ?Entity;
    public function findAll(): array;
}

// Dependency Inversion
final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository
    ) {}
}
```

## 📊 Documentation Standards

### 1. PHPDoc
```php
/**
 * Creates a new user account.
 *
 * @param string $email    User's email address
 * @param string $password Raw password to hash
 * @return User           Created user entity
 * @throws UserExistsException If email already exists
 */
public function createUser(string $email, string $password): User
```

### 2. README Files
- Každý modul má svoj README.md
- Dokumentácia API endpointov
- Príklady použitia

## 🔒 Security Standards

### 1. Input Validation
```php
public function validateInput(array $data): void
{
    $validator = new Validator();
    $validator->validate($data, [
        'email' => ['required', 'email'],
        'password' => ['required', 'min:12']
    ]);
}
```

### 2. SQL Injection Prevention
```php
// Správne
$user = $queryBuilder
    ->select('*')
    ->from('users')
    ->where('email = :email')
    ->setParameter('email', $email)
    ->execute()
    ->fetch();

// Nesprávne
$query = "SELECT * FROM users WHERE email = '$email'";
```

## 🚀 Performance Standards

### 1. Database
- Používame indexy
- Obmedzujeme počet queries
- Implementujeme caching

### 2. Caching
```php
public function getData(string $key): mixed
{
    return $this->cache->remember($key, 3600, function () {
        return $this->expensiveOperation();
    });
}
```

## 👥 Code Review Standards

### 1. Pull Request Template
```markdown
## Popis zmien
- Čo sa zmenilo
- Prečo sa to zmenilo

## Checklist
- [ ] Testy prešli
- [ ] Dokumentácia aktualizovaná
- [ ] Code style dodržaný
- [ ] Security review
```

### 2. Review Process
1. Automatické testy
2. Code style check
3. Security review
4. Performance review
5. Documentation review
