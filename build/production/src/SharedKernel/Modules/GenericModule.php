<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Modules;

use HdmBoot\SharedKernel\Contracts\ModuleInterface;

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
        $version = $this->manifest?->getVersion() ?? ($this->config['version'] ?? '1.0.0');

        return is_string($version) ? $version : '1.0.0';
    }

    /**
     * Get module description.
     */
    public function getDescription(): string
    {
        $description = $this->manifest?->getDescription() ?? ($this->config['description'] ?? "Module: {$this->name}");

        return is_string($description) ? $description : "Module: {$this->name}";
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
        $dependencies = $this->manifest?->getDependencies() ?? ($this->config['dependencies'] ?? []);
        if (!is_array($dependencies)) {
            return [];
        }

        // Ensure all dependencies are strings
        /** @var array<string> $typedDependencies */
        $typedDependencies = array_filter($dependencies, 'is_string');

        return array_values($typedDependencies);
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
        $services = $this->config['services'] ?? [];
        if (!is_array($services)) {
            return [];
        }

        /** @var array<string, mixed> $typedServices */
        $typedServices = $services;

        return $typedServices;
    }

    /**
     * Get module settings.
     */
    public function getSettings(): array
    {
        $settings = $this->config['settings'] ?? [];
        if (!is_array($settings)) {
            return [];
        }

        /** @var array<string, mixed> $typedSettings */
        $typedSettings = $settings;

        return $typedSettings;
    }

    /**
     * Get module public services.
     */
    public function getPublicServices(): array
    {
        $services = $this->config['public_services'] ?? [];
        if (!is_array($services)) {
            return [];
        }

        // Ensure proper typing for public services (interface => implementation)
        /** @var array<string, string> $typedServices */
        $typedServices = [];
        foreach ($services as $interface => $implementation) {
            if (is_string($interface) && is_string($implementation)) {
                $typedServices[$interface] = $implementation;
            }
        }

        return $typedServices;
    }

    /**
     * Get module published events.
     */
    public function getPublishedEvents(): array
    {
        $events = $this->config['published_events'] ?? [];
        if (!is_array($events)) {
            return [];
        }

        // Ensure proper typing for published events (event name => description)
        /** @var array<string, string> $typedEvents */
        $typedEvents = [];
        foreach ($events as $eventName => $description) {
            if (is_string($eventName) && is_string($description)) {
                $typedEvents[$eventName] = $description;
            }
        }

        return $typedEvents;
    }

    /**
     * Get module event subscriptions.
     */
    public function getEventSubscriptions(): array
    {
        $subscriptions = $this->config['event_subscriptions'] ?? [];
        if (!is_array($subscriptions)) {
            return [];
        }

        // Ensure proper typing for event subscriptions (event name => callable)
        /** @var array<string, callable(): mixed> $typedSubscriptions */
        $typedSubscriptions = [];
        foreach ($subscriptions as $eventName => $callable) {
            if (is_string($eventName) && is_callable($callable)) {
                $typedSubscriptions[$eventName] = $callable;
            }
        }

        return $typedSubscriptions;
    }

    /**
     * Get module API endpoints.
     */
    public function getApiEndpoints(): array
    {
        $endpoints = $this->config['api_endpoints'] ?? [];
        if (!is_array($endpoints)) {
            return [];
        }

        // Ensure proper typing for API endpoints (endpoint => description)
        /** @var array<string, string> $typedEndpoints */
        $typedEndpoints = [];
        foreach ($endpoints as $endpoint => $description) {
            if (is_string($endpoint) && is_string($description)) {
                $typedEndpoints[$endpoint] = $description;
            }
        }

        return $typedEndpoints;
    }

    /**
     * Get module middleware.
     */
    public function getMiddleware(): array
    {
        $middleware = $this->config['middleware'] ?? [];
        if (!is_array($middleware)) {
            return [];
        }

        // Ensure proper typing for middleware (class => description)
        /** @var array<string, string> $typedMiddleware */
        $typedMiddleware = [];
        foreach ($middleware as $class => $description) {
            if (is_string($class) && is_string($description)) {
                $typedMiddleware[$class] = $description;
            }
        }

        return $typedMiddleware;
    }

    /**
     * Get module permissions.
     */
    public function getPermissions(): array
    {
        $permissions = $this->config['permissions'] ?? [];
        if (!is_array($permissions)) {
            return [];
        }

        // Ensure proper typing for permissions (permission => description)
        /** @var array<string, string> $typedPermissions */
        $typedPermissions = [];
        foreach ($permissions as $permission => $description) {
            if (is_string($permission) && is_string($description)) {
                $typedPermissions[$permission] = $description;
            }
        }

        return $typedPermissions;
    }

    /**
     * Get module database tables.
     */
    public function getDatabaseTables(): array
    {
        $tables = $this->config['database_tables'] ?? [];
        if (!is_array($tables)) {
            return [];
        }

        // Ensure all table names are strings
        /** @var array<string> $typedTables */
        $typedTables = array_filter($tables, 'is_string');

        return array_values($typedTables);
    }

    /**
     * Get module status information.
     */
    public function getStatus(): array
    {
        $status = $this->config['status'] ?? [
            'implemented' => [],
            'planned'     => [],
        ];

        if (!is_array($status)) {
            return [
                'implemented' => [],
                'planned'     => [],
            ];
        }

        // Ensure proper typing for status (string => array<string>)
        /** @var array<string, array<string>> $typedStatus */
        $typedStatus = [];
        foreach ($status as $key => $value) {
            if (is_string($key) && is_array($value)) {
                $stringArray = array_filter($value, 'is_string');
                $typedStatus[$key] = array_values($stringArray);
            }
        }

        // Ensure required keys exist
        if (!isset($typedStatus['implemented'])) {
            $typedStatus['implemented'] = [];
        }
        if (!isset($typedStatus['planned'])) {
            $typedStatus['planned'] = [];
        }

        return $typedStatus;
    }

    /**
     * Initialize the module.
     */
    public function initialize(?object $container = null): void
    {
        if ($this->initialized) {
            return;
        }

        // Run initialization logic if defined
        if (isset($this->config['initialize']) && is_callable($this->config['initialize'])) {
            if ($container) {
                $this->config['initialize']($container);
            } else {
                // Try to call without parameters for backward compatibility
                try {
                    $this->config['initialize']();
                } catch (\ArgumentCountError $e) {
                    // If it requires parameters but none provided, skip initialization
                    // This prevents fatal errors during module loading
                }
            }
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
        // @phpstan-ignore-next-line function.alreadyNarrowedType
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
        /** @var array<string, mixed> $status */
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
                // Try to call health check with container if available
                // For now, call without parameters for backward compatibility
                $customHealth = $this->config['health_check']();
                if (is_array($customHealth)) {
                    /** @var array<string, mixed> $typedCustomHealth */
                    $typedCustomHealth = $customHealth;
                    $status = array_merge($status, $typedCustomHealth);
                }
            } catch (\ArgumentCountError $e) {
                $status['health_check_error'] = 'Health check requires container parameter';
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
