# 📊 Monitoring & Logging Infrastructure

## 🎯 **Overview**

This document describes the comprehensive monitoring and logging infrastructure implemented in the MVA Bootstrap project. The system provides centralized logging, health checks, performance monitoring, and observability features essential for production applications.

### ✅ **Recent Updates (2025-06-20)**
- **LoggerFactory refactored** - Unified formatting and specialized loggers
- **Log rotation implemented** - Automated cleanup and compression
- **Duplicate logging eliminated** - Clean, consistent log output
- **CLI tools added** - Manual log management capabilities
- **Cron automation** - Scheduled maintenance scripts

## 🏗️ **Architecture Components**

### 1. **LoggerFactory - Centralized Logger Management**

The logging system uses a refactored LoggerFactory with unified formatting and specialized loggers for different purposes.

#### **LoggerFactory Features**
- **Unified formatting** - Consistent log format across all channels
- **Specialized loggers** - Security, performance, audit, and general logging
- **Environment-aware** - Different configurations for dev/staging/production
- **Monolog compatibility** - Support for both array and LogRecord formats
- **Automatic rotation** - Built-in file rotation with configurable retention

#### **Available Logger Types**
```php
// Get specialized loggers from DI container
$securityLogger = $container->get('logger.security');    // Security events
$auditLogger = $container->get('logger.audit');          // Audit trail
$performanceLogger = $container->get('logger.performance'); // Performance metrics
$profileLogger = $container->get('logger.profile');      // Profile actions
$generalLogger = $container->get(LoggerInterface::class); // General application
```

### 2. **Centralized Logging System**

The logging system uses Monolog with multiple specialized loggers and handlers for different environments and purposes.

#### **Unified Log Format**

All loggers now use consistent formatting for better readability and monitoring:

```
🔍 [2025-06-20 12:34:56] app.INFO: User profile accessed | Context: {"user_id":"123"} | Extra: {"memory":"2MB"}
🔒 [2025-06-20 12:34:56] security.WARNING: Login failed | Context: {"email":"user@example.com"} | Extra: {"ip":"192.168.1.1"}
⚡ [2025-06-20 12:34:56] performance.INFO: Slow query detected | Context: {"duration":2.5} | Extra: {"query":"SELECT..."}
📋 [2025-06-20 12:34:56] audit.INFO: User created | Context: {"user_id":"456"} | Extra: {"created_by":"admin"}
```

#### **Logger Configuration**

```php
// src/Modules/Core/Logging/config.php
return [
    'services' => [
        // Logger Factory
        LoggerFactory::class => function (Container $c): LoggerFactory {
            return new LoggerFactory(
                $c->get(Paths::class),
                $_ENV['APP_ENV'] ?? 'production',
                (bool) ($_ENV['APP_DEBUG'] ?? false)
            );
        },

        // Default application logger
        LoggerInterface::class => function (Container $c): LoggerInterface {
            return $c->get(LoggerFactory::class)->createLogger('app');
        },

        // Specialized loggers with unique names
        'logger.security' => function (Container $c): LoggerInterface {
            return $c->get(LoggerFactory::class)->createSecurityLogger();
        },

        'logger.audit' => function (Container $c): LoggerInterface {
            return $c->get(LoggerFactory::class)->createAuditLogger();
        },

        'logger.performance' => function (Container $c): LoggerInterface {
            return $c->get(LoggerFactory::class)->createPerformanceLogger();
        },

        // Action-specific loggers
        'logger.profile' => function (Container $c): LoggerInterface {
            return $c->get(LoggerFactory::class)->createLogger('profile');
        },
    ],
];
```

#### Environment-Specific Logging

**Development Environment:**
- Console output with colored formatting
- Debug-level logging to files
- Detailed error information

```php
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new RotatingFileHandler('var/logs/app/debug.log', 0, Logger::DEBUG));
```

**Staging Environment:**
- Application logs with 30-day retention
- Error logs with JSON formatting
- Performance monitoring

```php
$appHandler = new RotatingFileHandler('var/logs/app/application.log', 30, Logger::INFO);
$errorHandler = new RotatingFileHandler('var/logs/errors/errors.log', 30, Logger::ERROR);
```

**Production Environment:**
- Application logs with 90-day retention
- Error logs with 90-day retention
- Critical logs with 365-day retention
- Minimal processors for performance

```php
$appHandler = new RotatingFileHandler('var/logs/app/application.log', 90, Logger::INFO);
$errorHandler = new RotatingFileHandler('var/logs/errors/errors.log', 90, Logger::ERROR);
$criticalHandler = new RotatingFileHandler('var/logs/errors/critical.log', 365, Logger::CRITICAL);
```

#### Specialized Loggers

**Security Logger:**
```php
// Security events and alerts
$securityLogger->info('User login successful', [
    'user_id' => $userId,
    'email' => $email,
    'ip_address' => $clientIp,
    'user_agent' => $userAgent,
]);

$securityLogger->warning('Failed login attempt', [
    'email' => $email,
    'ip_address' => $clientIp,
    'attempt_count' => $attemptCount,
]);

$securityLogger->error('Suspicious activity detected', [
    'user_id' => $userId,
    'activity_type' => 'multiple_failed_logins',
    'ip_address' => $clientIp,
]);
```

**Performance Logger:**
```php
// Performance metrics and slow operations
$performanceLogger->info('HTTP request completed', [
    'method' => $method,
    'path' => $path,
    'status_code' => $statusCode,
    'duration' => $duration,
    'memory_usage' => memory_get_usage(true),
]);

$performanceLogger->warning('Slow database query detected', [
    'query' => substr($query, 0, 200),
    'duration' => $duration,
    'parameters' => $parameters,
]);
```

**Audit Logger:**
```php
// Permanent audit trail (never rotates)
$auditLogger->info('User created', [
    'user_id' => $newUserId,
    'created_by' => $adminUserId,
    'user_data' => $sanitizedUserData,
]);

$auditLogger->info('User permissions changed', [
    'user_id' => $userId,
    'changed_by' => $adminUserId,
    'old_permissions' => $oldPermissions,
    'new_permissions' => $newPermissions,
]);
```

