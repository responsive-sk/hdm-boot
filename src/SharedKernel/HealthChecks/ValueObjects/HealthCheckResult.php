<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\HealthChecks\ValueObjects;

use JsonSerializable;

/**
 * Health Check Result.
 *
 * Represents the result of a health check execution.
 */
final readonly class HealthCheckResult implements JsonSerializable
{
    public function __construct(
        public string $name,
        public HealthStatus $status,
        public ?string $message = null,
        public array $data = [],
        public ?float $duration = null,
        public ?\DateTimeImmutable $timestamp = null,
        public ?string $category = null,
        public array $tags = []
    ) {
    }

    /**
     * Create a healthy result.
     */
    public static function healthy(
        string $name,
        ?string $message = null,
        array $data = [],
        ?float $duration = null,
        ?string $category = null,
        array $tags = []
    ): self {
        return new self(
            name: $name,
            status: HealthStatus::HEALTHY,
            message: $message ?? 'OK',
            data: $data,
            duration: $duration,
            timestamp: new \DateTimeImmutable(),
            category: $category,
            tags: $tags
        );
    }

    /**
     * Create an unhealthy result.
     */
    public static function unhealthy(
        string $name,
        string $message,
        array $data = [],
        ?float $duration = null,
        ?string $category = null,
        array $tags = []
    ): self {
        return new self(
            name: $name,
            status: HealthStatus::UNHEALTHY,
            message: $message,
            data: $data,
            duration: $duration,
            timestamp: new \DateTimeImmutable(),
            category: $category,
            tags: $tags
        );
    }

    /**
     * Create a degraded result.
     */
    public static function degraded(
        string $name,
        string $message,
        array $data = [],
        ?float $duration = null,
        ?string $category = null,
        array $tags = []
    ): self {
        return new self(
            name: $name,
            status: HealthStatus::DEGRADED,
            message: $message,
            data: $data,
            duration: $duration,
            timestamp: new \DateTimeImmutable(),
            category: $category,
            tags: $tags
        );
    }

    /**
     * Check if the result is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->status === HealthStatus::HEALTHY;
    }

    /**
     * Check if the result is unhealthy.
     */
    public function isUnhealthy(): bool
    {
        return $this->status === HealthStatus::UNHEALTHY;
    }

    /**
     * Check if the result is degraded.
     */
    public function isDegraded(): bool
    {
        return $this->status === HealthStatus::DEGRADED;
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'status'    => $this->status->value,
            'message'   => $this->message,
            'data'      => $this->data,
            'duration'  => $this->duration,
            'timestamp' => $this->timestamp?->format('Y-m-d\TH:i:s.u\Z'),
            'category'  => $this->category,
            'tags'      => $this->tags,
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
