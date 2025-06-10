<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Psr\Log\LoggerInterface;

/**
 * Logger Factory.
 *
 * Creates properly configured Monolog loggers for different channels.
 * Inspired by enterprise logging best practices.
 */
final class LoggerFactory
{
    private readonly string $logPath;
    private readonly string $environment;
    private readonly bool $debug;

    public function __construct(
        \ResponsiveSk\Slim4Paths\Paths $paths,
        string $environment = 'production',
        bool $debug = false
    ) {
        $this->logPath = $paths->base() . '/logs';
        $this->environment = $environment;
        $this->debug = $debug;

        // Ensure logs directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
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
        $logger->pushProcessor(function (array $record): array {
            // Add security context
            if (!isset($record['extra']) || !is_array($record['extra'])) {
                $record['extra'] = [];
            }
            $record['extra']['security_event'] = true;
            $record['extra']['timestamp_iso'] = date('c');
            return $record;
        });

        // Always log security events to separate file
        $securityHandler = new RotatingFileHandler(
            $this->logPath . '/security.log',
            30, // Keep 30 days
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
        $logger->pushProcessor(function (array $record): array {
            if (!isset($record['extra']) || !is_array($record['extra'])) {
                $record['extra'] = [];
            }
            $record['extra']['performance_metric'] = true;
            return $record;
        });

        $perfHandler = new RotatingFileHandler(
            $this->logPath . '/performance.log',
            7, // Keep 7 days
            Logger::INFO
        );

        $perfHandler->setFormatter($this->createPerformanceFormatter());
        $logger->pushHandler($perfHandler);

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
            14, // Keep 14 days
            Logger::INFO
        );
        $fileHandler->setFormatter($this->createProductionFormatter());
        $logger->pushHandler($fileHandler);

        // Error handler for critical issues
        $errorHandler = new RotatingFileHandler(
            $this->logPath . '/errors.log',
            30, // Keep 30 days
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
     * Create debug formatter.
     */
    private function createDebugFormatter(): LineFormatter
    {
        return new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message%\nContext: %context%\nExtra: %extra%\n---\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }

    /**
     * Create security formatter.
     */
    private function createSecurityFormatter(): LineFormatter
    {
        return new LineFormatter(
            "🔒 [%datetime%] SECURITY.%level_name%: %message% | Context: %context% | Extra: %extra%\n",
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
     * Create performance formatter.
     */
    private function createPerformanceFormatter(): LineFormatter
    {
        return new LineFormatter(
            "⚡ [%datetime%] PERF: %message% | Memory: %extra.memory_usage% | Context: %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
    }
}