### 2. Health Check System

The health check system provides endpoints for monitoring application health and dependencies.

#### Health Check Interface

```php
interface HealthCheckInterface
{
    public function getName(): string;
    public function check(): HealthCheckResult;
    public function getTimeout(): int;
    public function isCritical(): bool;
}
```

#### Health Check Results

```php
final readonly class HealthCheckResult
{
    public function __construct(
        public string $name,
        public HealthStatus $status,
        public ?string $message = null,
        public array $data = [],
        public ?float $duration = null,
        public ?\DateTimeImmutable $timestamp = null
    ) {}

    public static function healthy(string $name, ?string $message = null, array $data = []): self
    public static function unhealthy(string $name, string $message, array $data = []): self
    public static function degraded(string $name, string $message, array $data = []): self
}
```

#### Built-in Health Checks

**Database Health Check:**
```php
final class DatabaseHealthCheck implements HealthCheckInterface
{
    public function check(): HealthCheckResult
    {
        try {
            // Test basic connectivity
            $stmt = $this->pdo->query('SELECT 1 as test');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Test write capability
            $writeTest = $this->testWriteCapability();

            // Get database info
            $dbInfo = $this->getDatabaseInfo();

            return HealthCheckResult::healthy(
                $this->getName(),
                'Database is accessible and responsive',
                array_merge($dbInfo, [
                    'response_time' => $duration,
                    'write_test' => $writeTest,
                ])
            );
        } catch (\Exception $e) {
            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Database connection failed: ' . $e->getMessage()
            );
        }
    }
}
```

**Filesystem Health Check:**
```php
final class FilesystemHealthCheck implements HealthCheckInterface
{
    public function check(): HealthCheckResult
    {
        $checks = [
            'log_directory' => $this->checkLogDirectory(),
            'temp_directory' => $this->checkTempDirectory(),
            'cache_directory' => $this->checkCacheDirectory(),
            'disk_space' => $this->checkDiskSpace(),
            'write_permissions' => $this->checkWritePermissions(),
        ];

        // Check for failures
        $failures = array_filter($checks, fn($check) => !$check['success']);

        if (!empty($failures)) {
            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Filesystem checks failed',
                $checks
            );
        }

        // Check for warnings (disk space)
        $diskUsage = $checks['disk_space']['usage_percentage'];
        if ($diskUsage >= 80) {
            return HealthCheckResult::degraded(
                $this->getName(),
                "Disk usage is high: {$diskUsage}%",
                $checks
            );
        }

        return HealthCheckResult::healthy(
            $this->getName(),
            'Filesystem is accessible and has sufficient space',
            $checks
        );
    }
}
```

#### Health Check Manager

```php
final class HealthCheckManager
{
    public function registerHealthCheck(HealthCheckInterface $healthCheck): void
    {
        $this->healthChecks[$healthCheck->getName()] = $healthCheck;
    }

    public function checkHealth(): HealthCheckReport
    {
        $results = [];

        foreach ($this->healthChecks as $healthCheck) {
            $result = $this->executeHealthCheck($healthCheck);
            $results[$result->name] = $result;
        }

        return new HealthCheckReport($results, $totalDuration);
    }

    public function checkSpecific(string $name): ?HealthCheckResult
    {
        if (!isset($this->healthChecks[$name])) {
            return null;
        }

        return $this->executeHealthCheck($this->healthChecks[$name]);
    }
}
```

### 3. Health Check Endpoints

The system provides multiple endpoints for different monitoring tools and load balancers.

#### Available Endpoints

```php
// config/routes/monitoring.php
$app->get('/_status', HealthCheckAction::class);     // Main health check endpoint
$app->get('/health', HealthCheckAction::class);      // Alternative endpoint
$app->get('/healthz', HealthCheckAction::class);     // Kubernetes style
$app->get('/ping', HealthCheckAction::class);        // Simple ping endpoint
$app->get('/_status/{check}', HealthCheckAction::class); // Specific health check
```

#### Health Check Responses

**Healthy System Response:**
```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T23:27:07.123Z",
  "duration": 0.0004949569702148438,
  "summary": {
    "overall_status": "healthy",
    "total_checks": 3,
    "healthy_checks": 3,
    "unhealthy_checks": 0,
    "degraded_checks": 0
  },
  "checks": {
    "database": {
      "name": "database",
      "status": "healthy",
      "message": "Database is accessible and responsive",
      "data": {
        "driver": "sqlite",
        "server_version": "3.40.1",
        "response_time": 0.000079,
        "write_test": true
      },
      "duration": 0.000079,
      "timestamp": "2024-01-15T23:27:07.123Z"
    },
    "filesystem": {
      "name": "filesystem",
      "status": "healthy",
      "message": "Filesystem is accessible and has sufficient space",
      "data": {
        "log_directory": {
          "success": true,
          "path": "/app/var/logs",
          "permissions": "0755"
        },
        "disk_space": {
          "usage_percentage": 45.2,
          "free_gb": 12.5,
          "total_gb": 23.0
        }
      },
      "duration": 0.000087,
      "timestamp": "2024-01-15T23:27:07.123Z"
    },
    "application": {
      "name": "application",
      "status": "healthy",
      "message": "Application is running normally",
      "data": {
        "php_version": "8.2.0",
        "memory_limit": "256M",
        "max_execution_time": "30",
        "loaded_extensions": 45
      },
      "duration": 0.000032,
      "timestamp": "2024-01-15T23:27:07.123Z"
    }
  }
}
```

