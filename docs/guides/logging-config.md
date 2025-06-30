# Logging Configuration Guide

Komplexný sprievodca konfiguráciou logovania v HDM Boot aplikácii.

## 📊 Logging Overview

HDM Boot používa **Monolog** s viacúrovňovým logovaním:

- **Application Logs** - Hlavné aplikačné logy
- **Error Logs** - Chyby a výnimky
- **Security Logs** - Bezpečnostné udalosti
- **Performance Logs** - Výkonnostné metriky
- **Audit Logs** - Audit trail pre admin akcie

## 🏗️ Logging Architecture

```
Logging Flow:
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│ Application │───▶│   Monolog    │───▶│   Handlers  │
│   Events    │    │   Logger     │    │             │
└─────────────┘    └──────────────┘    └─────────────┘
                           │                    │
                           ▼                    ▼
                   ┌──────────────┐    ┌─────────────┐
                   │  Processors  │    │ Formatters  │
                   │ (Context)    │    │ (JSON/Line) │
                   └──────────────┘    └─────────────┘
```

## 🔧 Basic Configuration

### Logging Service Configuration

```php
<?php
// config/services/logging.php

use Monolog\Logger;
use Monolog\Handler\{StreamHandler, RotatingFileHandler, SyslogHandler};
use Monolog\Processor\{WebProcessor, IntrospectionProcessor, MemoryUsageProcessor};
use Monolog\Formatter\{JsonFormatter, LineFormatter};

return [
    'default_channel' => $_ENV['LOG_CHANNEL'] ?? 'file',
    'default_level' => $_ENV['LOG_LEVEL'] ?? 'info',
    
    'channels' => [
        'app' => [
            'handlers' => [
                [
                    'type' => 'rotating_file',
                    'path' => 'var/logs/app/application.log',
                    'level' => 'debug',
                    'max_files' => 30,
                    'formatter' => 'json',
                ],
                [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'level' => 'error',
                    'formatter' => 'line',
                ],
            ],
            'processors' => ['web', 'memory', 'introspection'],
        ],
        
        'error' => [
            'handlers' => [
                [
                    'type' => 'rotating_file',
                    'path' => 'var/logs/errors/error.log',
                    'level' => 'error',
                    'max_files' => 90,
                    'formatter' => 'json',
                ],
                [
                    'type' => 'syslog',
                    'ident' => 'hdm-boot-error',
                    'level' => 'critical',
                ],
            ],
            'processors' => ['web', 'introspection'],
        ],
        
        'security' => [
            'handlers' => [
                [
                    'type' => 'rotating_file',
                    'path' => 'var/logs/security/security.log',
                    'level' => 'info',
                    'max_files' => 365,
                    'formatter' => 'json',
                ],
            ],
            'processors' => ['web', 'security'],
        ],
        
        'performance' => [
            'handlers' => [
                [
                    'type' => 'rotating_file',
                    'path' => 'var/logs/performance/metrics.log',
                    'level' => 'info',
                    'max_files' => 30,
                    'formatter' => 'json',
                ],
            ],
            'processors' => ['memory', 'performance'],
        ],
        
        'audit' => [
            'handlers' => [
                [
                    'type' => 'rotating_file',
                    'path' => 'var/logs/audit/audit.log',
                    'level' => 'info',
                    'max_files' => 365,
                    'formatter' => 'json',
                ],
                [
                    'type' => 'database',
                    'table' => 'audit_logs',
                    'level' => 'info',
                ],
            ],
            'processors' => ['web', 'audit'],
        ],
    ],
    
    'formatters' => [
        'json' => [
            'class' => JsonFormatter::class,
            'options' => [
                'includeStacktraces' => true,
            ],
        ],
        'line' => [
            'class' => LineFormatter::class,
            'options' => [
                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => 'Y-m-d H:i:s',
            ],
        ],
    ],
    
    'processors' => [
        'web' => WebProcessor::class,
        'memory' => MemoryUsageProcessor::class,
        'introspection' => IntrospectionProcessor::class,
        'security' => SecurityProcessor::class,
        'performance' => PerformanceProcessor::class,
        'audit' => AuditProcessor::class,
    ],
];
```

