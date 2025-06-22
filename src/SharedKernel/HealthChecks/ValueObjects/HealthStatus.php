<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\HealthChecks\ValueObjects;

/**
 * Health Status Enum.
 *
 * Represents the possible health check statuses.
 */
enum HealthStatus: string
{
    case HEALTHY = 'healthy';
    case UNHEALTHY = 'unhealthy';
    case DEGRADED = 'degraded';

    /**
     * Get HTTP status code for this health status.
     */
    public function getHttpStatusCode(): int
    {
        return match ($this) {
            self::HEALTHY   => 200,
            self::DEGRADED  => 200, // Still OK but with warnings
            self::UNHEALTHY => 503, // Service Unavailable
        };
    }

    /**
     * Check if this status indicates a problem.
     */
    public function isProblematic(): bool
    {
        return $this === self::UNHEALTHY || $this === self::DEGRADED;
    }

    /**
     * Get the worst status from a list of statuses.
     *
     * @param array<HealthStatus> $statuses
     */
    public static function getWorst(array $statuses): self
    {
        if (in_array(self::UNHEALTHY, $statuses, true)) {
            return self::UNHEALTHY;
        }

        if (in_array(self::DEGRADED, $statuses, true)) {
            return self::DEGRADED;
        }

        return self::HEALTHY;
    }

    /**
     * Get color representation for UI.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::HEALTHY   => 'green',
            self::DEGRADED  => 'yellow',
            self::UNHEALTHY => 'red',
        };
    }

    /**
     * Get icon representation for UI.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::HEALTHY   => '✅',
            self::DEGRADED  => '⚠️',
            self::UNHEALTHY => '❌',
        };
    }
}