**Unhealthy System Response (HTTP 503):**
```json
{
  "status": "unhealthy",
  "timestamp": "2024-01-15T23:27:07.123Z",
  "duration": 0.0012345,
  "summary": {
    "overall_status": "unhealthy",
    "total_checks": 3,
    "healthy_checks": 2,
    "unhealthy_checks": 1,
    "degraded_checks": 0
  },
  "checks": {
    "database": {
      "name": "database",
      "status": "unhealthy",
      "message": "Database connection failed: Connection refused",
      "data": {
        "error_type": "PDOException"
      },
      "duration": 0.001234,
      "timestamp": "2024-01-15T23:27:07.123Z"
    }
  }
}
```

**Specific Health Check Response:**
```bash
# Check specific component
curl http://localhost/_status/database

{
  "name": "database",
  "status": "healthy",
  "message": "Database is accessible and responsive",
  "data": {
    "driver": "sqlite",
    "server_version": "3.40.1",
    "response_time": 0.000079
  },
  "duration": 0.000079,
  "timestamp": "2024-01-15T23:27:07.123Z"
}
```

### 4. Performance Monitoring

The performance monitoring system tracks application metrics, response times, and resource usage.

#### Performance Monitor

```php
final class PerformanceMonitor
{
    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stopTimer(string $name): float
    {
        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);
        $this->recordMetric("timer.{$name}", $duration);
        return $duration;
    }

    public function measure(string $name, callable $callable): mixed
    {
        $this->startTimer($name);
        try {
            return $callable();
        } finally {
            $duration = $this->stopTimer($name);
            $this->performanceLogger->info('Performance measurement', [
                'metric' => $name,
                'duration' => $duration,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
        }
    }

    public function incrementCounter(string $name, int $value = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
        $this->recordMetric("counter.{$name}", $this->counters[$name]);
    }

    public function recordMemoryUsage(string $context = 'general'): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $this->recordMetric("memory.usage.{$context}", $memoryUsage);
        $this->recordMetric("memory.peak.{$context}", $memoryPeak);
    }
}
```

#### Performance Metrics

**HTTP Request Monitoring:**
```php
$performanceMonitor->recordHttpRequest(
    method: 'POST',
    path: '/api/users',
    statusCode: 201,
    duration: 0.245
);

// Automatic slow request detection
if ($duration > 2.0) {
    $performanceLogger->warning('Slow HTTP request detected', [
        'method' => $method,
        'path' => $path,
        'status_code' => $statusCode,
        'duration' => $duration,
    ]);
}
```

**Database Query Monitoring:**
```php
$performanceMonitor->recordDatabaseQuery(
    query: 'SELECT * FROM users WHERE email = ?',
    duration: 0.123,
    success: true
);

// Automatic slow query detection
if ($duration > 1.0) {
    $performanceLogger->warning('Slow database query detected', [
        'query' => substr($query, 0, 200),
        'duration' => $duration,
        'success' => $success,
    ]);
}
```

**Memory Usage Tracking:**
```php
// Track memory usage at key points
$performanceMonitor->recordMemoryUsage('after_user_creation');
$performanceMonitor->recordMemoryUsage('after_email_sending');
$performanceMonitor->recordMemoryUsage('request_end');
```

**Custom Metrics:**
```php
// Business metrics
$performanceMonitor->incrementCounter('users.registered');
$performanceMonitor->incrementCounter('emails.sent');
$performanceMonitor->incrementCounter('api.requests.authenticated');

// Technical metrics
$performanceMonitor->incrementCounter('database.queries.total');
$performanceMonitor->incrementCounter('cache.hits');
$performanceMonitor->incrementCounter('cache.misses');
```

## Directory Structure

### Log Directory Organization

```
var/logs/
├── app/
│   ├── application.log          # General application logs
│   ├── debug.log               # Debug logs (development only)
│   └── audit.log               # Permanent audit trail
├── security/
│   ├── security.log            # Security events
│   └── alerts.log              # Security alerts (WARNING+)
├── performance/
│   └── metrics.log             # Performance metrics
└── errors/
    ├── errors.log              # Error logs (ERROR+)
    └── critical.log            # Critical errors (CRITICAL+)
```

### Source Code Organization

```
src/Shared/
├── Logging/
│   ├── Handlers/               # Custom log handlers
│   ├── Formatters/             # Custom log formatters
│   └── Processors/             # Custom log processors
└── Monitoring/
    ├── HealthChecks/
    │   ├── HealthCheckInterface.php
    │   ├── HealthCheckResult.php
    │   ├── HealthStatus.php
    │   ├── HealthCheckManager.php
    │   ├── HealthCheckReport.php
    │   ├── DatabaseHealthCheck.php
    │   └── FilesystemHealthCheck.php
    ├── Metrics/
    │   └── PerformanceMonitor.php
    ├── Actions/
    │   └── HealthCheckAction.php
    └── Bootstrap/
        └── MonitoringBootstrap.php
```

## Usage Examples

### 1. Service Layer Monitoring

```php
final class UserService
{
    public function createUser(string $email, string $name, string $password): array
    {
        return $this->performanceMonitor->measure('user_creation', function() use ($email, $name, $password) {
            // Validate input
            $this->validateUserInput($email, $name, $password);

            // Check if user exists
            if ($this->userRepository->emailExists($email)) {
                $this->securityLogger->warning('Attempt to create duplicate user', [
                    'email' => $email,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                throw UserAlreadyExistsException::withEmail($email);
            }

            // Create user
            $userData = [
                'email' => $email,
                'name' => $name,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $user = $this->userRepository->save($userData);

            // Log successful creation
            $this->auditLogger->info('User created', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'created_by' => 'system',
            ]);

            // Update metrics
            $this->performanceMonitor->incrementCounter('users.created');

            return $user;
        });
    }
}
```

### 2. Security Event Logging