### Logger Factory

```php
<?php
// src/SharedKernel/Infrastructure/Logging/LoggerFactory.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging;

use Monolog\Logger;
use Monolog\Handler\{StreamHandler, RotatingFileHandler, SyslogHandler};
use Monolog\Processor\{WebProcessor, IntrospectionProcessor, MemoryUsageProcessor};
use Monolog\Formatter\{JsonFormatter, LineFormatter};
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    private array $config;
    private array $loggers = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function create(string $channel = null): LoggerInterface
    {
        $channel = $channel ?? $this->config['default_channel'];
        
        if (!isset($this->loggers[$channel])) {
            $this->loggers[$channel] = $this->createLogger($channel);
        }

        return $this->loggers[$channel];
    }

    private function createLogger(string $channel): Logger
    {
        if (!isset($this->config['channels'][$channel])) {
            throw new \InvalidArgumentException("Logger channel '{$channel}' not configured");
        }

        $channelConfig = $this->config['channels'][$channel];
        $logger = new Logger($channel);

        // Add handlers
        foreach ($channelConfig['handlers'] as $handlerConfig) {
            $handler = $this->createHandler($handlerConfig);
            $logger->pushHandler($handler);
        }

        // Add processors
        foreach ($channelConfig['processors'] ?? [] as $processorName) {
            $processor = $this->createProcessor($processorName);
            $logger->pushProcessor($processor);
        }

        return $logger;
    }

    private function createHandler(array $config): \Monolog\Handler\HandlerInterface
    {
        $level = $this->parseLevel($config['level'] ?? $this->config['default_level']);
        
        $handler = match ($config['type']) {
            'stream' => new StreamHandler($config['path'], $level),
            'rotating_file' => new RotatingFileHandler(
                $config['path'], 
                $config['max_files'] ?? 30, 
                $level
            ),
            'syslog' => new SyslogHandler($config['ident'] ?? 'hdm-boot', LOG_USER, $level),
            'database' => new DatabaseHandler($config['table'], $level),
            default => throw new \InvalidArgumentException("Unknown handler type: {$config['type']}")
        };

        // Set formatter
        if (isset($config['formatter'])) {
            $formatter = $this->createFormatter($config['formatter']);
            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    private function createFormatter(string $formatterName): \Monolog\Formatter\FormatterInterface
    {
        if (!isset($this->config['formatters'][$formatterName])) {
            throw new \InvalidArgumentException("Formatter '{$formatterName}' not configured");
        }

        $formatterConfig = $this->config['formatters'][$formatterName];
        $class = $formatterConfig['class'];
        $options = $formatterConfig['options'] ?? [];

        return new $class(...array_values($options));
    }

    private function createProcessor(string $processorName): callable
    {
        if (!isset($this->config['processors'][$processorName])) {
            throw new \InvalidArgumentException("Processor '{$processorName}' not configured");
        }

        $processorClass = $this->config['processors'][$processorName];
        
        return new $processorClass();
    }

    private function parseLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::INFO
        };
    }
}
```

## 🔍 Custom Processors

### Security Processor

```php
<?php
// src/SharedKernel/Infrastructure/Logging/Processor/SecurityProcessor.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging\Processor;

use Monolog\Processor\ProcessorInterface;

final class SecurityProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        $record['extra']['security'] = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id() ?: null,
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid(),
        ];

        // Add user context if available
        if (isset($_SESSION['user_id'])) {
            $record['extra']['security']['user_id'] = $_SESSION['user_id'];
        }

        return $record;
    }
}
```

### Performance Processor

```php
<?php
// src/SharedKernel/Infrastructure/Logging/Processor/PerformanceProcessor.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging\Processor;

use Monolog\Processor\ProcessorInterface;

final class PerformanceProcessor implements ProcessorInterface
{
    private float $startTime;

    public function __construct()
    {
        $this->startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
    }

    public function __invoke(array $record): array
    {
        $record['extra']['performance'] = [
            'execution_time' => microtime(true) - $this->startTime,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_usage' => sys_getloadavg()[0] ?? null,
        ];

        return $record;
    }
}
```

