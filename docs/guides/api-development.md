# API Development Guide

Komplexný sprievodca vývojom REST API v HDM Boot aplikácii.

## 🌐 API Architecture Overview

HDM Boot používa **RESTful API** architektúru s týmito princípmi:

- **Resource-based URLs** - `/api/users`, `/api/products`
- **HTTP Methods** - GET, POST, PUT, DELETE, PATCH
- **JSON Communication** - Request/Response v JSON formáte
- **Stateless** - Každý request je nezávislý
- **Versioned** - API verzie pre backward compatibility

## 📁 API Štruktúra

```
src/Modules/YourModule/
├── Presentation/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── UserApiController.php
│   │   │   │   └── ProductApiController.php
│   │   │   └── V2/
│   │   └── Web/
│   ├── Middleware/
│   │   ├── ApiAuthMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── ValidationMiddleware.php
│   ├── Request/
│   │   ├── CreateUserRequest.php
│   │   └── UpdateUserRequest.php
│   ├── Response/
│   │   ├── ApiResponse.php
│   │   ├── UserResponse.php
│   │   └── ErrorResponse.php
│   └── Transformer/
│       ├── UserTransformer.php
│       └── ProductTransformer.php
└── routes/
    ├── api.php
    └── web.php
```

## 🎯 API Controller Pattern

### Base API Controller

```php
<?php
// src/SharedKernel/Presentation/Controller/BaseApiController.php

namespace HdmBoot\SharedKernel\Presentation\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class BaseApiController
{
    protected function jsonResponse(
        ResponseInterface $response,
        mixed $data = null,
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        $payload = [
            'success' => $statusCode < 400,
            'data' => $data,
            'timestamp' => time()
        ];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        $response = $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    protected function errorResponse(
        ResponseInterface $response,
        string $message,
        int $statusCode = 400,
        ?string $code = null,
        array $details = []
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details
            ],
            'timestamp' => time()
        ];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withStatus($statusCode)
            ->withHeader('Content-Type', 'application/json');
    }

    protected function getRequestData(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (str_contains($contentType, 'application/json')) {
            return $request->getParsedBody() ?? [];
        }
        
        return $request->getParsedBody() ?? [];
    }

    protected function getPaginationParams(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();
        
        return [
            'page' => max(1, (int) ($queryParams['page'] ?? 1)),
            'limit' => min(100, max(1, (int) ($queryParams['limit'] ?? 20))),
            'sort' => $queryParams['sort'] ?? 'id',
            'order' => in_array($queryParams['order'] ?? 'asc', ['asc', 'desc']) 
                ? $queryParams['order'] 
                : 'asc'
        ];
    }
}
```

### Resource API Controller

