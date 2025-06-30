<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\User;

use HdmBoot\Modules\Core\User\Contracts\Events\UserModuleEvents;
use HdmBoot\Modules\Core\User\Contracts\Services\UserServiceInterface;
use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\SharedKernel\Modules\ModuleInterface;
use Psr\Log\LoggerInterface;

/**
 * User Module.
 *
 * Manages user-related functionality with proper module isolation.
 * Publishes events and provides public services through contracts.
 */
final class UserModule implements ModuleInterface
{
    private bool $initialized = false;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'User';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        // User module has no dependencies on other modules
        return [];
    }

    public function getPublicServices(): array
    {
        return [
            UserServiceInterface::class => UserService::class,
        ];
    }

    public function getPublishedEvents(): array
    {
        return UserModuleEvents::getAllEvents();
    }

    public function getEventSubscriptions(): array
    {
        return [
            // User module can subscribe to events from other modules
            // Example: 'security.login_successful' => [$this, 'handleLoginSuccess']
        ];
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->logger->info('Initializing User module', [
            'module_name'    => $this->getName(),
            'module_version' => $this->getVersion(),
        ]);

        // Module-specific initialization logic
        $this->setupEventListeners();
        $this->validateConfiguration();

        $this->initialized = true;

        $this->logger->info('User module initialized successfully');
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Setup event listeners for this module.
     */
    private function setupEventListeners(): void
    {
        // Register event listeners for events this module subscribes to
        // This will be handled by the event dispatcher during module initialization
    }

    /**
     * Validate module configuration.
     */
    private function validateConfiguration(): void
    {
        // Validate that required services and dependencies are available
        // Throw exception if configuration is invalid
    }

    /**
     * Handle login success event from Security module.
     *
     * @param object $event
     */
    public function handleLoginSuccess(object $event): void
    {
        // Example event handler - update user last login time
        $this->logger->info('Handling login success event in User module', [
            'event' => get_class($event),
        ]);
    }
}
