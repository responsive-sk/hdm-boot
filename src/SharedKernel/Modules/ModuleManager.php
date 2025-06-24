<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Modules;

use HdmBoot\SharedKernel\Contracts\ModuleInterface;
use Psr\Container\ContainerInterface;
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

    private ?ContainerInterface $container = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $modulesPath = 'modules'
    ) {
    }

    /**
     * Set container for module initialization.
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
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
                'module'   => $module,
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

        // Initialize the module (no parameters required by interface)
        // Log container availability for debugging
        if ($this->container !== null) {
            $this->logger->debug('Container available for module initialization', [
                'module_name' => $moduleName,
            ]);
        }
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
     * Check if module has isolated configuration (Full Module Isolation).
     */
    public function hasIsolatedConfig(string $moduleName): bool
    {
        $manifest = $this->getModuleManifest($moduleName);
        if (!$manifest) {
            return false;
        }

        $modulePath = dirname($manifest->getConfigFile() ?? '');

        return file_exists($modulePath . '/composer.json');
    }

    /**
     * Run module tests (Full Module Isolation).
     *
     * @return array{success: bool, return_code: int, module_path: string, output: string[]}
     */
    public function runModuleTests(string $moduleName): array
    {
        $manifest = $this->getModuleManifest($moduleName);
        if (!$manifest) {
            throw new \InvalidArgumentException("Module '{$moduleName}' not found");
        }

        $modulePath = dirname($manifest->getConfigFile() ?? '');
        $phpunitConfig = $modulePath . '/phpunit.xml';

        if (!file_exists($phpunitConfig)) {
            throw new \RuntimeException("Module '{$moduleName}' has no phpunit.xml configuration");
        }

        // Run PHPUnit for this module
        $command = "cd {$modulePath} && composer test 2>&1";
        $output = [];
        $returnCode = 0;

        exec($command, $output, $returnCode);

        return [
            'success'     => $returnCode === 0,
            'output'      => $output,
            'return_code' => $returnCode,
            'module_path' => $modulePath,
        ];
    }

    /**
     * Get module isolation info (Full Module Isolation).
     *
     * @return array{isolated: bool, module_path: string, has_composer: bool, has_tests: bool, has_ci: bool, has_readme: bool, reason?: string}
     */
    public function getModuleIsolationInfo(string $moduleName): array
    {
        $manifest = $this->getModuleManifest($moduleName);
        if (!$manifest) {
            return [
                'isolated'     => false,
                'module_path'  => '',
                'has_composer' => false,
                'has_tests'    => false,
                'has_ci'       => false,
                'has_readme'   => false,
                'reason'       => 'Module not found',
            ];
        }

        // Get module path from manifest or config file
        $configFile = $manifest->getConfigFile();
        $modulePath = $configFile ? dirname($configFile) : '';

        // If no config file, try to find module directory
        if (empty($modulePath)) {
            $moduleDirectories = $this->findModuleDirectories();
            foreach ($moduleDirectories as $dir) {
                if (basename($dir) === $moduleName || str_contains($dir, $moduleName)) {
                    $modulePath = $dir;
                    break;
                }
            }
        }

        $info = [
            'isolated'     => false,
            'module_path'  => $modulePath,
            'has_composer' => file_exists($modulePath . '/composer.json'),
            'has_tests'    => file_exists($modulePath . '/phpunit.xml'),
            'has_ci'       => file_exists($modulePath . '/.github/workflows/ci.yml'),
            'has_readme'   => file_exists($modulePath . '/README.md'),
        ];

        $info['isolated'] = $info['has_composer'] && $info['has_tests'];

        if (!$info['isolated']) {
            $missing = [];
            if (!$info['has_composer']) {
                $missing[] = 'composer.json';
            }
            if (!$info['has_tests']) {
                $missing[] = 'phpunit.xml';
            }
            $info['reason'] = 'Missing: ' . implode(', ', $missing);
        } else {
            $info['reason'] = 'Fully isolated module';
        }

        return $info;
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
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

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

            // Create manifest object with proper type casting
            /** @var array<string, mixed> $typedManifestData */
            $typedManifestData = $manifestData;
            $manifest = ModuleManifest::fromArray($typedManifestData, $moduleDir);

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
                $configRaw = require $manifest->getConfigFile();
                if (is_array($configRaw)) {
                    /** @var array<string, mixed> $typedConfig */
                    $typedConfig = $configRaw;
                    $config = $typedConfig;
                }
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

            // Create module instance from config with proper type casting
            /** @var array<string, mixed> $typedConfig */
            $typedConfig = $config;
            $module = $this->createModuleFromConfig($moduleDir, $typedConfig);
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
     *
     * @param array<string, mixed> $config
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
     *
     * @param array<string, mixed> $config
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
