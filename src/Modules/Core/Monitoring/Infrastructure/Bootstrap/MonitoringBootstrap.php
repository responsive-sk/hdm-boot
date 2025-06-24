<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Infrastructure\Bootstrap;

use HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\DatabaseHealthCheck;
use HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\FilesystemHealthCheck;
use HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Monitoring Bootstrap.
 *
 * Sets up monitoring infrastructure including health checks and performance monitoring.
 */
final class MonitoringBootstrap
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
        private readonly Paths $paths
    ) {
    }

    /**
     * Bootstrap monitoring system.
     */
    public function bootstrap(): void
    {
        $this->logger->info('Starting monitoring bootstrap');

        // Setup health checks
        $this->setupHealthChecks();

        // Setup performance monitoring
        $this->setupPerformanceMonitoring();

        $this->logger->info('Monitoring bootstrap completed');
    }

    /**
     * Setup health checks.
     */
    private function setupHealthChecks(): void
    {
        $healthCheckManagerRaw = $this->container->get(HealthCheckManager::class);

        if (!$healthCheckManagerRaw instanceof HealthCheckManager) {
            $this->logger->error('Failed to get HealthCheckManager from container');

            return;
        }

        $healthCheckManager = $healthCheckManagerRaw;

        // Register database health check
        $this->registerDatabaseHealthCheck($healthCheckManager);

        // Register filesystem health check
        $this->registerFilesystemHealthCheck($healthCheckManager);

        // Register application health check
        $this->registerApplicationHealthCheck($healthCheckManager);

        $this->logger->info('Health checks registered', [
            'registered_checks' => $healthCheckManager->getRegisteredChecks(),
        ]);
    }

    /**
     * Register database health check.
     */
    private function registerDatabaseHealthCheck(HealthCheckManager $manager): void
    {
        try {
            $pdoRaw = $this->container->get(PDO::class);

            if (!$pdoRaw instanceof \PDO) {
                $this->logger->warning('Failed to get PDO instance from container');

                return;
            }

            $pdo = $pdoRaw;
            $databaseHealthCheck = new DatabaseHealthCheck($pdo, $this->logger);
            $manager->registerHealthCheck($databaseHealthCheck);

            $this->logger->debug('Database health check registered');
        } catch (\Exception $e) {
            $this->logger->warning('Could not register database health check', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register filesystem health check.
     */
    private function registerFilesystemHealthCheck(HealthCheckManager $manager): void
    {
        try {
            $filesystemHealthCheck = new FilesystemHealthCheck($this->logger, $this->paths);
            $manager->registerHealthCheck($filesystemHealthCheck);

            $this->logger->debug('Filesystem health check registered', [
                'base_path' => $this->paths->base(),
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Could not register filesystem health check', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register application health check.
     */
    private function registerApplicationHealthCheck(HealthCheckManager $manager): void
    {
        try {
            $applicationHealthCheck = new ApplicationHealthCheck($this->logger);
            $manager->registerHealthCheck($applicationHealthCheck);

            $this->logger->debug('Application health check registered');
        } catch (\Exception $e) {
            $this->logger->warning('Could not register application health check', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Setup performance monitoring.
     */
    private function setupPerformanceMonitoring(): void
    {
        try {
            $performanceMonitorRaw = $this->container->get(\HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor::class);

            if (!is_object($performanceMonitorRaw) || !method_exists($performanceMonitorRaw, 'recordMemoryUsage')) {
                $this->logger->warning('Failed to get valid PerformanceMonitor from container');

                return;
            }

            // Record initial memory usage
            $performanceMonitorRaw->recordMemoryUsage('bootstrap');

            $this->logger->debug('Performance monitoring initialized');
        } catch (\Exception $e) {
            $this->logger->warning('Could not setup performance monitoring', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

/**
 * Simple Application Health Check.
 */
class ApplicationHealthCheck implements \HdmBoot\SharedKernel\HealthChecks\Contracts\HealthCheckInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'application';
    }

    public function check(): \HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            $this->logger->debug('Running application health check');

            // Check basic PHP functionality
            $phpVersion = PHP_VERSION;
            $memoryLimit = ini_get('memory_limit');
            $maxExecutionTime = ini_get('max_execution_time');

            // Check if required extensions are loaded
            $requiredExtensions = ['pdo', 'json', 'mbstring'];
            $missingExtensions = [];

            foreach ($requiredExtensions as $extension) {
                if (!extension_loaded($extension)) {
                    $missingExtensions[] = $extension;
                }
            }

            if (!empty($missingExtensions)) {
                return \HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult::unhealthy(
                    $this->getName(),
                    'Missing required PHP extensions: ' . implode(', ', $missingExtensions),
                    [
                        'missing_extensions' => $missingExtensions,
                        'php_version'        => $phpVersion,
                    ],
                    microtime(true) - $startTime
                );
            }

            return \HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult::healthy(
                $this->getName(),
                'Application is running normally',
                [
                    'php_version'        => $phpVersion,
                    'memory_limit'       => $memoryLimit,
                    'max_execution_time' => $maxExecutionTime,
                    'loaded_extensions'  => count(get_loaded_extensions()),
                ],
                microtime(true) - $startTime
            );
        } catch (\Exception $e) {
            return \HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult::unhealthy(
                $this->getName(),
                'Application health check failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    public function getTimeout(): int
    {
        return 2; // 2 seconds timeout
    }

    public function isCritical(): bool
    {
        return true; // Application health is critical
    }

    public function getCategory(): string
    {
        return 'application';
    }

    public function getTags(): array
    {
        return ['application', 'php', 'critical'];
    }
}
