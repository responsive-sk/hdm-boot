<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics;

use Psr\Log\LoggerInterface;

/**
 * Performance Monitor.
 *
 * Tracks application performance metrics and logs them for analysis.
 */
final class PerformanceMonitor
{
    /** @var array<string, float> */
    private array $timers = [];

    /** @var array<string, int> */
    private array $counters = [];

    /** @var array<string, mixed> */
    private array $metrics = [];

    public function __construct(
        private readonly LoggerInterface $performanceLogger
    ) {
    }

    /**
     * Start a timer.
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    /**
     * Stop a timer and return the duration.
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            throw new \InvalidArgumentException("Timer '{$name}' was not started");
        }

        $duration = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);

        $this->recordMetric("timer.{$name}", $duration);

        return $duration;
    }

    /**
     * Measure execution time of a callable.
     */
    public function measure(string $name, callable $callable): mixed
    {
        $this->startTimer($name);

        try {
            $result = $callable();

            return $result;
        } finally {
            $duration = $this->stopTimer($name);

            $this->performanceLogger->info('Performance measurement', [
                'metric'       => $name,
                'duration'     => $duration,
                'memory_usage' => memory_get_usage(true),
                'memory_peak'  => memory_get_peak_usage(true),
            ]);
        }
    }

    /**
     * Increment a counter.
     */
    public function incrementCounter(string $name, int $value = 1): void
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }

        $this->counters[$name] += $value;
        $this->recordMetric("counter.{$name}", $this->counters[$name]);
    }

    /**
     * Record a metric value.
     */
    public function recordMetric(string $name, mixed $value): void
    {
        $this->metrics[$name] = $value;

        $this->performanceLogger->info('Metric recorded', [
            'metric_name'  => $name,
            'metric_value' => $value,
            'timestamp'    => microtime(true),
        ]);
    }

    /**
     * Record memory usage.
     */
    public function recordMemoryUsage(string $context = 'general'): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $this->recordMetric("memory.usage.{$context}", $memoryUsage);
        $this->recordMetric("memory.peak.{$context}", $memoryPeak);

        $this->performanceLogger->info('Memory usage recorded', [
            'context'         => $context,
            'memory_usage'    => $memoryUsage,
            'memory_peak'     => $memoryPeak,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb'  => round($memoryPeak / 1024 / 1024, 2),
        ]);
    }

    /**
     * Record database query metrics.
     */
    public function recordDatabaseQuery(string $query, float $duration, bool $success = true): void
    {
        $this->incrementCounter('database.queries.total');

        if ($success) {
            $this->incrementCounter('database.queries.successful');
        } else {
            $this->incrementCounter('database.queries.failed');
        }

        $this->recordMetric('database.query.last_duration', $duration);

        // Log slow queries
        if ($duration > 1.0) { // Queries taking more than 1 second
            $this->incrementCounter('database.queries.slow');

            $this->performanceLogger->warning('Slow database query detected', [
                'query'    => substr($query, 0, 200), // Truncate long queries
                'duration' => $duration,
                'success'  => $success,
            ]);
        }

        $this->performanceLogger->info('Database query executed', [
            'duration'   => $duration,
            'success'    => $success,
            'query_type' => $this->getQueryType($query),
        ]);
    }

    /**
     * Record HTTP request metrics.
     */
    public function recordHttpRequest(
        string $method,
        string $path,
        int $statusCode,
        float $duration
    ): void {
        $this->incrementCounter('http.requests.total');
        $this->incrementCounter("http.requests.method.{$method}");
        $this->incrementCounter("http.requests.status.{$statusCode}");

        if ($statusCode >= 400) {
            $this->incrementCounter('http.requests.errors');
        }

        $this->recordMetric('http.request.last_duration', $duration);

        // Log slow requests
        if ($duration > 2.0) { // Requests taking more than 2 seconds
            $this->incrementCounter('http.requests.slow');

            $this->performanceLogger->warning('Slow HTTP request detected', [
                'method'      => $method,
                'path'        => $path,
                'status_code' => $statusCode,
                'duration'    => $duration,
            ]);
        }

        $this->performanceLogger->info('HTTP request completed', [
            'method'      => $method,
            'path'        => $path,
            'status_code' => $statusCode,
            'duration'    => $duration,
        ]);
    }

    /**
     * Get all current metrics.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        return [
            'counters' => $this->counters,
            'metrics'  => $this->metrics,
            'memory'   => [
                'current_usage'    => memory_get_usage(true),
                'peak_usage'       => memory_get_peak_usage(true),
                'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_usage_mb'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Reset all metrics.
     */
    public function reset(): void
    {
        $this->timers = [];
        $this->counters = [];
        $this->metrics = [];

        $this->performanceLogger->info('Performance metrics reset');
    }

    /**
     * Get query type from SQL query.
     */
    private function getQueryType(string $query): string
    {
        $query = trim(strtoupper($query));

        if (str_starts_with($query, 'SELECT')) {
            return 'SELECT';
        } elseif (str_starts_with($query, 'INSERT')) {
            return 'INSERT';
        } elseif (str_starts_with($query, 'UPDATE')) {
            return 'UPDATE';
        } elseif (str_starts_with($query, 'DELETE')) {
            return 'DELETE';
        } elseif (str_starts_with($query, 'CREATE')) {
            return 'CREATE';
        } elseif (str_starts_with($query, 'DROP')) {
            return 'DROP';
        } elseif (str_starts_with($query, 'ALTER')) {
            return 'ALTER';
        }

        return 'OTHER';
    }
}