```php
<?php
// src/Modules/Core/User/Presentation/Controller/Api/V1/UserApiController.php

namespace HdmBoot\Modules\Core\User\Presentation\Controller\Api\V1;

use HdmBoot\SharedKernel\Presentation\Controller\BaseApiController;
use HdmBoot\Modules\Core\User\Application\UseCase\{CreateUser, GetUser, UpdateUser, DeleteUser};
use HdmBoot\Modules\Core\User\Presentation\Request\{CreateUserRequest, UpdateUserRequest};
use HdmBoot\Modules\Core\User\Presentation\Transformer\UserTransformer;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

final class UserApiController extends BaseApiController
{
    public function __construct(
        private readonly CreateUser $createUser,
        private readonly GetUser $getUser,
        private readonly UpdateUser $updateUser,
        private readonly DeleteUser $deleteUser,
        private readonly UserTransformer $userTransformer
    ) {}

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $this->getPaginationParams($request);
        $filters = $request->getQueryParams();
        
        $users = $this->getUser->getAll($params, $filters);
        $transformedUsers = array_map(
            fn($user) => $this->userTransformer->transform($user),
            $users['data']
        );

        return $this->jsonResponse($response, [
            'users' => $transformedUsers,
            'pagination' => $users['pagination']
        ]);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $args['id'];
        
        try {
            $user = $this->getUser->getById($userId);
            $transformedUser = $this->userTransformer->transform($user);
            
            return $this->jsonResponse($response, ['user' => $transformedUser]);
        } catch (UserNotFoundException $e) {
            return $this->errorResponse($response, 'User not found', 404, 'USER_NOT_FOUND');
        }
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $requestData = $this->getRequestData($request);
            $createRequest = CreateUserRequest::fromArray($requestData);
            
            $user = $this->createUser->execute($createRequest->toCommand());
            $transformedUser = $this->userTransformer->transform($user);
            
            return $this->jsonResponse($response, ['user' => $transformedUser], 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($response, 'Validation failed', 422, 'VALIDATION_ERROR', $e->getErrors());
        } catch (DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400, 'DOMAIN_ERROR');
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $args['id'];
        
        try {
            $requestData = $this->getRequestData($request);
            $updateRequest = UpdateUserRequest::fromArray($requestData);
            
            $user = $this->updateUser->execute($userId, $updateRequest->toCommand());
            $transformedUser = $this->userTransformer->transform($user);
            
            return $this->jsonResponse($response, ['user' => $transformedUser]);
        } catch (UserNotFoundException $e) {
            return $this->errorResponse($response, 'User not found', 404, 'USER_NOT_FOUND');
        } catch (ValidationException $e) {
            return $this->errorResponse($response, 'Validation failed', 422, 'VALIDATION_ERROR', $e->getErrors());
        }
    }

    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $args['id'];
        
        try {
            $this->deleteUser->execute($userId);
            return $this->jsonResponse($response, ['message' => 'User deleted successfully']);
        } catch (UserNotFoundException $e) {
            return $this->errorResponse($response, 'User not found', 404, 'USER_NOT_FOUND');
        }
    }
}
```

## 📝 Request Validation

### Request DTO

```php
<?php
// src/Modules/Core/User/Presentation/Request/CreateUserRequest.php

namespace HdmBoot\Modules\Core\User\Presentation\Request;

use HdmBoot\Modules\Core\User\Application\Command\CreateUserCommand;
use HdmBoot\SharedKernel\Validation\ValidationException;

final readonly class CreateUserRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
        public ?string $role = 'user'
    ) {}

    public static function fromArray(array $data): self
    {
        $validator = new RequestValidator();
        $errors = $validator->validate($data, [
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:128|password_strength',
            'name' => 'required|string|min:2|max:100',
            'role' => 'optional|string|in:user,admin,moderator'
        ]);

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return new self(
            email: $data['email'],
            password: $data['password'],
            name: $data['name'],
            role: $data['role'] ?? 'user'
        );
    }

    public function toCommand(): CreateUserCommand
    {
        return new CreateUserCommand(
            email: $this->email,
            password: $this->password,
            name: $this->name,
            role: $this->role
        );
    }
}
```

### Custom Validation Rules

```php
<?php
// src/SharedKernel/Validation/RequestValidator.php

namespace HdmBoot\SharedKernel\Validation;

final class RequestValidator
{
    private array $rules = [];

    public function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $fieldErrors = $this->validateField($data[$field] ?? null, $fieldRules, $field);
            
            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            }
        }

        return $errors;
    }

    private function validateField(mixed $value, array $rules, string $field): array
    {
        $errors = [];
        $isRequired = in_array('required', $rules);
        
        if ($isRequired && ($value === null || $value === '')) {
            $errors[] = "Field {$field} is required";
            return $errors;
        }

        if ($value === null || $value === '') {
            return $errors; // Optional field, skip other validations
        }

        foreach ($rules as $rule) {
            $error = $this->applyRule($value, $rule, $field);
            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function applyRule(mixed $value, string $rule, string $field): ?string
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        return match ($ruleName) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Invalid email format",
            'min' => strlen($value) >= (int)$parameter ? null : "Minimum length is {$parameter}",
            'max' => strlen($value) <= (int)$parameter ? null : "Maximum length is {$parameter}",
            'password_strength' => $this->validatePasswordStrength($value),
            'in' => in_array($value, explode(',', $parameter)) ? null : "Invalid value",
            default => null
        };
    }

    private function validatePasswordStrength(string $password): ?string
    {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            return 'Password must contain at least one lowercase, uppercase letter and number';
        }
        return null;
    }
}
```

## 🔄 Data Transformation

### Transformer Pattern

