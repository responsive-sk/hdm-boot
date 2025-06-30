<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\CQRS\Queries;

/**
 * Query Interface.
 *
 * Represents a query that reads system state.
 * Queries are read operations that don't modify data.
 */
interface QueryInterface
{
    /**
     * Get query identifier for logging and tracking.
     */
    public function getQueryId(): string;

    /**
     * Get query name for identification.
     */
    public function getQueryName(): string;

    /**
     * Get query parameters for logging.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array;
}
