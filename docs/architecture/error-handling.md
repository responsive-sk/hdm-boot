# Standardized Error Handling & RFC 7807 Problem Details

## Overview

This document describes the standardized error handling implementation in the MVA Bootstrap project. The system follows RFC 7807 Problem Details for HTTP APIs to provide consistent, machine-readable error responses across all endpoints.

## RFC 7807 Problem Details

### What is RFC 7807?

RFC 7807 defines a standard format for HTTP API error responses called "Problem Details". It provides a consistent structure that includes:

- **type**: A URI reference that identifies the problem type
- **title**: A short, human-readable summary of the problem type
- **status**: The HTTP status code
- **detail**: A human-readable explanation specific to this occurrence
- **instance**: A URI reference that identifies the specific occurrence

### Example Problem Details Response

```json
{
  "type": "https://httpstatuses.com/400",
  "title": "Validation Error",
  "status": 400,
  "detail": "The request contains invalid data",
  "instance": "/api/users",
  "validation_errors": {
    "email": "Must be a valid email address",
    "password": "Must be at least 8 characters long"
  }
}
```

## Architecture Components

### 1. Problem Details Implementation

The core `ProblemDetails` class provides standardized error formatting:

```php
// src/Shared/ErrorHandling/ProblemDetails/ProblemDetails.php
final readonly class ProblemDetails implements JsonSerializable
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $extensions = []
    ) {}

    // Factory methods for common error types
    public static function validationError(string $detail, array $validationErrors = []): self
    public static function authenticationError(string $detail = 'Authentication failed'): self
    public static function authorizationError(string $detail = 'Access denied'): self
    public static function notFoundError(string $detail = 'Resource not found'): self
    public static function conflictError(string $detail = 'Resource conflict'): self
    public static function rateLimitError(string $detail = 'Rate limit exceeded'): self
    public static function internalServerError(string $detail = 'Internal server error'): self
}
```

### 2. Exception Hierarchy

All application exceptions extend `ProblemDetailsException`:

```php
// Base exception with Problem Details
abstract class ProblemDetailsException extends Exception
{
    protected ProblemDetails $problemDetails;

    public function __construct(ProblemDetails $problemDetails, ?\Throwable $previous = null)
    {
        $this->problemDetails = $problemDetails;
        parent::__construct($problemDetails->detail ?? $problemDetails->title, $problemDetails->status, $previous);
    }

    public function getProblemDetails(): ProblemDetails
    {
        return $this->problemDetails;
    }
}
```

### 3. Specific Exception Types

#### Validation Exception

```php
final class ValidationException extends ProblemDetailsException
{
    public static function withErrors(array $validationErrors, string $detail = 'Invalid data'): self
    {
        $problemDetails = ProblemDetails::validationError($detail, $validationErrors);
        return new self($problemDetails);
    }

    public static function requiredField(string $field): self
    {
        return self::forField($field, "The {$field} field is required");
    }

    public static function invalidFormat(string $field, string $expectedFormat): self
    {
        return self::forField($field, "The {$field} field must be a valid {$expectedFormat}");
    }
}
```

#### Authentication Exception

```php
final class AuthenticationException extends ProblemDetailsException
{
    public static function invalidCredentials(): self
    {
        $problemDetails = ProblemDetails::authenticationError('Invalid email or password');
        return new self($problemDetails);
    }

    public static function expiredToken(): self
    {
        $problemDetails = ProblemDetails::authenticationError('Authentication token has expired');
        return new self($problemDetails);
    }

    public static function accountLocked(): self
    {
        $problemDetails = ProblemDetails::authenticationError('Account is locked due to too many failed attempts');
        return new self($problemDetails);
    }
}
```

#### Authorization Exception

```php
final class AuthorizationException extends ProblemDetailsException
{
    public static function insufficientPermissions(string $requiredPermission): self
    {
        $problemDetails = ProblemDetails::authorizationError("Insufficient permissions. Required: {$requiredPermission}");
        return new self($problemDetails);
    }

    public static function resourceAccessDenied(string $resource, string $action = 'access'): self
    {
        $problemDetails = ProblemDetails::authorizationError("You don't have permission to {$action} {$resource}");
        return new self($problemDetails);
    }
}
```