```php
final class AuthenticationService
{
    public function authenticateForWeb(string $email, string $password, string $clientIp): ?array
    {
        $startTime = microtime(true);

        try {
            $user = $this->userService->authenticate($email, $password);

            if ($user) {
                // Log successful authentication
                $this->securityLogger->info('User login successful', [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'ip_address' => $clientIp,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'duration' => microtime(true) - $startTime,
                ]);

                // Update metrics
                $this->performanceMonitor->incrementCounter('auth.login.successful');

                return $user;
            } else {
                // Log failed authentication
                $this->securityLogger->warning('Failed login attempt', [
                    'email' => $email,
                    'ip_address' => $clientIp,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'duration' => microtime(true) - $startTime,
                ]);

                // Update metrics
                $this->performanceMonitor->incrementCounter('auth.login.failed');

                return null;
            }
        } catch (\Exception $e) {
            // Log authentication error
            $this->securityLogger->error('Authentication error', [
                'email' => $email,
                'ip_address' => $clientIp,
                'error' => $e->getMessage(),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $e;
        }
    }
}
```

### 3. Performance Monitoring in Actions

```php
final class CreateUserAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        try {
            $data = (array) $request->getParsedBody();

            // Create user with performance monitoring
            $user = $this->userService->createUser(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['password'] ?? ''
            );

            $statusCode = 201;
            $responseData = ['user' => $user];

            return $this->jsonResponse($responseData, $statusCode);
        } catch (\Exception $e) {
            $statusCode = $e instanceof ProblemDetailsException ? $e->getStatusCode() : 500;
            throw $e;
        } finally {
            // Record HTTP request metrics
            $duration = microtime(true) - $startTime;
            $this->performanceMonitor->recordHttpRequest($method, $path, $statusCode, $duration);
        }
    }
}
```

### 4. Custom Health Checks

```php
final class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'redis';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            // Test basic connectivity
            $pong = $this->redis->ping();

            if ($pong !== '+PONG') {
                return HealthCheckResult::unhealthy(
                    $this->getName(),
                    'Redis ping failed',
                    ['response' => $pong],
                    microtime(true) - $startTime
                );
            }

            // Test read/write operations
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';

            $this->redis->set($testKey, $testValue, 10); // 10 second TTL
            $retrievedValue = $this->redis->get($testKey);
            $this->redis->del($testKey);

            if ($retrievedValue !== $testValue) {
                return HealthCheckResult::degraded(
                    $this->getName(),
                    'Redis read/write test failed',
                    ['expected' => $testValue, 'actual' => $retrievedValue],
                    microtime(true) - $startTime
                );
            }

            // Get Redis info
            $info = $this->redis->info();

            return HealthCheckResult::healthy(
                $this->getName(),
                'Redis is accessible and responsive',
                [
                    'version' => $info['redis_version'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 'unknown',
                    'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                    'response_time' => microtime(true) - $startTime,
                ],
                microtime(true) - $startTime
            );
        } catch (\Exception $e) {
            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Redis connection failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    public function getTimeout(): int
    {
        return 3; // 3 seconds timeout
    }

    public function isCritical(): bool
    {
        return false; // Redis is not critical for basic functionality
    }
}
```

## Integration with External Services

### 1. Sentry Integration

```php
// Add Sentry handler to production logger
use Sentry\Monolog\Handler as SentryHandler;

$sentryHandler = new SentryHandler();
$sentryHandler->setLevel(Logger::ERROR);
$logger->pushHandler($sentryHandler);
```

### 2. Elasticsearch Integration

```php
// Add Elasticsearch handler for log aggregation
use Monolog\Handler\ElasticSearchHandler;

$elasticHandler = new ElasticSearchHandler(
    $elasticsearchClient,
    ['index' => 'application-logs', 'type' => '_doc']
);
$logger->pushHandler($elasticHandler);
```

### 3. Datadog Integration

```php
// Add Datadog handler for metrics
use Monolog\Handler\SocketHandler;

$datadogHandler = new SocketHandler('udp://localhost:8125');
$datadogHandler->setFormatter(new DatadogFormatter());
$logger->pushHandler($datadogHandler);
```

## Load Balancer Configuration

### 1. HAProxy Configuration

```haproxy
# HAProxy health check configuration
backend web_servers
    balance roundrobin
    option httpchk GET /_status
    http-check expect status 200
    server web1 192.168.1.10:80 check
    server web2 192.168.1.11:80 check
```

### 2. Nginx Configuration

```nginx
# Nginx upstream health checks
upstream backend {
    server 192.168.1.10:80;
    server 192.168.1.11:80;
}

# Health check location
location /_status {
    access_log off;
    proxy_pass http://backend;
    proxy_set_header Host $host;
}
```

### 3. Kubernetes Configuration

```yaml
# Kubernetes liveness and readiness probes
apiVersion: apps/v1
kind: Deployment
metadata:
  name: mva-bootstrap
spec:
  template:
    spec:
      containers:
      - name: app
        image: mva-bootstrap:latest
        livenessProbe:
          httpGet:
            path: /healthz
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /_status
            port: 80
          initialDelaySeconds: 5
          periodSeconds: 5
```

## Best Practices

### 1. Logging Best Practices

**Structured Logging:**
```php
// ✅ Good - Structured logging with context
$logger->info('User registration completed', [
    'user_id' => $userId,
    'email' => $email,
    'registration_source' => 'web',
    'duration' => $duration,
    'ip_address' => $clientIp,
]);

// ❌ Bad - Unstructured logging
$logger->info("User {$email} registered successfully in {$duration}ms from {$clientIp}");
```

**Log Levels:**
- **DEBUG**: Detailed information for debugging
- **INFO**: General information about application flow
- **WARNING**: Something unexpected happened but application continues
- **ERROR**: Error occurred but application continues
- **CRITICAL**: Serious error occurred, application may not continue

**Sensitive Information:**
```php
// ✅ Good - Sanitize sensitive data
$logger->info('User login attempt', [
    'email' => $email,
    'ip_address' => $clientIp,
    'success' => $success,
    // Don't log passwords, tokens, etc.
]);

// ❌ Bad - Logging sensitive information
$logger->info('User login attempt', [
    'email' => $email,
    'password' => $password, // Never log passwords!
    'token' => $authToken,   // Never log tokens!
]);
```

### 2. Health Check Best Practices