### Audit Processor

```php
<?php
// src/SharedKernel/Infrastructure/Logging/Processor/AuditProcessor.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging\Processor;

use Monolog\Processor\ProcessorInterface;

final class AuditProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        $record['extra']['audit'] = [
            'timestamp' => time(),
            'action' => $record['context']['action'] ?? 'unknown',
            'resource' => $record['context']['resource'] ?? 'unknown',
            'resource_id' => $record['context']['resource_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ];

        return $record;
    }
}
```

## 🗄️ Database Handler

### Custom Database Handler

```php
<?php
// src/SharedKernel/Infrastructure/Logging/Handler/DatabaseHandler.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use PDO;

final class DatabaseHandler extends AbstractProcessingHandler
{
    private PDO $pdo;
    private string $table;

    public function __construct(string $table, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->table = $table;
        $this->pdo = $this->createConnection();
        $this->createTable();
    }

    protected function write(LogRecord $record): void
    {
        $sql = "INSERT INTO {$this->table} 
                (channel, level, level_name, message, context, extra, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $record['channel'],
            $record['level'],
            $record['level_name'],
            $record['message'],
            json_encode($record['context']),
            json_encode($record['extra']),
            $record['datetime']->format('Y-m-d H:i:s'),
        ]);
    }

    private function createConnection(): PDO
    {
        $dsn = $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/logs.db';
        return new PDO($dsn);
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            channel VARCHAR(255) NOT NULL,
            level INTEGER NOT NULL,
            level_name VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            context TEXT,
            extra TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_channel (channel),
            INDEX idx_level (level),
            INDEX idx_created_at (created_at)
        )";

        $this->pdo->exec($sql);
    }
}
```

## 📱 Application Logging

### Structured Logging Service

```php
<?php
// src/SharedKernel/Infrastructure/Logging/StructuredLogger.php

namespace HdmBoot\SharedKernel\Infrastructure\Logging;

use Psr\Log\LoggerInterface;

final class StructuredLogger
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function logUserAction(string $action, string $userId, array $context = []): void
    {
        $this->logger->info('User action performed', [
            'action' => $action,
            'user_id' => $userId,
            'context' => $context,
            'timestamp' => time(),
        ]);
    }

    public function logSecurityEvent(string $event, string $severity, array $details = []): void
    {
        $level = match ($severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'error',
            'critical' => 'critical',
            default => 'info'
        };

        $this->logger->log($level, "Security event: {$event}", [
            'event_type' => 'security',
            'severity' => $severity,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
    }

    public function logPerformanceMetric(string $metric, float $value, array $tags = []): void
    {
        $this->logger->info("Performance metric: {$metric}", [
            'metric_type' => 'performance',
            'metric_name' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true),
        ]);
    }

    public function logApiRequest(string $method, string $endpoint, int $statusCode, float $duration): void
    {
        $this->logger->info('API request', [
            'request_type' => 'api',
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration' => $duration,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }

    public function logDatabaseQuery(string $query, array $params, float $duration): void
    {
        $this->logger->debug('Database query', [
            'query_type' => 'database',
            'query' => $query,
            'params' => $params,
            'duration' => $duration,
        ]);
    }
}
```

## 🔄 Log Rotation & Cleanup

### Log Rotation Script