### 4. Module-Specific Exceptions

Each module defines its own domain-specific exceptions:

```php
// User Module Exceptions
final class UserNotFoundException extends ProblemDetailsException
{
    public static function byId(string $userId): self
    {
        $problemDetails = ProblemDetails::notFoundError("User with ID '{$userId}' not found");
        return new self($problemDetails);
    }

    public static function byEmail(string $email): self
    {
        $problemDetails = ProblemDetails::notFoundError("User with email '{$email}' not found");
        return new self($problemDetails);
    }
}

final class UserAlreadyExistsException extends ProblemDetailsException
{
    public static function withEmail(string $email): self
    {
        $problemDetails = ProblemDetails::conflictError("User with email '{$email}' already exists");
        return new self($problemDetails);
    }
}

// Security Module Exceptions
final class SecurityException extends ProblemDetailsException
{
    public static function rateLimitExceeded(string $detail = 'Rate limit exceeded', ?int $retryAfter = null): self
    {
        $problemDetails = ProblemDetails::rateLimitError($detail, retryAfter: $retryAfter);
        return new self($problemDetails);
    }

    public static function invalidCsrfToken(): self
    {
        $problemDetails = ProblemDetails::authenticationError('Invalid CSRF token');
        return new self($problemDetails);
    }
}
```

## Error Handler Middleware

### Centralized Error Handling

The `ErrorHandlerMiddleware` provides centralized exception handling:

```php
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ProblemDetailsException $e) {
            return $this->handleProblemDetailsException($e, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->handleValidationException($e, $request);
        } catch (\Throwable $e) {
            return $this->handleUnexpectedException($e, $request);
        }
    }

    private function handleProblemDetailsException(ProblemDetailsException $exception, ServerRequestInterface $request): ResponseInterface
    {
        $problemDetails = $exception->getProblemDetails();

        // Log based on error type
        if ($exception->isClientError()) {
            $this->logger->warning('Client error occurred', ['exception' => $exception->toArray()]);
        } else {
            $this->logger->error('Server error occurred', ['exception' => $exception->toArray()]);
        }

        return $this->errorResponseHandler->createProblemDetailsResponse($problemDetails, $request);
    }
}
```

### Error Response Handler

The `ErrorResponseHandler` creates standardized HTTP responses:

```php
final class ErrorResponseHandler
{
    public function createProblemDetailsResponse(ProblemDetails $problemDetails, ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($problemDetails->getStatus());

        // Set Content-Type header for Problem Details
        $response = $response->withHeader('Content-Type', 'application/problem+json');

        // Set instance if not provided
        if ($problemDetails->instance === null) {
            $problemDetails = ProblemDetails::custom(
                // ... set instance to request URI
            );
        }

        // Write JSON response
        $response->getBody()->write($problemDetails->toJson());

        return $response;
    }
}
```

## Error Helper Utilities

### Convenient Validation Methods

The `ErrorHelper` class provides convenient methods for common validations:

```php
final class ErrorHelper
{
    // Validation helpers
    public static function validateRequired(string $value, string $fieldName): void
    {
        if (empty(trim($value))) {
            throw ValidationException::requiredField($fieldName);
        }
    }

    public static function validateEmail(string $email, string $fieldName = 'email'): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidFormat($fieldName, 'email address');
        }
    }

    public static function validateMinLength(string $value, int $minLength, string $fieldName): void
    {
        if (strlen($value) < $minLength) {
            throw ValidationException::invalidFormat($fieldName, "string with at least {$minLength} characters");
        }
    }

    // Exception throwing helpers
    public static function throwInvalidCredentials(): never
    {
        throw AuthenticationException::invalidCredentials();
    }

    public static function throwAccessDenied(): never
    {
        throw AuthorizationException::accessDenied();
    }

    public static function throwUserNotFound(string $userId): never
    {
        throw UserNotFoundException::byId($userId);
    }
}
```