**Health Check Design:**
- Keep health checks lightweight and fast
- Test actual dependencies, not just connectivity
- Use appropriate timeouts
- Distinguish between critical and non-critical checks

**Health Check Granularity:**
```php
// ✅ Good - Specific health checks
$healthCheckManager->registerHealthCheck(new DatabaseHealthCheck());
$healthCheckManager->registerHealthCheck(new RedisHealthCheck());
$healthCheckManager->registerHealthCheck(new FilesystemHealthCheck());

// ❌ Bad - Single monolithic health check
$healthCheckManager->registerHealthCheck(new EverythingHealthCheck());
```

**Health Check Caching:**
```php
// Cache health check results for a short period to avoid overwhelming dependencies
final class CachedHealthCheck implements HealthCheckInterface
{
    private ?HealthCheckResult $cachedResult = null;
    private ?float $cacheTime = null;
    private const CACHE_TTL = 30; // 30 seconds

    public function check(): HealthCheckResult
    {
        $now = microtime(true);

        if ($this->cachedResult && $this->cacheTime && ($now - $this->cacheTime) < self::CACHE_TTL) {
            return $this->cachedResult;
        }

        $this->cachedResult = $this->actualHealthCheck->check();
        $this->cacheTime = $now;

        return $this->cachedResult;
    }
}
```

### 3. Performance Monitoring Best Practices

**Metric Naming:**
```php
// ✅ Good - Consistent metric naming
$monitor->incrementCounter('http.requests.total');
$monitor->incrementCounter('http.requests.method.post');
$monitor->incrementCounter('http.requests.status.200');
$monitor->recordMetric('http.request.duration', $duration);

// ❌ Bad - Inconsistent naming
$monitor->incrementCounter('requests');
$monitor->incrementCounter('POST_requests');
$monitor->incrementCounter('status_200');
$monitor->recordMetric('request_time', $duration);
```

**Performance Thresholds:**
```php
// Define performance thresholds
private const SLOW_REQUEST_THRESHOLD = 2.0;    // 2 seconds
private const SLOW_QUERY_THRESHOLD = 1.0;      // 1 second
private const HIGH_MEMORY_THRESHOLD = 128 * 1024 * 1024; // 128MB

// Monitor against thresholds
if ($duration > self::SLOW_REQUEST_THRESHOLD) {
    $this->performanceLogger->warning('Slow request detected', [
        'duration' => $duration,
        'threshold' => self::SLOW_REQUEST_THRESHOLD,
    ]);
}
```

## Troubleshooting

### 1. Common Issues

**Log Files Not Created:**
```bash
# Check directory permissions
ls -la var/logs/
chmod 755 var/logs/
chmod 755 var/logs/app/
chmod 755 var/logs/errors/

# Check disk space
df -h
```

**Health Checks Failing:**
```bash
# Test individual health checks
curl http://localhost/_status/database
curl http://localhost/_status/filesystem

# Check logs for health check errors
tail -f var/logs/app/application.log | grep health
```

**Performance Issues:**
```bash
# Check performance logs
tail -f var/logs/performance/metrics.log | grep slow

# Monitor memory usage
tail -f var/logs/performance/metrics.log | grep memory
```

### 2. Debugging Tools

**Log Analysis:**
```bash
# Search for errors in logs
grep -r "ERROR\|CRITICAL" var/logs/

# Monitor logs in real-time
tail -f var/logs/app/application.log

# Parse JSON logs
tail -f var/logs/app/application.log | jq .

# Filter by log level
tail -f var/logs/app/application.log | jq 'select(.level == "ERROR")'
```

**Health Check Debugging:**
```php
// Debug health check execution
$healthCheckManager = $container->get(HealthCheckManager::class);
$report = $healthCheckManager->checkHealth();

foreach ($report->results as $name => $result) {
    echo "Health Check: {$name}\n";
    echo "Status: {$result->status->value}\n";
    echo "Message: {$result->message}\n";
    echo "Duration: {$result->duration}s\n";
    echo "Data: " . json_encode($result->data, JSON_PRETTY_PRINT) . "\n\n";
}
```

**Performance Debugging:**
```php
// Debug performance metrics
$performanceMonitor = $container->get(PerformanceMonitor::class);
$metrics = $performanceMonitor->getMetrics();

echo "Performance Metrics:\n";
echo json_encode($metrics, JSON_PRETTY_PRINT);
```

### 3. Monitoring Alerts

**Log-based Alerts:**
```bash
# Alert on critical errors
tail -f var/logs/errors/critical.log | while read line; do
    echo "CRITICAL ERROR: $line" | mail -s "Critical Error Alert" admin@example.com
done

# Alert on high error rate
tail -f var/logs/errors/errors.log | while read line; do
    # Count errors in last minute and alert if > threshold
done
```

**Health Check Alerts:**
```bash
# Monitor health check endpoint
while true; do
    status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/_status)
    if [ "$status" != "200" ]; then
        echo "Health check failed with status: $status" | mail -s "Health Check Alert" admin@example.com
    fi
    sleep 60
done
```

## Migration Guide

### Step 1: Setup Log Directories

```bash
# Create log directory structure
mkdir -p var/logs/{app,security,performance,errors}
chmod 755 var/logs/
chmod 755 var/logs/*
```

### Step 2: Configure Logging

1. Copy `config/logging/logger.php` configuration
2. Update container bindings for specialized loggers
3. Test logger configuration

### Step 3: Implement Health Checks

1. Create health check classes for your dependencies
2. Register health checks in `MonitoringBootstrap`
3. Add health check routes
4. Test health check endpoints

### Step 4: Add Performance Monitoring

1. Inject `PerformanceMonitor` into services
2. Add performance measurements to critical paths
3. Configure performance thresholds
4. Test performance logging

### Step 5: Configure External Integrations

1. Add Sentry/Elasticsearch handlers if needed
2. Configure load balancer health checks
3. Setup monitoring alerts
4. Test end-to-end monitoring

## Conclusion

