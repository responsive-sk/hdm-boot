<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\HealthChecks\ValueObjects;

use JsonSerializable;

/**
 * Health Check Report.
 *
 * Aggregated report of multiple health check results.
 */
final readonly class HealthCheckReport implements JsonSerializable
{
    /**
     * @param HealthCheckResult[] $results
     */
    public function __construct(
        public HealthStatus $overallStatus,
        public array $results,
        public \DateTimeImmutable $timestamp,
        public float $totalDuration,
        public int $totalChecks,
        public int $healthyChecks,
        public int $unhealthyChecks,
        public int $degradedChecks
    ) {
    }

    /**
     * Create report from health check results.
     *
     * @param HealthCheckResult[] $results
     */
    public static function fromResults(array $results): self
    {
        $statuses = array_map(fn(HealthCheckResult $result) => $result->status, $results);
        $overallStatus = HealthStatus::getWorst($statuses);

        $totalDuration = array_sum(array_map(fn(HealthCheckResult $result) => $result->duration ?? 0, $results));

        $healthyCount = count(array_filter($results, fn(HealthCheckResult $r) => $r->isHealthy()));
        $unhealthyCount = count(array_filter($results, fn(HealthCheckResult $r) => $r->isUnhealthy()));
        $degradedCount = count(array_filter($results, fn(HealthCheckResult $r) => $r->isDegraded()));

        return new self(
            overallStatus: $overallStatus,
            results: $results,
            timestamp: new \DateTimeImmutable(),
            totalDuration: $totalDuration,
            totalChecks: count($results),
            healthyChecks: $healthyCount,
            unhealthyChecks: $unhealthyCount,
            degradedChecks: $degradedCount
        );
    }

    /**
     * Get results by status.
     *
     * @return HealthCheckResult[]
     */
    public function getResultsByStatus(HealthStatus $status): array
    {
        return array_filter($this->results, fn(HealthCheckResult $result) => $result->status === $status);
    }

    /**
     * Get results by category.
     *
     * @return HealthCheckResult[]
     */
    public function getResultsByCategory(string $category): array
    {
        return array_filter($this->results, fn(HealthCheckResult $result) => $result->category === $category);
    }

    /**
     * Get failed results (unhealthy + degraded).
     *
     * @return HealthCheckResult[]
     */
    public function getFailedResults(): array
    {
        return array_filter($this->results, fn(HealthCheckResult $result) => $result->status->isProblematic());
    }

    /**
     * Check if overall status is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->overallStatus === HealthStatus::HEALTHY;
    }

    /**
     * Check if there are any problems.
     */
    public function hasProblems(): bool
    {
        return $this->overallStatus->isProblematic();
    }

    /**
     * Get HTTP status code for this report.
     */
    public function getHttpStatusCode(): int
    {
        return $this->overallStatus->getHttpStatusCode();
    }

    /**
     * Get summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'overall_status' => $this->overallStatus->value,
            'total_checks' => $this->totalChecks,
            'healthy_checks' => $this->healthyChecks,
            'unhealthy_checks' => $this->unhealthyChecks,
            'degraded_checks' => $this->degradedChecks,
            'total_duration' => $this->totalDuration,
            'average_duration' => $this->totalChecks > 0 ? $this->totalDuration / $this->totalChecks : 0,
            'timestamp' => $this->timestamp->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->getSummary(),
            'results' => array_map(fn(HealthCheckResult $result) => $result->toArray(), $this->results),
        ];
    }

    /**
     * JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