## Usage Examples

### 1. Service Layer Error Handling

```php
final class UserService implements UserServiceInterface
{
    public function createUser(string $email, string $name, string $password): array
    {
        // Validate input using Error Helper
        ErrorHelper::validateRequired($email, 'email');
        ErrorHelper::validateRequired($name, 'name');
        ErrorHelper::validateEmail($email);
        ErrorHelper::validateMinLength($password, 8, 'password');

        // Check business rules
        if ($this->userRepository->emailExists($email)) {
            throw UserAlreadyExistsException::withEmail($email);
        }

        // Create user
        return $this->userRepository->save([
            'email' => $email,
            'name' => $name,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function getUserById(string $id): array
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            throw UserNotFoundException::byId($id);
        }
        
        return $user;
    }
}
```

### 2. Action Layer Error Handling

```php
final class CreateUserAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        try {
            // Delegate to service - exceptions will be caught by middleware
            $user = $this->userService->createUser(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['password'] ?? ''
            );

            // Return success response
            return $this->jsonResponse(['user' => $user], 201);
        } catch (ProblemDetailsException $e) {
            // Let middleware handle the exception
            throw $e;
        }
    }
}
```

### 3. Multiple Validation Errors

```php
final class UpdateUserAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $validationErrors = [];

        // Collect all validation errors
        if (empty($data['name'])) {
            $validationErrors['name'] = 'Name is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors['email'] = 'Invalid email format';
        }

        if (!empty($data['password']) && strlen($data['password']) < 8) {
            $validationErrors['password'] = 'Password must be at least 8 characters long';
        }

        // Throw validation exception with all errors
        if (!empty($validationErrors)) {
            throw ValidationException::withErrors($validationErrors, 'The request contains invalid data');
        }

        // Process valid data
        $user = $this->userService->updateUser($userId, $data);
        return $this->jsonResponse(['user' => $user]);
    }
}
```

## Error Response Examples

### Validation Error Response

```json
{
  "type": "https://httpstatuses.com/400",
  "title": "Validation Error",
  "status": 400,
  "detail": "The request contains invalid data",
  "instance": "/api/users",
  "validation_errors": {
    "email": "Must be a valid email address",
    "password": "Must be at least 8 characters long",
    "name": "Name is required"
  }
}
```

### Authentication Error Response

```json
{
  "type": "https://httpstatuses.com/401",
  "title": "Authentication Error",
  "status": 401,
  "detail": "Invalid email or password",
  "instance": "/api/login"
}
```

### Authorization Error Response

```json
{
  "type": "https://httpstatuses.com/403",
  "title": "Authorization Error",
  "status": 403,
  "detail": "You don't have permission to access users",
  "instance": "/api/admin/users"
}
```

### Not Found Error Response

```json
{
  "type": "https://httpstatuses.com/404",
  "title": "Not Found",
  "status": 404,
  "detail": "User with ID '123' not found",
  "instance": "/api/users/123"
}
```

### Rate Limit Error Response

```json
{
  "type": "https://httpstatuses.com/429",
  "title": "Too Many Requests",
  "status": 429,
  "detail": "Rate limit exceeded. Try again later.",
  "instance": "/api/login",
  "retry_after": 60
}
```

### Internal Server Error Response

```json
{
  "type": "https://httpstatuses.com/500",
  "title": "Internal Server Error",
  "status": 500,
  "detail": "An internal server error occurred",
  "instance": "/api/users",
  "trace_id": "error_64f8a1b2c3d4e"
}
```

## Benefits

### 1. Consistency

**Before Standardized Error Handling:**
```php
// Different error formats across actions
class LoginAction
{
    public function handle(): ResponseInterface
    {
        if (!$user) {
            return $this->jsonResponse(['error' => 'Invalid credentials'], 401);
        }
    }
}

class CreateUserAction
{
    public function handle(): ResponseInterface
    {
        if ($validationErrors) {
            return $this->jsonResponse(['message' => 'Validation failed', 'errors' => $validationErrors], 400);
        }
    }
}
```