The monitoring and logging infrastructure provides:

- **Centralized Logging**: Multiple specialized loggers with environment-specific configuration
- **Health Checks**: Comprehensive system health monitoring with multiple endpoints
- **Performance Monitoring**: Detailed performance metrics and automatic alerting
- **Production Ready**: Log rotation, structured logging, and external service integration
- **Observability**: Complete visibility into application behavior and performance

This infrastructure enables proactive monitoring, quick issue detection, and comprehensive observability for production applications.

# Monitoring & Logging Infrastructure

## Overview

This document describes the comprehensive monitoring and logging infrastructure implemented in the MVA Bootstrap project. The system provides centralized logging, health checks, performance monitoring, and observability features essential for production applications.

## Architecture Components

### 1. Centralized Logging System

The logging system uses Monolog with multiple specialized loggers and handlers for different environments and purposes.

#### Logger Configuration

```php
// config/logging/logger.php
return [
    LoggerInterface::class => function (Container $container): LoggerInterface {
        $environment = $_ENV['APP_ENV'] ?? 'development';
        
        if ($environment === 'production') {
            return $container->get('logger.production');
        } elseif ($environment === 'staging') {
            return $container->get('logger.staging');
        } else {
            return $container->get('logger.development');
        }
    },
    
    'logger.security' => function (): LoggerInterface {
        // Security-specific logging configuration
    },
    
    'logger.performance' => function (): LoggerInterface {
        // Performance metrics logging
    },
    
    'logger.audit' => function (): LoggerInterface {
        // Permanent audit trail logging
    },
];
```

#### Environment-Specific Logging

**Development Environment:**
- Console output with colored formatting
- Debug-level logging to files
- Detailed error information

```php
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->pushHandler(new RotatingFileHandler('var/logs/app/debug.log', 0, Logger::DEBUG));
```

**Staging Environment:**
- Application logs with 30-day retention
- Error logs with JSON formatting
- Performance monitoring

```php
$appHandler = new RotatingFileHandler('var/logs/app/application.log', 30, Logger::INFO);
$errorHandler = new RotatingFileHandler('var/logs/errors/errors.log', 30, Logger::ERROR);
```

**Production Environment:**
- Application logs with 90-day retention
- Error logs with 90-day retention
- Critical logs with 365-day retention
- Minimal processors for performance

```php
$appHandler = new RotatingFileHandler('var/logs/app/application.log', 90, Logger::INFO);
$errorHandler = new RotatingFileHandler('var/logs/errors/errors.log', 90, Logger::ERROR);
$criticalHandler = new RotatingFileHandler('var/logs/errors/critical.log', 365, Logger::CRITICAL);
```

#### Specialized Loggers

**Security Logger:**
```php
// Security events and alerts
$securityLogger->info('User login successful', [
    'user_id' => $userId,
    'email' => $email,
    'ip_address' => $clientIp,
    'user_agent' => $userAgent,
]);

$securityLogger->warning('Failed login attempt', [
    'email' => $email,
    'ip_address' => $clientIp,
    'attempt_count' => $attemptCount,
]);

$securityLogger->error('Suspicious activity detected', [
    'user_id' => $userId,
    'activity_type' => 'multiple_failed_logins',
    'ip_address' => $clientIp,
]);
```

**Performance Logger:**
```php
// Performance metrics and slow operations
$performanceLogger->info('HTTP request completed', [
    'method' => $method,
    'path' => $path,
    'status_code' => $statusCode,
    'duration' => $duration,
    'memory_usage' => memory_get_usage(true),
]);

$performanceLogger->warning('Slow database query detected', [
    'query' => substr($query, 0, 200),
    'duration' => $duration,
    'parameters' => $parameters,
]);
```

**Audit Logger:**
```php
// Permanent audit trail (never rotates)
$auditLogger->info('User created', [
    'user_id' => $newUserId,
    'created_by' => $adminUserId,
    'user_data' => $sanitizedUserData,
]);

$auditLogger->info('User permissions changed', [
    'user_id' => $userId,
    'changed_by' => $adminUserId,
    'old_permissions' => $oldPermissions,
    'new_permissions' => $newPermissions,
]);
```

### 2. Application Monitoring

#### Health Check System

The application provides multiple health check endpoints to support various monitoring systems and deployment environments:

- `/_status` - Primary health check endpoint
- `/health` - Alternative health check
- `/healthz` - Kubernetes-style health check
- `/ping` - Simple ping endpoint

All health check endpoints are implemented using a consistent architecture:
```php
HealthCheckAction -> HealthCheckService -> SystemChecks
```

#### Status API

The `/api/status` endpoint provides detailed system information:

```json
{
    "status": "OK",
    "timestamp": 1686557452,
    "version": "1.0.0",
    "app": {
        "name": "MVA Bootstrap",
        "environment": "production",
        "debug": false,
        "timezone": "UTC"
    },
    "php": {
        "version": "8.3.0",
        "memory_limit": "128M",
        "timezone": "UTC"
    }
}
```

This endpoint follows Clean Architecture principles:
- Actions layer (`StatusAction`) handles HTTP concerns
- Service layer (`MonitoringService`) provides business logic
- Infrastructure layer collects system metrics

#### Monitoring Best Practices

1. **Health Checks**
   - Keep checks lightweight and fast
   - Avoid database queries in basic health checks
   - Return appropriate HTTP status codes

2. **Status Endpoint**
   - Include only non-sensitive information
   - Cache heavy metrics where appropriate
   - Version information should be consistent

3. **Security Considerations**
   - Rate limit monitoring endpoints
   - Consider authentication for detailed status
   - Sanitize system information

### 3. Performance Monitoring

The performance monitoring system tracks application metrics, response times, and resource usage.

#### Performance Monitor

