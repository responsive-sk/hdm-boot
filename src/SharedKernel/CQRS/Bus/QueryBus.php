<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\CQRS\Bus;

use HdmBoot\SharedKernel\CQRS\Handlers\QueryHandlerInterface;
use HdmBoot\SharedKernel\CQRS\Queries\QueryInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Query Bus.
 *
 * Dispatches queries to their appropriate handlers.
 */
final class QueryBus
{
    /** @var array<string, string> */
    private array $handlerMap = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register a query handler.
     */
    public function registerHandler(string $queryClass, string $handlerClass): void
    {
        $this->handlerMap[$queryClass] = $handlerClass;

        $this->logger->debug('Query handler registered', [
            'query_class'   => $queryClass,
            'handler_class' => $handlerClass,
        ]);
    }

    /**
     * Dispatch a query to its handler and return result.
     */
    public function dispatch(QueryInterface $query): mixed
    {
        $queryClass = get_class($query);

        $this->logger->info('Dispatching query', [
            'query_class' => $queryClass,
            'query_id'    => $query->getQueryId(),
            'query_name'  => $query->getQueryName(),
        ]);

        if (!isset($this->handlerMap[$queryClass])) {
            throw new InvalidArgumentException(
                "No handler registered for query: {$queryClass}"
            );
        }

        $handlerClass = $this->handlerMap[$queryClass];

        try {
            /** @var QueryHandlerInterface $handler */
            $handler = $this->container->get($handlerClass);

            // @phpstan-ignore-next-line instanceof.alwaysTrue
            if (!$handler instanceof QueryHandlerInterface) {
                throw new InvalidArgumentException(
                    "Handler {$handlerClass} must implement QueryHandlerInterface"
                );
            }

            $result = $handler->handle($query);

            $this->logger->info('Query handled successfully', [
                'query_class'   => $queryClass,
                'query_id'      => $query->getQueryId(),
                'handler_class' => $handlerClass,
                'result_type'   => gettype($result),
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Query handling failed', [
                'query_class'   => $queryClass,
                'query_id'      => $query->getQueryId(),
                'handler_class' => $handlerClass,
                'error'         => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all registered handlers.
     *
     * @return array<string, string>
     */
    public function getHandlers(): array
    {
        return $this->handlerMap;
    }

    /**
     * Check if handler is registered for query.
     */
    public function hasHandler(string $queryClass): bool
    {
        return isset($this->handlerMap[$queryClass]);
    }
}
