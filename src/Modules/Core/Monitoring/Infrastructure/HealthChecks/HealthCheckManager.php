<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks;

use HdmBoot\SharedKernel\HealthChecks\Contracts\HealthCheckInterface;
use HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckReport;
use HdmBoot\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult;
use Psr\Log\LoggerInterface;

/**
 * Health Check Manager.
 *
 * Manages and executes health checks, providing overall system health status.
 */
final class HealthCheckManager
{
    /** @var array<HealthCheckInterface> */
    private array $healthChecks = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register a health check.
     */
    public function registerHealthCheck(HealthCheckInterface $healthCheck): void
    {
        $this->healthChecks[$healthCheck->getName()] = $healthCheck;

        $this->logger->debug('Health check registered', [
            'name'     => $healthCheck->getName(),
            'critical' => $healthCheck->isCritical(),
            'timeout'  => $healthCheck->getTimeout(),
        ]);
    }

    /**
     * Run all health checks.
     */
    public function checkHealth(): HealthCheckReport
    {
        $startTime = microtime(true);
        $results = [];

        $this->logger->info('Starting health checks', [
            'total_checks' => count($this->healthChecks),
        ]);

        foreach ($this->healthChecks as $healthCheck) {
            $result = $this->executeHealthCheck($healthCheck);
            $results[$result->name] = $result;

            $this->logger->info('Health check completed', [
                'name'     => $result->name,
                'status'   => $result->status->value,
                'duration' => $result->duration,
                'message'  => $result->message,
            ]);
        }

        $totalDuration = microtime(true) - $startTime;

        $report = HealthCheckReport::fromResults($results);

        $this->logger->info('Health checks completed', [
            'overall_status'   => $report->overallStatus->value,
            'total_duration'   => $totalDuration,
            'healthy_checks'   => $report->healthyChecks,
            'unhealthy_checks' => $report->unhealthyChecks,
            'degraded_checks'  => $report->degradedChecks,
        ]);

        return $report;
    }

    /**
     * Run a specific health check by name.
     */
    public function checkSpecific(string $name): ?HealthCheckResult
    {
        if (!isset($this->healthChecks[$name])) {
            return null;
        }

        return $this->executeHealthCheck($this->healthChecks[$name]);
    }

    /**
     * Get all registered health check names.
     *
     * @return array<string>
     */
    public function getRegisteredChecks(): array
    {
        return array_keys($this->healthChecks);
    }

    /**
     * Execute a single health check with timeout handling.
     */
    private function executeHealthCheck(HealthCheckInterface $healthCheck): HealthCheckResult
    {
        $startTime = microtime(true);

        try {
            // Set timeout for the health check
            $timeout = $healthCheck->getTimeout();

            // Use a simple timeout mechanism
            $result = $this->executeWithTimeout($healthCheck, $timeout);

            if ($result === null) {
                return HealthCheckResult::unhealthy(
                    $healthCheck->getName(),
                    "Health check timed out after {$timeout} seconds",
                    ['timeout' => $timeout],
                    microtime(true) - $startTime
                );
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Health check execution failed', [
                'name'  => $healthCheck->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return HealthCheckResult::unhealthy(
                $healthCheck->getName(),
                'Health check failed: ' . $e->getMessage(),
                ['error_type' => get_class($e)],
                microtime(true) - $startTime
            );
        }
    }

    /**
     * Execute health check with timeout.
     *
     * Note: This is a simple implementation. In production, you might want
     * to use more sophisticated timeout mechanisms.
     */
    private function executeWithTimeout(HealthCheckInterface $healthCheck, int $timeout): ?HealthCheckResult
    {
        // For now, just execute directly
        // In a more sophisticated implementation, you could use:
        // - Process forking
        // - Async execution
        // - Signal handling

        $startTime = time();
        $result = $healthCheck->check();
        $endTime = time();

        // Check if execution took longer than timeout
        if (($endTime - $startTime) > $timeout) {
            return null; // Indicate timeout
        }

        return $result;
    }
}
