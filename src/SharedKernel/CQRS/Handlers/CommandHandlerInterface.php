<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\CQRS\Handlers;

use MvaBootstrap\SharedKernel\CQRS\Commands\CommandInterface;

/**
 * Command Handler Interface.
 *
 * Handles commands that change system state.
 */
interface CommandHandlerInterface
{
    /**
     * Handle the command.
     */
    public function handle(CommandInterface $command): void;

    /**
     * Get the command class this handler supports.
     */
    public function getSupportedCommandClass(): string;
}
