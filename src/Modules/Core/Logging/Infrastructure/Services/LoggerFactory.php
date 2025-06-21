<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Logging\Infrastructure\Services;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

/**
 * Logger Factory.
 *
 * Creates properly configured Monolog loggers for different channels.
 * Inspired by enterprise logging best practices.
 */
final class LoggerFactory
{
    // Log rotation configuration constants
    private const DEFAULT_RETENTION_DAYS = 30;
    private const PERFORMANCE_RETENTION_DAYS = 14;
    private const AUDIT_RETENTION_DAYS = 365; // 1 year for compliance

    private readonly string $logPath;

    private readonly string $environment;

    private readonly bool $debug;

    public function __construct(
        \ResponsiveSk\Slim4Paths\Paths $paths,
        string $environment = 'production',
        bool $debug = false
    ) {
        $this->logPath = $paths->logs();
        $this->environment = $environment;
        $this->debug = $debug;

        // Ensure logs directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0o755, true);
        }
    }

    /**
     * Create application logger.
     */
    public function createLogger(string $channel = 'app'): LoggerInterface
    {
        $logger = new Logger($channel);

        // Add processors for context
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new ProcessIdProcessor());

        // Configure handlers based on environment
        if ($this->environment === 'production') {
            $this->addProductionHandlers($logger, $channel);
        } else {
            $this->addDevelopmentHandlers($logger, $channel);
        }

        return $logger;
    }

    /**
     * Create security logger for authentication, authorization, etc.
     */
    public function createSecurityLogger(): LoggerInterface
    {
        $logger = new Logger('security');

        // Security logs need special handling
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(function (\Monolog\LogRecord $record): \Monolog\LogRecord {
            // Monolog 3.x uses LogRecord objects
            $extra = $record->extra;
            $extra['security_event'] = true;
            $extra['timestamp_iso'] = date('c');
            return $record->with(extra: $extra);
        });

        // Always log security events to separate file
        $securityHandler = new RotatingFileHandler(
            $this->logPath . '/security.log',
            self::DEFAULT_RETENTION_DAYS, // Keep 30 days
            Logger::INFO
        );

        $securityHandler->setFormatter($this->createSecurityFormatter());
        $logger->pushHandler($securityHandler);

        // In production, also send critical security events to syslog
        if ($this->environment === 'production') {
            $criticalHandler = new StreamHandler('php://stderr', Logger::CRITICAL);
            $logger->pushHandler($criticalHandler);
        }

        return $logger;
    }

    /**
     * Create performance logger for monitoring.
     */
    public function createPerformanceLogger(): LoggerInterface
    {
        $logger = new Logger('performance');

        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(function (\Monolog\LogRecord $record): \Monolog\LogRecord {
            // Monolog 3.x uses LogRecord objects
            $extra = $record->extra;
            $extra['performance_metric'] = true;
            return $record->with(extra: $extra);
        });

        $perfHandler = new RotatingFileHandler(
            $this->logPath . '/performance.log',
            self::PERFORMANCE_RETENTION_DAYS, // Keep 14 days
            Logger::INFO
        );

        $perfHandler->setFormatter($this->createPerformanceFormatter());
        $logger->pushHandler($perfHandler);

        return $logger;
    }

    /**
     * Create audit logger for compliance and tracking.
     */
    public function createAuditLogger(): LoggerInterface
    {
        $logger = new Logger('audit');

        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(function (\Monolog\LogRecord $record): \Monolog\LogRecord {
            // Monolog 3.x uses LogRecord objects
            $extra = $record->extra;
            $extra['audit_event'] = true;
            $extra['timestamp_iso'] = date('c');
            return $record->with(extra: $extra);
        });

        // Audit logs are critical and should be preserved
        $auditHandler = new RotatingFileHandler(
            $this->logPath . '/audit.log',
            self::AUDIT_RETENTION_DAYS, // Keep 1 year
            Logger::INFO
        );

        $auditHandler->setFormatter($this->createAuditFormatter());
        $logger->pushHandler($auditHandler);

        return $logger;
    }

    /**
     * Add production handlers.
     */
    private function addProductionHandlers(Logger $logger, string $channel): void
    {
        // Rotating file handler for general logs
        $fileHandler = new RotatingFileHandler(
            $this->logPath . "/{$channel}.log",
            self::DEFAULT_RETENTION_DAYS, // Keep 30 days
            Logger::INFO
        );
        $fileHandler->setFormatter($this->createProductionFormatter());
        $logger->pushHandler($fileHandler);

        // Error handler for critical issues
        $errorHandler = new RotatingFileHandler(
            $this->logPath . '/errors.log',
            self::DEFAULT_RETENTION_DAYS, // Keep 30 days
            Logger::ERROR
        );
        $errorHandler->setFormatter($this->createErrorFormatter());
        $logger->pushHandler($errorHandler);
    }

    /**
     * Add development handlers.
     */
    private function addDevelopmentHandlers(Logger $logger, string $channel): void
    {
        // Console output for development
        $consoleHandler = new StreamHandler('php://stdout', Logger::DEBUG);
        $consoleHandler->setFormatter($this->createDevelopmentFormatter());
        $logger->pushHandler($consoleHandler);

        // File handler for debugging
        if ($this->debug) {
            $debugHandler = new StreamHandler(
                $this->logPath . "/debug-{$channel}.log",
                Logger::DEBUG
            );
            $debugHandler->setFormatter($this->createDebugFormatter());
            $logger->pushHandler($debugHandler);
        }
    }

    /**
     * Create production formatter.
     */
    private function createProductionFormatter(): LineFormatter
    {
        return new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create development formatter.
     */
    private function createDevelopmentFormatter(): LineFormatter
    {
        return new LineFormatter(
            "🚀 [%datetime%] %channel%.%level_name%: %message% %context%\n",
            'H:i:s',
            true,
            true
        );
    }

    /**
     * Create unified debug formatter for consistency.
     */
    private function createDebugFormatter(): LineFormatter
    {
        return new LineFormatter(
            "🔍 [%datetime%] %channel%.%level_name%: %message% | Context: %context% | Extra: %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create unified security formatter.
     */
    private function createSecurityFormatter(): LineFormatter
    {
        return new LineFormatter(
            "🔒 [%datetime%] security.%level_name%: %message% | Context: %context% | Extra: %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create error formatter.
     */
    private function createErrorFormatter(): LineFormatter
    {
        return new LineFormatter(
            "❌ [%datetime%] ERROR.%level_name%: %message%\nContext: %context%\nExtra: %extra%\n" . str_repeat('-', 80) . "\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create unified performance formatter.
     */
    private function createPerformanceFormatter(): LineFormatter
    {
        return new LineFormatter(
            "⚡ [%datetime%] performance.%level_name%: %message% | Context: %context% | Extra: %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create unified audit formatter.
     */
    private function createAuditFormatter(): LineFormatter
    {
        return new LineFormatter(
            "📋 [%datetime%] audit.%level_name%: %message% | Context: %context% | Extra: %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }
}
