<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security;

use MvaBootstrap\Modules\Core\Security\Contracts\Events\SecurityModuleEvents;
use MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface;
use MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthorizationServiceInterface;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use MvaBootstrap\Modules\Core\Security\Services\AuthorizationService;
use MvaBootstrap\SharedKernel\Contracts\Modules\ModuleInterface;
use Psr\Log\LoggerInterface;

/**
 * Security Module.
 *
 * Manages security-related functionality with proper module isolation.
 * Depends on User module for user data but communicates through contracts.
 */
final class SecurityModule implements ModuleInterface
{
    private bool $initialized = false;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function getName(): string
    {
        return 'Security';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDependencies(): array
    {
        return [
            'User', // Security module depends on User module
        ];
    }

    public function getPublicServices(): array
    {
        return [
            AuthenticationServiceInterface::class => AuthenticationService::class,
            AuthorizationServiceInterface::class  => AuthorizationService::class,
        ];
    }

    public function getPublishedEvents(): array
    {
        return SecurityModuleEvents::getAllEvents();
    }

    public function getEventSubscriptions(): array
    {
        return [
            // Security module can subscribe to User module events
            'user.registered'     => [$this, 'handleUserRegistered'],
            'user.status_changed' => [$this, 'handleUserStatusChanged'],
        ];
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->logger->info('Initializing Security module', [
            'module_name'    => $this->getName(),
            'module_version' => $this->getVersion(),
        ]);

        // Module-specific initialization logic
        $this->setupEventListeners();
        $this->validateConfiguration();
        $this->initializeSecurityPolicies();

        $this->initialized = true;

        $this->logger->info('Security module initialized successfully');
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
    }

    /**
     * Validate module configuration.
     */
    private function validateConfiguration(): void
    {
        // Validate security configuration
        // Check JWT secret, session settings, etc.
    }

    /**
     * Initialize security policies.
     */
    private function initializeSecurityPolicies(): void
    {
        // Setup rate limiting, CSRF protection, etc.
    }

    /**
     * Handle user registered event from User module.
     *
     * @param object $event
     */
    public function handleUserRegistered(object $event): void
    {
        $this->logger->info('Handling user registered event in Security module', [
            'event' => get_class($event),
        ]);

        // Example: Setup default security settings for new user
        // Send welcome email with security tips, etc.
    }

    /**
     * Handle user status changed event from User module.
     *
     * @param object $event
     */
    public function handleUserStatusChanged(object $event): void
    {
        $this->logger->info('Handling user status changed event in Security module', [
            'event' => get_class($event),
        ]);

        // Example: Revoke tokens if user is suspended
        // Log security event, etc.
    }
}
