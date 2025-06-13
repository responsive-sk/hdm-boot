<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\HealthChecks\Contracts;

use MvaBootstrap\SharedKernel\HealthChecks\ValueObjects\HealthCheckResult;

/**
 * Health Check Interface.
 *
 * Defines the contract for health check implementations.
 * Health checks are used to monitor the status of various system components.
 */
interface HealthCheckInterface
{
    /**
     * Get the name of this health check.
     */
    public function getName(): string;

    /**
     * Perform the health check.
     */
    public function check(): HealthCheckResult;

    /**
     * Get the timeout for this health check in seconds.
     */
    public function getTimeout(): int;

    /**
     * Check if this health check is critical.
     * Critical checks will cause the overall status to be unhealthy if they fail.
     */
    public function isCritical(): bool;

    /**
     * Get the category/group of this health check.
     */
    public function getCategory(): string;

    /**
     * Get tags associated with this health check.
     *
     * @return string[]
     */
    public function getTags(): array;
}