**After Standardized Error Handling:**
```php
// Consistent RFC 7807 format everywhere
class LoginAction
{
    public function handle(): ResponseInterface
    {
        if (!$user) {
            throw AuthenticationException::invalidCredentials();
        }
    }
}

class CreateUserAction
{
    public function handle(): ResponseInterface
    {
        if ($validationErrors) {
            throw ValidationException::withErrors($validationErrors);
        }
    }
}
```

### 2. Machine-Readable Errors

Clients can programmatically handle errors based on the `type` field:

```javascript
// Frontend error handling
fetch('/api/users', { method: 'POST', body: userData })
  .then(response => {
    if (!response.ok) {
      return response.json().then(problem => {
        switch (problem.type) {
          case 'https://httpstatuses.com/400':
            handleValidationErrors(problem.validation_errors);
            break;
          case 'https://httpstatuses.com/401':
            redirectToLogin();
            break;
          case 'https://httpstatuses.com/403':
            showAccessDeniedMessage();
            break;
          default:
            showGenericError(problem.detail);
        }
      });
    }
    return response.json();
  });
```

### 3. Better Debugging

Structured error information improves debugging:

```php
// Detailed error logging
$this->logger->error('User creation failed', [
    'exception_class' => get_class($exception),
    'problem_details' => $exception->getProblemDetails()->toArray(),
    'request_data' => $sanitizedRequestData,
    'trace_id' => $traceId,
]);
```

### 4. API Documentation

RFC 7807 format makes API documentation clearer:

```yaml
# OpenAPI specification
responses:
  400:
    description: Validation Error
    content:
      application/problem+json:
        schema:
          $ref: '#/components/schemas/ProblemDetails'
        example:
          type: "https://httpstatuses.com/400"
          title: "Validation Error"
          status: 400
          detail: "The request contains invalid data"
          validation_errors:
            email: "Must be a valid email address"
```

## Testing Error Handling

### 1. Unit Testing Exceptions

```php
class UserServiceTest extends TestCase
{
    public function testCreateUserThrowsExceptionForDuplicateEmail(): void
    {
        $this->userRepository->method('emailExists')->willReturn(true);

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage("User with email 'test@example.com' already exists");

        $this->userService->createUser('test@example.com', 'Test User', 'password123');
    }

    public function testGetUserByIdThrowsExceptionWhenNotFound(): void
    {
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with ID '123' not found");

        $this->userService->getUserById('123');
    }
}
```

### 2. Integration Testing Error Responses

```php
class ErrorHandlingIntegrationTest extends TestCase
{
    public function testValidationErrorReturnsRfc7807Response(): void
    {
        $request = $this->createRequest('POST', '/api/users')
            ->withParsedBody(['email' => 'invalid-email']);

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('https://httpstatuses.com/400', $body['type']);
        $this->assertEquals('Validation Error', $body['title']);
        $this->assertEquals(400, $body['status']);
        $this->assertArrayHasKey('validation_errors', $body);
    }

    public function testAuthenticationErrorReturnsRfc7807Response(): void
    {
        $request = $this->createRequest('POST', '/api/login')
            ->withParsedBody(['email' => 'test@example.com', 'password' => 'wrong']);

        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals('https://httpstatuses.com/401', $body['type']);
        $this->assertEquals('Authentication Error', $body['title']);
    }
}
```

### 3. Testing Error Helper

```php
class ErrorHelperTest extends TestCase
{
    public function testValidateRequiredThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The email field is required');

        ErrorHelper::validateRequired('', 'email');
    }

    public function testValidateEmailThrowsExceptionForInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The email field must be a valid email address');

        ErrorHelper::validateEmail('invalid-email');
    }

    public function testValidateMinLengthThrowsExceptionForShortValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The password field must be a valid string with at least 8 characters');

        ErrorHelper::validateMinLength('short', 8, 'password');
    }
}
```

## Best Practices

### 1. Exception Design

