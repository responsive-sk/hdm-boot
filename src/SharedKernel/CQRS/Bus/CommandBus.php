<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\CQRS\Bus;

use HdmBoot\SharedKernel\CQRS\Commands\CommandInterface;
use HdmBoot\SharedKernel\CQRS\Handlers\CommandHandlerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Command Bus.
 *
 * Dispatches commands to their appropriate handlers.
 */
final class CommandBus
{
    /** @var array<string, string> */
    private array $handlerMap = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Register a command handler.
     */
    public function registerHandler(string $commandClass, string $handlerClass): void
    {
        $this->handlerMap[$commandClass] = $handlerClass;

        $this->logger->debug('Command handler registered', [
            'command_class' => $commandClass,
            'handler_class' => $handlerClass,
        ]);
    }

    /**
     * Dispatch a command to its handler.
     */
    public function dispatch(CommandInterface $command): void
    {
        $commandClass = get_class($command);

        $this->logger->info('Dispatching command', [
            'command_class' => $commandClass,
            'command_id'    => $command->getCommandId(),
            'command_name'  => $command->getCommandName(),
        ]);

        if (!isset($this->handlerMap[$commandClass])) {
            throw new InvalidArgumentException(
                "No handler registered for command: {$commandClass}"
            );
        }

        $handlerClass = $this->handlerMap[$commandClass];

        try {
            /** @var CommandHandlerInterface $handler */
            $handler = $this->container->get($handlerClass);

            if (!$handler instanceof CommandHandlerInterface) {
                throw new InvalidArgumentException(
                    "Handler {$handlerClass} must implement CommandHandlerInterface"
                );
            }

            $handler->handle($command);

            $this->logger->info('Command handled successfully', [
                'command_class' => $commandClass,
                'command_id'    => $command->getCommandId(),
                'handler_class' => $handlerClass,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Command handling failed', [
                'command_class' => $commandClass,
                'command_id'    => $command->getCommandId(),
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
     * Check if handler is registered for command.
     */
    public function hasHandler(string $commandClass): bool
    {
        return isset($this->handlerMap[$commandClass]);
    }
}
