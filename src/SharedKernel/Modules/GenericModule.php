<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Modules;

use MvaBootstrap\SharedKernel\Contracts\ModuleInterface;

/**
 * Generic Module Implementation.
 *
 * Default implementation of ModuleInterface that loads configuration
 * from module config.php files.
 */
final class GenericModule implements ModuleInterface
{
    private bool $initialized = false;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly array $config,
        private readonly ?ModuleManifest $manifest = null
    ) {
    }

    /**
     * Get module name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module version.
     */
    public function getVersion(): string
    {
        return $this->manifest?->getVersion() ?? $this->config['version'] ?? '1.0.0';
    }

    /**
     * Get module description.
     */
    public function getDescription(): string
    {
        return $this->manifest?->getDescription() ?? $this->config['description'] ?? "Module: {$this->name}";
    }

    /**
     * Get module configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get module dependencies.
     */
    public function getDependencies(): array
    {
        return $this->manifest?->getDependencies() ?? $this->config['dependencies'] ?? [];
    }

    /**
     * Get module manifest.
     */
    public function getManifest(): ?ModuleManifest
    {
        return $this->manifest;
    }

    /**
     * Get module service definitions for DI container.
     */
    public function getServiceDefinitions(): array
    {
        return $this->config['services'] ?? $this->config;
    }

    /**
     * Get module settings.
     */
    public function getSettings(): array
    {
        return $this->config['settings'] ?? [];
    }

    /**
     * Get module public services.
     */
    public function getPublicServices(): array
    {
        return $this->config['public_services'] ?? [];
    }

    /**
     * Get module published events.
     */
    public function getPublishedEvents(): array
    {
        return $this->config['published_events'] ?? [];
    }

    /**
     * Get module event subscriptions.
     */
    public function getEventSubscriptions(): array
    {
        return $this->config['event_subscriptions'] ?? [];
    }

    /**
     * Get module API endpoints.
     */
    public function getApiEndpoints(): array
    {
        return $this->config['api_endpoints'] ?? [];
    }

    /**
     * Get module middleware.
     */
    public function getMiddleware(): array
    {
        return $this->config['middleware'] ?? [];
    }

    /**
     * Get module permissions.
     */
    public function getPermissions(): array
    {
        return $this->config['permissions'] ?? [];
    }

    /**
     * Get module database tables.
     */
    public function getDatabaseTables(): array
    {
        return $this->config['database_tables'] ?? [];
    }

    /**
     * Get module status information.
     */
    public function getStatus(): array
    {
        return $this->config['status'] ?? [
            'implemented' => [],
            'planned'     => [],
        ];
    }

    /**
     * Initialize the module.
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Run initialization logic if defined
        if (isset($this->config['initialize']) && is_callable($this->config['initialize'])) {
            $this->config['initialize']();
        }

        $this->initialized = true;
    }

    /**
     * Check if module is initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Validate module configuration.
     */
    public function validateConfig(): array
    {
        $errors = [];

        // Validate required fields
        if (empty($this->name)) {
            $errors[] = 'Module name is required';
        }

        // Validate version format
        $version = $this->getVersion();
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $errors[] = "Invalid version format: '{$version}'. Expected format: x.y.z";
        }

        // Validate dependencies exist
        foreach ($this->getDependencies() as $dependency) {
            if (empty($dependency) || !is_string($dependency)) {
                $errors[] = "Invalid dependency: '{$dependency}'";
            }
        }

        // Validate service definitions
        $services = $this->getServiceDefinitions();
        if (!is_array($services)) {
            $errors[] = 'Service definitions must be an array';
        }

        return $errors;
    }

    /**
     * Get module health status.
     */
    public function getHealthStatus(): array
    {
        $status = [
            'name'               => $this->name,
            'version'            => $this->getVersion(),
            'initialized'        => $this->initialized,
            'path'               => $this->path,
            'config_valid'       => empty($this->validateConfig()),
            'dependencies_count' => count($this->getDependencies()),
            'services_count'     => count($this->getServiceDefinitions()),
            'events_published'   => count($this->getPublishedEvents()),
            'events_subscribed'  => count($this->getEventSubscriptions()),
        ];

        // Add custom health checks if defined
        if (isset($this->config['health_check']) && is_callable($this->config['health_check'])) {
            try {
                $customHealth = $this->config['health_check']();
                if (is_array($customHealth)) {
                    $status = array_merge($status, $customHealth);
                }
            } catch (\Throwable $e) {
                $status['health_check_error'] = $e->getMessage();
            }
        }

        return $status;
    }

    /**
     * Get module path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Check if module has specific configuration key.
     */
    public function hasConfig(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Get specific configuration value.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
