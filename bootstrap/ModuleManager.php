<?php

declare(strict_types=1);

namespace MvaBootstrap\Bootstrap;

use DI\Container;
use Slim\App;

/**
 * Module Manager.
 *
 * Handles loading and registration of application modules.
 */
final class ModuleManager
{
    /** @var array<string, array<string, mixed>> */
    private array $loadedModules = [];

    /** @var array<string> */
    private array $coreModules = ['User', 'Security'];

    /** @var array<string> */
    private array $optionalModules = ['Article'];

    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * Load core modules (required).
     */
    public function loadCoreModules(): void
    {
        foreach ($this->coreModules as $moduleName) {
            try {
                $this->loadModule($moduleName, 'Core');
            } catch (\RuntimeException $e) {
                // Silently skip missing core modules during development
                // In production, you might want to log this or throw an error
            }
        }
    }

    /**
     * Load optional modules based on configuration.
     */
    public function loadOptionalModules(): void
    {
        $enabledModules = $this->getEnabledOptionalModules();

        foreach ($enabledModules as $moduleName) {
            if (in_array($moduleName, $this->optionalModules, true)) {
                try {
                    $this->loadModule($moduleName, 'Optional');
                } catch (\RuntimeException $e) {
                    // Silently skip missing optional modules
                }
            }
        }
    }

    /**
     * Load a specific module.
     */
    private function loadModule(string $moduleName, string $type): void
    {
        $moduleKey = "{$type}.{$moduleName}";

        if (isset($this->loadedModules[$moduleKey])) {
            return; // Already loaded
        }

        $modulePath = __DIR__ . "/../modules/{$type}/{$moduleName}";

        if (!is_dir($modulePath)) {
            throw new \RuntimeException("Module {$moduleName} not found in {$type}");
        }

        // Load module configuration
        $configFile = "{$modulePath}/config.php";
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->registerModuleServices($config);
        }

        // Load module routes
        $routesFile = "{$modulePath}/routes.php";
        if (file_exists($routesFile)) {
            $this->registerModuleRoutes($routesFile);
        }

        $this->loadedModules[$moduleKey] = [
            'name'      => $moduleName,
            'type'      => $type,
            'path'      => $modulePath,
            'loaded_at' => time(),
        ];
    }

    /**
     * Register module services in DI container.
     */
    /** @param array<string, mixed> $config */
    private function registerModuleServices(array $config): void
    {
        if (isset($config['services']) && is_array($config['services'])) {
            foreach ($config['services'] as $serviceId => $serviceDefinition) {
                $this->container->set($serviceId, $serviceDefinition);
            }
        }
    }

    /**
     * Register module routes.
     */
    private function registerModuleRoutes(string $routesFile): void
    {
        // Routes will be registered when Slim app is available
        // For now, we store the route file path
        $this->container->set('module.routes.' . str_replace(['/', '\\'], '.', dirname($routesFile)), $routesFile);
    }

    /**
     * Get enabled optional modules from configuration.
     */
    /** @return array<string> */
    private function getEnabledOptionalModules(): array
    {
        $enabledModules = $_ENV['ENABLED_MODULES'] ?? '';

        if (empty($enabledModules)) {
            return [];
        }

        return array_map('trim', explode(',', $enabledModules));
    }

    /**
     * Get loaded modules information.
     */
    /** @return array<string, array<string, mixed>> */
    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Check if module is loaded.
     */
    public function isModuleLoaded(string $moduleName, string $type = 'Core'): bool
    {
        return isset($this->loadedModules["{$type}.{$moduleName}"]);
    }

    /**
     * Register all module routes with Slim app.
     */
    public function registerRoutes(App $app): void
    {
        // Get all registered route files
        $routeFiles = [];
        foreach ($this->container->getKnownEntryNames() as $entryName) {
            if (str_starts_with($entryName, 'module.routes.')) {
                $routeFiles[] = $this->container->get($entryName);
            }
        }

        // Load each route file
        foreach ($routeFiles as $routeFile) {
            if (is_string($routeFile) && file_exists($routeFile)) {
                $routes = require $routeFile;
                if (is_callable($routes)) {
                    $routes($app);
                }
            }
        }
    }
}
