<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\CQRS\Handlers;

use MvaBootstrap\SharedKernel\CQRS\Queries\QueryInterface;

/**
 * Query Handler Interface.
 *
 * Handles queries that read system state.
 */
interface QueryHandlerInterface
{
    /**
     * Handle the query and return result.
     *
     * @return mixed
     */
    public function handle(QueryInterface $query): mixed;

    /**
     * Get the query class this handler supports.
     */
    public function getSupportedQueryClass(): string;
}