```php
<?php
// src/Modules/Core/User/Presentation/Transformer/UserTransformer.php

namespace HdmBoot\Modules\Core\User\Presentation\Transformer;

use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class UserTransformer
{
    public function transform(User $user): array
    {
        return [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
            'active' => $user->isActive(),
            'created_at' => $user->getCreatedAt()->format('c'),
            'updated_at' => $user->getUpdatedAt()?->format('c'),
        ];
    }

    public function transformCollection(array $users): array
    {
        return array_map([$this, 'transform'], $users);
    }

    public function transformWithRelations(User $user, array $includes = []): array
    {
        $data = $this->transform($user);

        if (in_array('profile', $includes) && $user->getProfile()) {
            $data['profile'] = $this->transformProfile($user->getProfile());
        }

        if (in_array('permissions', $includes)) {
            $data['permissions'] = $user->getPermissions();
        }

        return $data;
    }

    private function transformProfile($profile): array
    {
        return [
            'avatar' => $profile->getAvatar(),
            'bio' => $profile->getBio(),
            'location' => $profile->getLocation(),
        ];
    }
}
```

## 🛡️ API Security

### Authentication Middleware

```php
<?php
// src/SharedKernel/Presentation/Middleware/ApiAuthMiddleware.php

namespace HdmBoot\SharedKernel\Presentation\Middleware;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final class ApiAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly UserRepository $userRepository
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse();
        }

        $token = substr($authHeader, 7);
        $payload = $this->jwtService->validateToken($token);
        
        if (!$payload) {
            return $this->unauthorizedResponse();
        }

        $user = $this->userRepository->findById($payload['sub']);
        if (!$user || !$user->isActive()) {
            return $this->unauthorizedResponse();
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('user_id', $user->getId()->toString());

        return $handler->handle($request);
    }

    private function unauthorizedResponse(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => [
                'message' => 'Unauthorized',
                'code' => 'UNAUTHORIZED'
            ]
        ]));
        
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

## 📊 API Testing

### Feature Test Example

```php
<?php
// tests/Feature/Api/UserApiTest.php

namespace Tests\Feature\Api;

use Tests\TestCase;
use HdmBoot\Modules\Core\User\Domain\Entity\User;

final class UserApiTest extends TestCase
{
    public function testCreateUserSuccess(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'name' => 'Test User'
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'email',
                            'name',
                            'role',
                            'created_at'
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
    }

    public function testCreateUserValidationError(): void
    {
        $userData = [
            'email' => 'invalid-email',
            'password' => '123', // Too short
            'name' => ''
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'error' => [
                        'message',
                        'code',
                        'details'
                    ]
                ]);
    }

    public function testGetUserWithAuth(): void
    {
        $user = User::create('test@example.com', 'password', 'Test User');
        $this->userRepository->save($user);
        
        $token = $this->jwtService->createToken($user);

        $response = $this->getJson("/api/v1/users/{$user->getId()}", [
            'Authorization' => "Bearer {$token}"
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'id' => $user->getId()->toString(),
                            'email' => 'test@example.com'
                        ]
                    ]
                ]);
    }
}
```

## 📋 API Development Checklist

### Pred implementáciou:
- [ ] Navrhnúť API endpoints a resources
- [ ] Definovať request/response štruktúry
- [ ] Naplánovať validačné pravidlá
- [ ] Identifikovať security requirements

### Počas vývoja:
- [ ] Implementovať controllers s proper error handling
- [ ] Vytvoriť request DTOs s validáciou
- [ ] Implementovať transformers pre responses
- [ ] Pridať authentication/authorization
- [ ] Implementovať rate limiting

### Testovanie:
- [ ] Unit testy pre business logiku
- [ ] Feature testy pre API endpoints
- [ ] Security testy (auth, validation)
- [ ] Performance testy

### Dokumentácia:
- [ ] OpenAPI/Swagger dokumentácia
- [ ] Príklady requestov/responses
- [ ] Error codes dokumentácia
- [ ] Authentication guide

## 🔗 Ďalšie zdroje

- [Authentication API](../api/auth-api.md)
- [Security Best Practices](security-practices.md)
- [Testing Guide](testing-guide.md)
- [API Documentation](../API.md)