- **Use specific exceptions** for different error types
- **Include relevant context** in error messages
- **Follow naming conventions** (e.g., `UserNotFoundException`, `ValidationException`)
- **Extend ProblemDetailsException** for all application exceptions

### 2. Error Messages

- **Be descriptive but not verbose** - provide enough information to understand the problem
- **Don't expose sensitive information** - avoid revealing internal system details
- **Use consistent language** - maintain the same tone and style across all errors
- **Include actionable guidance** when possible

### 3. HTTP Status Codes

- **400 Bad Request** - Client error, invalid request data
- **401 Unauthorized** - Authentication required or failed
- **403 Forbidden** - Authenticated but not authorized
- **404 Not Found** - Resource doesn't exist
- **409 Conflict** - Resource already exists or conflict
- **422 Unprocessable Entity** - Valid syntax but semantic errors
- **429 Too Many Requests** - Rate limiting
- **500 Internal Server Error** - Unexpected server errors

### 4. Logging Strategy

```php
// Log client errors as warnings
if ($exception->isClientError()) {
    $this->logger->warning('Client error occurred', [
        'exception' => $exception->toArray(),
        'request_uri' => $request->getUri(),
    ]);
}

// Log server errors as errors with full context
if ($exception->isServerError()) {
    $this->logger->error('Server error occurred', [
        'exception' => $exception->toArray(),
        'request_uri' => $request->getUri(),
        'trace' => $exception->getTraceAsString(),
        'request_data' => $this->sanitizeRequestData($request),
    ]);
}
```

## Migration Guide

### Step 1: Install Error Handling Components

1. Create the error handling directory structure
2. Implement `ProblemDetails` class
3. Create base `ProblemDetailsException` class
4. Add `ErrorHandlerMiddleware` and `ErrorResponseHandler`

### Step 2: Create Standard Exceptions

1. Implement common exceptions (`ValidationException`, `AuthenticationException`, etc.)
2. Create module-specific exceptions
3. Add `ErrorHelper` utility class

### Step 3: Update Services

1. Replace `InvalidArgumentException` with specific exceptions
2. Use `ErrorHelper` for validation
3. Throw domain-specific exceptions

### Step 4: Configure Middleware

1. Add `ErrorHandlerMiddleware` to middleware stack
2. Configure error response handler in container
3. Set up proper logging

### Step 5: Update Tests

1. Update unit tests to expect specific exceptions
2. Add integration tests for error responses
3. Test RFC 7807 compliance

## Troubleshooting

### Common Issues

1. **Wrong Content-Type Header**
   - **Problem**: Responses don't have `application/problem+json` header
   - **Solution**: Ensure `ErrorResponseHandler` sets the correct header

2. **Missing Instance Field**
   - **Problem**: Problem Details responses don't include `instance`
   - **Solution**: `ErrorResponseHandler` automatically sets instance from request URI

3. **Inconsistent Error Format**
   - **Problem**: Some errors don't follow RFC 7807 format
   - **Solution**: Ensure all exceptions extend `ProblemDetailsException`

4. **Sensitive Information Exposure**
   - **Problem**: Error messages reveal internal details
   - **Solution**: Use generic messages for server errors, detailed messages only for client errors

### Debugging Tools

```php
// Check if exception follows RFC 7807
if ($exception instanceof ProblemDetailsException) {
    $problemDetails = $exception->getProblemDetails();
    error_log('RFC 7807 compliant error: ' . $problemDetails->toJson());
}

// Validate Problem Details structure
$problemDetails = ProblemDetails::validationError('Test error');
assert($problemDetails->type !== null);
assert($problemDetails->title !== null);
assert($problemDetails->status >= 400);
```

## Conclusion

Standardized error handling with RFC 7807 Problem Details provides:

- **Consistent API responses** across all endpoints
- **Machine-readable error format** for better client integration
- **Improved debugging** with structured error information
- **Better user experience** with clear, actionable error messages
- **Maintainable codebase** with centralized error handling

This implementation ensures that all errors are handled consistently, making the API more reliable and easier to integrate with frontend applications and external services.
```
