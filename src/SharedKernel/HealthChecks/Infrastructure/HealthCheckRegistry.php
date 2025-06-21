<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\HealthChecks\Infrastructure;

use MvaBootstrap\SharedKernel\HealthChecks\Contracts\HealthCheckInterface;
use MvaBootstrap\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult;
use MvaBootstrap\SharedKernel\HealthChecks\ValueObjects\HealthStatus;
use Psr\Log\LoggerInterface;

/**
 * Health Check Registry.
 *
 * Central registry for managing and executing health checks.
 * Supports registration, categorization, and batch execution.
 */
final class HealthCheckRegistry
{
    /**
     * @var array<string, HealthCheckInterface>
     */
    private array $healthChecks = [];

    /**
     * @var array<string, string[]>
     */
    private array $categories = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register a health check.
     */
    public function register(HealthCheckInterface $healthCheck): void
    {
        $name = $healthCheck->getName();

        if (isset($this->healthChecks[$name])) {
            $this->logger->warning('Health check already registered, overwriting', [
                'name' => $name,
            ]);
        }

        $this->healthChecks[$name] = $healthCheck;

        // Add to category
        $category = $healthCheck->getCategory();
        if (!isset($this->categories[$category])) {
            $this->categories[$category] = [];
        }
        $this->categories[$category][] = $name;

        $this->logger->debug('Health check registered', [
            'name' => $name,
            'category' => $category,
            'critical' => $healthCheck->isCritical(),
            'timeout' => $healthCheck->getTimeout(),
        ]);
    }

    /**
     * Unregister a health check.
     */
    public function unregister(string $name): void
    {
        if (!isset($this->healthChecks[$name])) {
            return;
        }

        $healthCheck = $this->healthChecks[$name];
        $category = $healthCheck->getCategory();

        unset($this->healthChecks[$name]);

        // Remove from category
        if (isset($this->categories[$category])) {
            $this->categories[$category] = array_filter(
                $this->categories[$category],
                fn($checkName) => $checkName !== $name
            );

            if (empty($this->categories[$category])) {
                unset($this->categories[$category]);
            }
        }

        $this->logger->debug('Health check unregistered', ['name' => $name]);
    }

    /**
     * Get a specific health check.
     */
    public function get(string $name): ?HealthCheckInterface
    {
        return $this->healthChecks[$name] ?? null;
    }

    /**
     * Get all registered health checks.
     *
     * @return array<string, HealthCheckInterface>
     */
    public function getAll(): array
    {
        return $this->healthChecks;
    }

    /**
     * Get health checks by category.
     *
     * @return HealthCheckInterface[]
     */
    public function getByCategory(string $category): array
    {
        $names = $this->categories[$category] ?? [];
        return array_map(fn($name) => $this->healthChecks[$name], $names);
    }

    /**
     * Get health checks by tags.
     *
     * @param array<string> $tags
     * @return HealthCheckInterface[]
     */
    public function getByTags(array $tags): array
    {
        return array_filter($this->healthChecks, function (HealthCheckInterface $healthCheck) use ($tags) {
            $checkTags = $healthCheck->getTags();
            return !empty(array_intersect($tags, $checkTags));
        });
    }

    /**
     * Get critical health checks.
     *
     * @return HealthCheckInterface[]
     */
    public function getCritical(): array
    {
        return array_filter($this->healthChecks, fn(HealthCheckInterface $check) => $check->isCritical());
    }

    /**
     * Get non-critical health checks.
     *
     * @return HealthCheckInterface[]
     */
    public function getNonCritical(): array
    {
        return array_filter($this->healthChecks, fn(HealthCheckInterface $check) => !$check->isCritical());
    }

    /**
     * Execute a specific health check.
     */
    public function execute(string $name): ?HealthCheckResult
    {
        $healthCheck = $this->get($name);
        if (!$healthCheck) {
            return null;
        }

        return $this->executeHealthCheck($healthCheck);
    }

    /**
     * Execute all health checks.
     *
     * @return HealthCheckResult[]
     */
    public function executeAll(): array
    {
        $results = [];

        foreach ($this->healthChecks as $name => $healthCheck) {
            $results[$name] = $this->executeHealthCheck($healthCheck);
        }

        return $results;
    }

    /**
     * Execute health checks by category.
     *
     * @return HealthCheckResult[]
     */
    public function executeByCategory(string $category): array
    {
        $healthChecks = $this->getByCategory($category);
        $results = [];

        foreach ($healthChecks as $healthCheck) {
            $results[$healthCheck->getName()] = $this->executeHealthCheck($healthCheck);
        }

        return $results;
    }

    /**
     * Execute only critical health checks.
     *
     * @return HealthCheckResult[]
     */
    public function executeCritical(): array
    {
        $criticalChecks = $this->getCritical();
        $results = [];

        foreach ($criticalChecks as $healthCheck) {
            $results[$healthCheck->getName()] = $this->executeHealthCheck($healthCheck);
        }

        return $results;
    }

    /**
     * Get overall health status.
     */
    public function getOverallStatus(): HealthStatus
    {
        $results = $this->executeAll();
        $statuses = array_map(fn(HealthCheckResult $result) => $result->status, $results);

        return HealthStatus::getWorst($statuses);
    }

    /**
     * Get available categories.
     *
     * @return string[]
     */
    public function getCategories(): array
    {
        return array_keys($this->categories);
    }

    /**
     * Get health check count.
     */
    public function getCount(): int
    {
        return count($this->healthChecks);
    }

    /**
     * Check if a health check is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->healthChecks[$name]);
    }

    /**
     * Execute a single health check with timeout and error handling.
     */
    private function executeHealthCheck(HealthCheckInterface $healthCheck): HealthCheckResult
    {
        $name = $healthCheck->getName();
        $startTime = microtime(true);

        try {
            $this->logger->debug('Executing health check', ['name' => $name]);

            // Execute with timeout (simplified - real implementation would use process timeout)
            $result = $healthCheck->check();

            $duration = microtime(true) - $startTime;

            $this->logger->debug('Health check completed', [
                'name' => $name,
                'status' => $result->status->value,
                'duration' => $duration,
            ]);

            return new HealthCheckResult(
                name: $result->name,
                status: $result->status,
                message: $result->message,
                data: $result->data,
                duration: $duration,
                timestamp: new \DateTimeImmutable(),
                category: $healthCheck->getCategory(),
                tags: $healthCheck->getTags()
            );
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Health check failed with exception', [
                'name' => $name,
                'error' => $e->getMessage(),
                'duration' => $duration,
            ]);

            return HealthCheckResult::unhealthy(
                name: $name,
                message: "Health check failed: {$e->getMessage()}",
                data: [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                duration: $duration,
                category: $healthCheck->getCategory(),
                tags: $healthCheck->getTags()
            );
        }
    }
}
