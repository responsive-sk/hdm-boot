<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Modules;

/**
 * Module Interface.
 *
 * Defines the contract that all HDM Boot modules must implement.
 * This interface ensures consistent module behavior across the framework.
 */
interface ModuleInterface
{
    /**
     * Get the module name.
     *
     * @return string Unique module identifier
     */
    public function getName(): string;

    /**
     * Get the module version.
     *
     * @return string Semantic version (e.g., "1.0.0")
     */
    public function getVersion(): string;

    /**
     * Get module dependencies.
     *
     * @return array<string> List of module names this module depends on
     */
    public function getDependencies(): array;

    /**
     * Get public services provided by this module.
     *
     * @return array<string, string> Map of interface => implementation class
     */
    public function getPublicServices(): array;

    /**
     * Get events published by this module.
     *
     * @return array<string> List of event names this module publishes
     */
    public function getPublishedEvents(): array;

    /**
     * Get event subscriptions for this module.
     *
     * @return array<string, callable> Map of event name => handler
     */
    public function getEventSubscriptions(): array;

    /**
     * Initialize the module.
     *
     * This method is called during application bootstrap to set up
     * the module's services, event listeners, and configuration.
     *
     * @throws \Exception If initialization fails
     */
    public function initialize(): void;

    /**
     * Check if the module is initialized.
     *
     * @return bool True if module is initialized, false otherwise
     */
    public function isInitialized(): bool;
}
