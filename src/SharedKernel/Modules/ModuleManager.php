<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Modules;

use MvaBootstrap\SharedKernel\Contracts\ModuleInterface;
use Psr\Log\LoggerInterface;

/**
 * Module Manager.
 *
 * Centralized management of module loading, configuration, and lifecycle.
 */
final class ModuleManager
{
    /**
     * @var array<string, ModuleInterface>
     */
    private array $modules = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $moduleConfigs = [];

    /**
     * @var array<string, ModuleManifest>
     */
    private array $moduleManifests = [];

    /**
     * @var array<string>
     */
    private array $initializedModules = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $modulesPath = 'modules'
    ) {
    }

    /**
     * Discover and load all modules.
     */
    public function discoverModules(): void
    {
        $this->logger->info('Starting module discovery', [
            'modules_path' => $this->modulesPath,
        ]);

        $moduleDirectories = $this->findModuleDirectories();

        foreach ($moduleDirectories as $moduleDir) {
            $this->loadModuleFromDirectory($moduleDir);
        }

        $this->logger->info('Module discovery completed', [
            'discovered_modules' => count($this->modules),
            'module_names'       => array_keys($this->modules),
        ]);
    }

    /**
     * Register a module instance.
     */
    public function registerModule(ModuleInterface $module): void
    {
        $moduleName = $module->getName();

        $this->logger->debug('Registering module', [
            'module_name'    => $moduleName,
            'module_version' => $module->getVersion(),
        ]);

        // Validate module configuration
        $validationErrors = $module->validateConfig();
        if (!empty($validationErrors)) {
            $this->logger->error('Module configuration validation failed', [
                'module_name' => $moduleName,
                'errors'      => $validationErrors,
            ]);
            throw new \InvalidArgumentException(
                "Module '{$moduleName}' configuration is invalid: " . implode(', ', $validationErrors)
            );
        }

        $this->modules[$moduleName] = $module;
        $this->moduleConfigs[$moduleName] = $module->getConfig();

        $this->logger->info('Module registered successfully', [
            'module_name'  => $moduleName,
            'dependencies' => $module->getDependencies(),
        ]);
    }

    /**
     * Get module by name.
     */
    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Get all registered modules.
     *
     * @return array<string, ModuleInterface>
     */
    public function getAllModules(): array
    {
        return $this->modules;
    }

    /**
     * Get module configuration.
     *
     * @return array<string, mixed>
     */
    public function getModuleConfig(string $moduleName): array
    {
        return $this->moduleConfigs[$moduleName] ?? [];
    }

    /**
     * Get all module configurations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllModuleConfigs(): array
    {
        return $this->moduleConfigs;
    }

    /**
     * Get module manifest.
     */
    public function getModuleManifest(string $moduleName): ?ModuleManifest
    {
        return $this->moduleManifests[$moduleName] ?? null;
    }

    /**
     * Get all module manifests.
     *
     * @return array<string, ModuleManifest>
     */
    public function getAllModuleManifests(): array
    {
        return $this->moduleManifests;
    }

    /**
     * Get loaded modules with their manifests.
     *
     * @return array<string, array{module: ModuleInterface, manifest: ?ModuleManifest}>
     */
    public function getLoadedModules(): array
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            $result[$name] = [
                'module' => $module,
                'manifest' => $this->moduleManifests[$name] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Check if a module is loaded.
     */
    public function isModuleLoaded(string $moduleName): bool
    {
        return isset($this->modules[$moduleName]);
    }

    /**
     * Initialize modules in dependency order.
     */
    public function initializeModules(): void
    {
        $this->logger->info('Starting module initialization');

        $initializationOrder = $this->resolveDependencyOrder();

        foreach ($initializationOrder as $moduleName) {
            $this->initializeModule($moduleName);
        }

        $this->logger->info('Module initialization completed', [
            'initialized_modules' => $this->initializedModules,
        ]);
    }

    /**
     * Initialize specific module.
     */
    public function initializeModule(string $moduleName): void
    {
        if (in_array($moduleName, $this->initializedModules, true)) {
            return; // Already initialized
        }

        $module = $this->getModule($moduleName);
        if (!$module) {
            throw new \RuntimeException("Module '{$moduleName}' not found");
        }

        $this->logger->debug('Initializing module', [
            'module_name' => $moduleName,
        ]);

        // Initialize dependencies first
        foreach ($module->getDependencies() as $dependency) {
            $this->initializeModule($dependency);
        }

        // Initialize the module
        $module->initialize();
        $this->initializedModules[] = $moduleName;

        $this->logger->info('Module initialized successfully', [
            'module_name' => $moduleName,
        ]);
    }

    /**
     * Check if module is registered.
     */
    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Check if module is initialized.
     */
    public function isModuleInitialized(string $name): bool
    {
        return in_array($name, $this->initializedModules, true);
    }

    /**
     * Get module health status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getModulesHealthStatus(): array
    {
        $healthStatus = [];

        foreach ($this->modules as $name => $module) {
            $healthStatus[$name] = $module->getHealthStatus();
        }

        return $healthStatus;
    }

    /**
     * Get module statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $totalModules = count($this->modules);
        $initializedModules = count($this->initializedModules);

        $modulesByStatus = [];
        foreach ($this->modules as $name => $module) {
            $status = $this->isModuleInitialized($name) ? 'initialized' : 'registered';
            $modulesByStatus[$status][] = $name;
        }

        return [
            'total_modules'       => $totalModules,
            'initialized_modules' => $initializedModules,
            'pending_modules'     => $totalModules - $initializedModules,
            'modules_by_status'   => $modulesByStatus,
            'module_names'        => array_keys($this->modules),
        ];
    }

    /**
     * Find module directories.
     *
     * @return array<string>
     */
    private function findModuleDirectories(): array
    {
        $directories = [];

        if (!is_dir($this->modulesPath)) {
            $this->logger->warning('Modules directory not found', [
                'path' => $this->modulesPath,
            ]);

            return $directories;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->modulesPath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $file->getFilename() !== '.' && $file->getFilename() !== '..') {
                $manifestFile = $file->getPathname() . '/module.php';
                $configFile = $file->getPathname() . '/config.php';

                // Prefer manifest file, fallback to config file
                if (file_exists($manifestFile) || file_exists($configFile)) {
                    $directories[] = $file->getPathname();
                }
            }
        }

        return $directories;
    }

    /**
     * Load module from directory.
     */
    private function loadModuleFromDirectory(string $moduleDir): void
    {
        $manifestFile = $moduleDir . '/module.php';
        $configFile = $moduleDir . '/config.php';

        // Try to load manifest first
        if (file_exists($manifestFile)) {
            $this->loadModuleFromManifest($moduleDir, $manifestFile);
        } elseif (file_exists($configFile)) {
            $this->loadModuleFromConfig($moduleDir, $configFile);
        } else {
            $this->logger->warning('No module manifest or config file found', [
                'module_dir'    => $moduleDir,
                'manifest_file' => $manifestFile,
                'config_file'   => $configFile,
            ]);
        }
    }

    /**
     * Load module from manifest file.
     */
    private function loadModuleFromManifest(string $moduleDir, string $manifestFile): void
    {
        try {
            $manifestData = require $manifestFile;

            if (!is_array($manifestData)) {
                $this->logger->error('Invalid module manifest format', [
                    'module_dir'    => $moduleDir,
                    'manifest_file' => $manifestFile,
                    'expected'      => 'array',
                    'actual'        => gettype($manifestData),
                ]);
                return;
            }

            // Create manifest object
            $manifest = ModuleManifest::fromArray($manifestData, $moduleDir);

            // Validate manifest
            $errors = $manifest->validate();
            if (!empty($errors)) {
                $this->logger->error('Module manifest validation failed', [
                    'module_dir' => $moduleDir,
                    'errors'     => $errors,
                ]);
                return;
            }

            // Skip disabled modules
            if (!$manifest->isEnabled()) {
                $this->logger->info('Skipping disabled module', [
                    'module_name' => $manifest->getName(),
                    'module_dir'  => $moduleDir,
                ]);
                return;
            }

            $this->moduleManifests[$manifest->getName()] = $manifest;

            // Load config if specified
            $config = [];
            if ($manifest->getConfigFile() && file_exists($manifest->getConfigFile())) {
                $config = require $manifest->getConfigFile();
            }

            // Create module instance
            $module = $this->createModuleFromManifest($manifest, $config);
            $this->registerModule($module);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to load module from manifest', [
                'module_dir'    => $moduleDir,
                'manifest_file' => $manifestFile,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
            ]);
        }
    }

    /**
     * Load module from config file (legacy support).
     */
    private function loadModuleFromConfig(string $moduleDir, string $configFile): void
    {
        try {
            $config = require $configFile;

            if (!is_array($config)) {
                $this->logger->error('Invalid module config format', [
                    'module_dir' => $moduleDir,
                    'expected'   => 'array',
                    'actual'     => gettype($config),
                ]);
                return;
            }

            // Create module instance from config
            $module = $this->createModuleFromConfig($moduleDir, $config);
            $this->registerModule($module);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to load module', [
                'module_dir' => $moduleDir,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);
        }
    }

    /**
     * Create module instance from manifest.
     */
    private function createModuleFromManifest(ModuleManifest $manifest, array $config): ModuleInterface
    {
        // Create generic module instance with manifest data
        return new GenericModule(
            $manifest->getName(),
            dirname($manifest->getConfigFile() ?? ''),
            $config,
            $manifest
        );
    }

    /**
     * Create module instance from configuration (legacy support).
     */
    private function createModuleFromConfig(string $moduleDir, array $config): ModuleInterface
    {
        // Extract module name from directory path
        $moduleName = basename($moduleDir);

        // Create generic module instance
        return new GenericModule($moduleName, $moduleDir, $config);
    }

    /**
     * Resolve module dependency order.
     *
     * @return array<string>
     */
    private function resolveDependencyOrder(): array
    {
        $resolved = [];
        $visiting = [];

        foreach (array_keys($this->modules) as $moduleName) {
            $this->resolveDependencies($moduleName, $resolved, $visiting);
        }

        return $resolved;
    }

    /**
     * Resolve dependencies recursively.
     *
     * @param array<string> $resolved
     * @param array<string> $visiting
     */
    private function resolveDependencies(string $moduleName, array &$resolved, array &$visiting): void
    {
        if (in_array($moduleName, $resolved, true)) {
            return; // Already resolved
        }

        if (in_array($moduleName, $visiting, true)) {
            throw new \RuntimeException("Circular dependency detected involving module '{$moduleName}'");
        }

        $module = $this->getModule($moduleName);
        if (!$module) {
            throw new \RuntimeException("Module '{$moduleName}' not found");
        }

        $visiting[] = $moduleName;

        foreach ($module->getDependencies() as $dependency) {
            $this->resolveDependencies($dependency, $resolved, $visiting);
        }

        $resolved[] = $moduleName;
        $visiting = array_filter($visiting, fn ($name) => $name !== $moduleName);
    }
}