```bash
#!/bin/bash
# scripts/log-rotation.sh

LOG_DIR="var/logs"
RETENTION_DAYS=30
ARCHIVE_DIR="var/logs/archive"

# Create archive directory
mkdir -p "$ARCHIVE_DIR"

# Function to rotate logs
rotate_logs() {
    local log_pattern="$1"
    local retention="$2"
    
    echo "Rotating logs: $log_pattern (retention: $retention days)"
    
    # Find and compress old logs
    find "$LOG_DIR" -name "$log_pattern" -type f -mtime +1 -exec gzip {} \;
    
    # Move compressed logs to archive
    find "$LOG_DIR" -name "${log_pattern}.gz" -type f -mtime +7 -exec mv {} "$ARCHIVE_DIR/" \;
    
    # Delete old archived logs
    find "$ARCHIVE_DIR" -name "${log_pattern}.gz" -type f -mtime +$retention -delete
}

# Rotate different log types
rotate_logs "application-*.log" 30
rotate_logs "error-*.log" 90
rotate_logs "security-*.log" 365
rotate_logs "performance-*.log" 30
rotate_logs "audit-*.log" 365

echo "Log rotation completed"
```

### Logrotate Configuration

```bash
# /etc/logrotate.d/hdm-boot
/var/www/hdm-boot/var/logs/*/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 hdm-boot hdm-boot
    postrotate
        /usr/bin/systemctl reload php8.3-fpm > /dev/null 2>&1 || true
    endscript
}

/var/www/hdm-boot/var/logs/security/*.log {
    daily
    missingok
    rotate 365
    compress
    delaycompress
    notifempty
    create 644 hdm-boot hdm-boot
}

/var/www/hdm-boot/var/logs/audit/*.log {
    daily
    missingok
    rotate 365
    compress
    delaycompress
    notifempty
    create 644 hdm-boot hdm-boot
}
```

## 📊 Log Monitoring

### Log Analysis Script

```bash
#!/bin/bash
# scripts/log-analysis.sh

LOG_DIR="var/logs"
REPORT_FILE="var/logs/daily-report.txt"

echo "HDM Boot Daily Log Report - $(date)" > "$REPORT_FILE"
echo "=================================" >> "$REPORT_FILE"

# Error summary
echo "" >> "$REPORT_FILE"
echo "Error Summary:" >> "$REPORT_FILE"
echo "-------------" >> "$REPORT_FILE"
grep -h "ERROR\|CRITICAL" "$LOG_DIR"/errors/*.log | tail -20 >> "$REPORT_FILE"

# Security events
echo "" >> "$REPORT_FILE"
echo "Security Events:" >> "$REPORT_FILE"
echo "---------------" >> "$REPORT_FILE"
grep -h "security" "$LOG_DIR"/security/*.log | tail -10 >> "$REPORT_FILE"

# Performance metrics
echo "" >> "$REPORT_FILE"
echo "Performance Summary:" >> "$REPORT_FILE"
echo "-------------------" >> "$REPORT_FILE"
grep -h "slow\|performance" "$LOG_DIR"/performance/*.log | tail -10 >> "$REPORT_FILE"

# Send report via email (optional)
if command -v mail &> /dev/null; then
    mail -s "HDM Boot Daily Log Report" admin@your-domain.com < "$REPORT_FILE"
fi

echo "Log analysis completed. Report saved to $REPORT_FILE"
```

## 📋 Logging Configuration Checklist

### Setup:
- [ ] Monolog nakonfigurovaný
- [ ] Log directories vytvorené s správnymi permissions
- [ ] Log rotation nastavený
- [ ] Custom processors implementované

### Configuration:
- [ ] Rôzne log levels pre rôzne environments
- [ ] Structured logging implementované
- [ ] Security logging nakonfigurované
- [ ] Performance logging nastavené

### Monitoring:
- [ ] Log monitoring scripts vytvorené
- [ ] Alerting pre critical errors nastavené
- [ ] Daily reports nakonfigurované
- [ ] Log analysis tools implementované

### Maintenance:
- [ ] Log cleanup scripts vytvorené
- [ ] Archive strategy implementovaná
- [ ] Backup procedures nastavené
- [ ] Performance monitoring logs

## 🔗 Ďalšie zdroje

- [Monitoring & Logging Architecture](../architecture/monitoring-logging.md)
- [Security Best Practices](security-practices.md)
- [Environment Configuration](environment-config.md)
- [Troubleshooting Guide](../TROUBLESHOOTING.md)
