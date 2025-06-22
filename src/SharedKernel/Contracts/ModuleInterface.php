<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Contracts;

/**
 * Module Interface.
 *
 * Defines the contract that all modules must implement for proper
 * module isolation, configuration management, and communication.
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
     * Get module configuration.
     *
     * @return array<string, mixed> Complete module configuration
     */
    public function getConfig(): array;

    /**
     * Get module dependencies.
     *
     * @return array<string> Array of module names this module depends on
     */
    public function getDependencies(): array;

    /**
     * Get module service definitions for DI container.
     *
     * @return array<string, mixed> Array of service definitions
     */
    public function getServiceDefinitions(): array;

    /**
     * Get module settings.
     *
     * @return array<string, mixed> Module-specific settings
     */
    public function getSettings(): array;

    /**
     * Get module public services.
     *
     * @return array<string, string> Array of service interface => implementation class
     */
    public function getPublicServices(): array;

    /**
     * Get module published events.
     *
     * @return array<string, string> Array of event name => description
     */
    public function getPublishedEvents(): array;

    /**
     * Get module event subscriptions.
     *
     * @return array<string, callable> Array of event name => listener callable
     */
    public function getEventSubscriptions(): array;

    /**
     * Get module API endpoints.
     *
     * @return array<string, string> Array of endpoint => description
     */
    public function getApiEndpoints(): array;

    /**
     * Get module middleware.
     *
     * @return array<string, string> Array of middleware class => description
     */
    public function getMiddleware(): array;

    /**
     * Get module permissions.
     *
     * @return array<string, string> Array of permission => description
     */
    public function getPermissions(): array;

    /**
     * Get module database tables.
     *
     * @return array<string> Array of table names managed by this module
     */
    public function getDatabaseTables(): array;

    /**
     * Get module status information.
     *
     * @return array<string, array<string>> Status with implemented and planned features
     */
    public function getStatus(): array;

    /**
     * Initialize the module.
     */
    public function initialize(): void;

    /**
     * Check if module is initialized.
     */
    public function isInitialized(): bool;

    /**
     * Validate module configuration.
     *
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validateConfig(): array;

    /**
     * Get module health status.
     *
     * @return array<string, mixed> Health status information
     */
    public function getHealthStatus(): array;
}
