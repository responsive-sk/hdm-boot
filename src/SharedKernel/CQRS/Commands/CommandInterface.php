<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\CQRS\Commands;

/**
 * Command Interface.
 *
 * Represents a command that changes system state.
 * Commands are write operations that modify data.
 */
interface CommandInterface
{
    /**
     * Get command identifier for logging and tracking.
     */
    public function getCommandId(): string;

    /**
     * Get command name for identification.
     */
    public function getCommandName(): string;

    /**
     * Get command payload for logging (without sensitive data).
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array;
}