```php
final class PerformanceMonitor
{
    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stopTimer(string $name): float
    {
        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);
        $this->recordMetric("timer.{$name}", $duration);
        return $duration;
    }

    public function measure(string $name, callable $callable): mixed
    {
        $this->startTimer($name);
        try {
            return $callable();
        } finally {
            $duration = $this->stopTimer($name);
            $this->performanceLogger->info('Performance measurement', [
                'metric' => $name,
                'duration' => $duration,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
        }
    }

    public function incrementCounter(string $name, int $value = 1): void
    {
        $this->counters[$name] = ($this->counters[$name] ?? 0) + $value;
        $this->recordMetric("counter.{$name}", $this->counters[$name]);
    }

    public function recordMemoryUsage(string $context = 'general'): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $this->recordMetric("memory.usage.{$context}", $memoryUsage);
        $this->recordMetric("memory.peak.{$context}", $memoryPeak);
    }
}
```

#### Performance Metrics

**HTTP Request Monitoring:**
```php
$performanceMonitor->recordHttpRequest(
    method: 'POST',
    path: '/api/users',
    statusCode: 201,
    duration: 0.245
);

// Automatic slow request detection
if ($duration > 2.0) {
    $performanceLogger->warning('Slow HTTP request detected', [
        'method' => $method,
        'path' => $path,
        'status_code' => $statusCode,
        'duration' => $duration,
    ]);
}
```

**Database Query Monitoring:**
```php
$performanceMonitor->recordDatabaseQuery(
    query: 'SELECT * FROM users WHERE email = ?',
    duration: 0.123,
    success: true
);

// Automatic slow query detection
if ($duration > 1.0) {
    $performanceLogger->warning('Slow database query detected', [
        'query' => substr($query, 0, 200),
        'duration' => $duration,
        'success' => $success,
    ]);
}
```

**Memory Usage Tracking:**
```php
// Track memory usage at key points
$performanceMonitor->recordMemoryUsage('after_user_creation');
$performanceMonitor->recordMemoryUsage('after_email_sending');
$performanceMonitor->recordMemoryUsage('request_end');
```

**Custom Metrics:**
```php
// Business metrics
$performanceMonitor->incrementCounter('users.registered');
$performanceMonitor->incrementCounter('emails.sent');
$performanceMonitor->incrementCounter('api.requests.authenticated');

// Technical metrics
$performanceMonitor->incrementCounter('database.queries.total');
$performanceMonitor->incrementCounter('cache.hits');
$performanceMonitor->incrementCounter('cache.misses');
```

## Directory Structure

### Log Directory Organization

```
var/logs/
├── app/
│   ├── application.log          # General application logs
│   ├── debug.log               # Debug logs (development only)
│   └── audit.log               # Permanent audit trail
├── security/
│   ├── security.log            # Security events
│   └── alerts.log              # Security alerts (WARNING+)
├── performance/
│   └── metrics.log             # Performance metrics
└── errors/
    ├── errors.log              # Error logs (ERROR+)
    └── critical.log            # Critical errors (CRITICAL+)
```

### Source Code Organization

```
src/Shared/
├── Logging/
│   ├── Handlers/               # Custom log handlers
│   ├── Formatters/             # Custom log formatters
│   └── Processors/             # Custom log processors
└── Monitoring/
    ├── HealthChecks/
    │   ├── HealthCheckInterface.php
    │   ├── HealthCheckResult.php
    │   ├── HealthStatus.php
    │   ├── HealthCheckManager.php
    │   ├── HealthCheckReport.php
    │   ├── DatabaseHealthCheck.php
    │   └── FilesystemHealthCheck.php
    ├── Metrics/
    │   └── PerformanceMonitor.php
    ├── Actions/
    │   └── HealthCheckAction.php
    └── Bootstrap/
        └── MonitoringBootstrap.php
```

## Usage Examples

### 1. Service Layer Monitoring

```php
final class UserService
{
    public function createUser(string $email, string $name, string $password): array
    {
        return $this->performanceMonitor->measure('user_creation', function() use ($email, $name, $password) {
            // Validate input
            $this->validateUserInput($email, $name, $password);

            // Check if user exists
            if ($this->userRepository->emailExists($email)) {
                $this->securityLogger->warning('Attempt to create duplicate user', [
                    'email' => $email,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                throw UserAlreadyExistsException::withEmail($email);
            }

            // Create user
            $userData = [
                'email' => $email,
                'name' => $name,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $user = $this->userRepository->save($userData);

            // Log successful creation
            $this->auditLogger->info('User created', [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'created_by' => 'system',
            ]);

            // Update metrics
            $this->performanceMonitor->incrementCounter('users.created');

            return $user;
        });
    }
}
```

### 2. Security Event Logging

```php
final class AuthenticationService
{
    public function authenticateForWeb(string $email, string $password, string $clientIp): ?array
    {
        $startTime = microtime(true);

        try {
            $user = $this->userService->authenticate($email, $password);

            if ($user) {
                // Log successful authentication
                $this->securityLogger->info('User login successful', [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                    'ip_address' => $clientIp,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'duration' => microtime(true) - $startTime,
                ]);

                // Update metrics
                $this->performanceMonitor->incrementCounter('auth.login.successful');

                return $user;
            } else {
                // Log failed authentication
                $this->securityLogger->warning('Failed login attempt', [
                    'email' => $email,
                    'ip_address' => $clientIp,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    'duration' => microtime(true) - $startTime,
                ]);

                // Update metrics
                $this->performanceMonitor->incrementCounter('auth.login.failed');

                return null;
            }
        } catch (\Exception $e) {
            // Log authentication error
            $this->securityLogger->error('Authentication error', [
                'email' => $email,
                'ip_address' => $clientIp,
                'error' => $e->getMessage(),
                'duration' => microtime(true) - $startTime,
            ]);

            throw $e;
        }
    }
}
```

### 3. Performance Monitoring in Actions

