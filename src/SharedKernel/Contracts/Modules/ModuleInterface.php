<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Contracts\Modules;

/**
 * Module Interface
 *
 * Defines the contract that all modules must implement.
 * Provides standardized module lifecycle and dependency management.
 */
interface ModuleInterface
{
    /**
     * Get module name.
     */
    public function getName(): string;

    /**
     * Get module version.
     */
    public function getVersion(): string;

    /**
     * Get module description.
     */
    public function getDescription(): string;

    /**
     * Get module dependencies.
     *
     * @return array<string>
     */
    public function getDependencies(): array;

    /**
     * Check if module is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Get module priority (lower number = higher priority).
     */
    public function getPriority(): int;

    /**
     * Get services that this module provides to other modules.
     *
     * @return array<string>
     */
    public function getPublicServices(): array;

    /**
     * Get events that this module publishes.
     *
     * @return array<string>
     */
    public function getPublishedEvents(): array;

    /**
     * Get events that this module subscribes to.
     *
     * @return array<string, string>
     */
    public function getEventSubscriptions(): array;

    /**
     * Initialize the module.
     */
    public function initialize(): void;

    /**
     * Boot the module (called after all modules are initialized).
     */
    public function boot(): void;

    /**
     * Shutdown the module.
     */
    public function shutdown(): void;
}
