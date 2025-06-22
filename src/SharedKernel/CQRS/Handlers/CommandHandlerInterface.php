<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\CQRS\Handlers;

use HdmBoot\SharedKernel\CQRS\Commands\CommandInterface;

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