```php
final class CreateUserAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        try {
            $data = (array) $request->getParsedBody();

            // Create user with performance monitoring
            $user = $this->userService->createUser(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['password'] ?? ''
            );

            $statusCode = 201;
            $responseData = ['user' => $user];

            return $this->jsonResponse($responseData, $statusCode);
        } catch (\Exception $e) {
            $statusCode = $e instanceof ProblemDetailsException ? $e->getStatusCode() : 500;
            throw $e;
        } finally {
            // Record HTTP request metrics
            $duration = microtime(true) - $startTime;
            $this->performanceMonitor->recordHttpRequest($method, $path, $statusCode, $duration);
        }
    }
}
```

### 4. Custom Health Checks

```php
final class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'redis';
    }

    public function check(): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            // Test basic connectivity
            $pong = $this->redis->ping();

            if ($pong !== '+PONG') {
                return HealthCheckResult::unhealthy(
                    $this->getName(),
                    'Redis ping failed',
                    ['response' => $pong],
                    microtime(true) - $startTime
                );
            }

            // Test read/write operations
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';

            $this->redis->set($testKey, $testValue, 10); // 10 second TTL
            $retrievedValue = $this->redis->get($testKey);
            $this->redis->del($testKey);

            if ($retrievedValue !== $testValue) {
                return HealthCheckResult::degraded(
                    $this->getName(),
                    'Redis read/write test failed',
                    ['expected' => $testValue, 'actual' => $retrievedValue],
                    microtime(true) - $startTime
                );
            }

            // Get Redis info
            $info = $this->redis->info();

            return HealthCheckResult::healthy(
                $this->getName(),
                'Redis is accessible and responsive',
                [
                    'version' => $info['redis_version'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 'unknown',
                    'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                    'response_time' => microtime(true) - $startTime,
                ],
                microtime(true) - $startTime
            );
        } catch (\Exception $e) {
            return HealthCheckResult::unhealthy(
                $this->getName(),
                'Redis connection failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    public function getTimeout(): int
    {
        return 3; // 3 seconds timeout
    }

    public function isCritical(): bool
    {
        return false; // Redis is not critical for basic functionality
    }
}
```

---

## 🔄 **Log Rotation & Cleanup**

### **Automatic Log Rotation**

The system implements comprehensive log rotation to prevent disk space issues:

#### **Retention Policies**
```php
// LoggerFactory retention configuration
private const DEFAULT_RETENTION_DAYS = 30;     // General logs
private const PERFORMANCE_RETENTION_DAYS = 14; // Performance logs
private const DEBUG_RETENTION_DAYS = 7;        // Debug logs
private const AUDIT_RETENTION_DAYS = 365;      // Audit logs (compliance)
private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB per file
```

#### **Log Types & Retention**
| Log Type | Retention | Compression | Purpose |
|----------|-----------|-------------|---------|
| **Security** | 30 days | After 7 days | Security incident investigation |
| **Performance** | 14 days | After 7 days | Performance monitoring |
| **Debug** | 7 days | After 3 days | Development debugging |
| **Audit** | 365 days | After 30 days | Compliance requirements |
| **General** | 30 days | After 7 days | Application monitoring |
| **Errors** | 30 days | After 7 days | Error tracking |

### **Manual Log Management**

#### **CLI Tools**
```bash
# Show log statistics
php bin/log-cleanup stats

# Bash script for full management
./bin/log-rotation stats      # Show statistics
./bin/log-rotation cleanup    # Clean old files
./bin/log-rotation compress   # Compress old files
./bin/log-rotation health     # Health check
./bin/log-rotation full       # Complete maintenance
```

#### **Automated Cron Jobs**
```bash
# Recommended cron schedule
0 2 * * * /path/to/bootstrap/bin/log-rotation cleanup    # Daily cleanup
0 3 * * 0 /path/to/bootstrap/bin/log-rotation compress   # Weekly compression
0 4 1 * * /path/to/bootstrap/bin/log-rotation health     # Monthly health check
```

### **Log Directory Structure**
```
var/logs/
├── debug-app.log              # Current application debug log
├── debug-app-2025-06-19.log   # Rotated debug log
├── debug-profile.log          # Current profile debug log
├── security-2025-06-20.log    # Daily security log
├── performance-2025-06-20.log # Daily performance log
├── audit-2025-06-20.log       # Daily audit log
├── errors.log                 # Current error log
└── compressed/
    ├── debug-app-2025-06-01.log.gz    # Compressed old logs
    └── security-2025-06-01.log.gz
```

### **Health Monitoring**
```bash
# Check log rotation health
./bin/log-rotation health

# Output example:
🏥 Log Rotation Health Check
==================================================
Status: ✅ healthy

✅ All log rotation systems are working properly!
```

For detailed log rotation documentation, see: **[Log Rotation Guide](../LOG_ROTATION.md)**

---

## 📈 **Best Practices**

### **Production Logging**
- ✅ **Use structured logging** with consistent context
- ✅ **Implement log rotation** to prevent disk issues
- ✅ **Monitor log health** with automated checks
- ✅ **Separate security logs** for audit compliance
- ✅ **Compress old logs** to save disk space
- ✅ **Set up alerting** for critical log events

### **Development Logging**
- ✅ **Use debug level** for detailed information
- ✅ **Shorter retention** (3-7 days) for faster development
- ✅ **Console output** for immediate feedback
- ✅ **Disable compression** for easier debugging

### **Security Considerations**
- ✅ **Secure log files** with proper permissions (644)
- ✅ **Sanitize sensitive data** before logging
- ✅ **Audit log access** and modifications
- ✅ **Backup critical logs** to separate storage
- ✅ **Monitor for log tampering** attempts

---

## 🎯 **Summary**

**MVA Bootstrap provides enterprise-grade logging infrastructure:**

- ✅ **Unified formatting** - Consistent, readable log output
- ✅ **Specialized loggers** - Security, audit, performance, and general
- ✅ **Automatic rotation** - Prevents disk space issues
- ✅ **Manual management** - CLI tools for maintenance
- ✅ **Health monitoring** - Proactive issue detection
- ✅ **Production ready** - Battle-tested in enterprise environments

**Your application logging is comprehensive, maintainable, and production-ready!** 🚀
