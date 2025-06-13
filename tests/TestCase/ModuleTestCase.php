<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\TestCase;

use DI\Container;
use DI\ContainerBuilder;
use MvaBootstrap\SharedKernel\Events\EventDispatcher;
use MvaBootstrap\SharedKernel\Modules\GenericModule;
use MvaBootstrap\SharedKernel\Contracts\ModuleInterface;
use MvaBootstrap\SharedKernel\Modules\ModuleManager;
use MvaBootstrap\SharedKernel\Modules\ModuleManifest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Module Test Case.
 * 
 * Base test case for testing individual modules in isolation.
 * Provides automatic module loading, mock services, and testing utilities.
 */
abstract class ModuleTestCase extends TestCase
{
    protected Container $container;
    protected ModuleManager $moduleManager;
    protected ModuleInterface $module;
    protected string $moduleName;
    protected string $modulePath;

    /** @var MockObject|LoggerInterface */
    protected MockObject $mockLogger;

    protected EventDispatcher $mockEventDispatcher;

    /**
     * Get the module name to test.
     * Must be implemented by concrete test classes.
     */
    abstract protected function getModuleName(): string;

    /**
     * Get additional module dependencies.
     * Override to specify dependencies for the module under test.
     *
     * @return string[]
     */
    protected function getModuleDependencies(): array
    {
        return [];
    }

    /**
     * Get additional container services.
     * Override to provide custom services for testing.
     *
     * @return array<string, callable>
     */
    protected function getAdditionalServices(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleName = $this->getModuleName();

        // Create mock services
        $this->createMockServices();

        // Setup container with minimal services
        $this->setupContainer();

        // Now we can get module path after container is initialized
        $this->modulePath = $this->getModulePath($this->moduleName);

        // Load module under test
        $this->loadModule();
    }

    protected function tearDown(): void
    {
        // Clean up
        unset($this->container, $this->moduleManager, $this->module);
        parent::tearDown();
    }

    /**
     * Create mock services for testing.
     */
    protected function createMockServices(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        // EventDispatcher is final, so we use real implementation
        $this->mockEventDispatcher = new EventDispatcher($this->mockLogger);
    }

    /**
     * Setup DI container with minimal services.
     */
    protected function setupContainer(): void
    {
        $builder = new ContainerBuilder();
        
        // Add core services
        $builder->addDefinitions([
            LoggerInterface::class => $this->mockLogger,
            EventDispatcher::class => $this->mockEventDispatcher,
            
            // Module Manager
            ModuleManager::class => function (Container $c): ModuleManager {
                return new ModuleManager($c->get(LoggerInterface::class));
            },
            
            // Settings
            'settings' => [
                'app' => [
                    'name' => 'MVA Bootstrap Test',
                    'env' => 'testing',
                ],
                'paths' => [
                    'root' => dirname(__DIR__, 2),
                    'modules' => dirname(__DIR__, 2) . '/src/Modules/Core',
                ],
            ],
        ]);

        // Add additional services
        $additionalServices = $this->getAdditionalServices();
        if (!empty($additionalServices)) {
            $builder->addDefinitions($additionalServices);
        }

        $this->container = $builder->build();
        $this->moduleManager = $this->container->get(ModuleManager::class);
    }

    /**
     * Load the module under test.
     */
    protected function loadModule(): void
    {
        // Load dependencies first
        $dependencies = $this->getModuleDependencies();
        foreach ($dependencies as $dependency) {
            $this->loadModuleByName($dependency);
        }

        // Load main module
        $this->module = $this->loadModuleByName($this->moduleName);
    }

    /**
     * Load a module by name.
     */
    protected function loadModuleByName(string $moduleName): ModuleInterface
    {
        $modulePath = $this->getModulePath($moduleName);
        
        // Check for manifest file
        $manifestFile = $modulePath . '/module.php';
        $configFile = $modulePath . '/config.php';

        if (file_exists($manifestFile)) {
            return $this->loadModuleFromManifest($moduleName, $manifestFile);
        } elseif (file_exists($configFile)) {
            return $this->loadModuleFromConfig($moduleName, $configFile);
        }

        throw new \RuntimeException("Module '{$moduleName}' not found at '{$modulePath}'");
    }

    /**
     * Load module from manifest file.
     */
    protected function loadModuleFromManifest(string $moduleName, string $manifestFile): ModuleInterface
    {
        $manifestData = require $manifestFile;
        $manifest = ModuleManifest::fromArray($manifestData, dirname($manifestFile));
        
        // Load config if specified
        $config = [];
        if ($manifest->getConfigFile() && file_exists($manifest->getConfigFile())) {
            $config = require $manifest->getConfigFile();
        }

        // Register services in container
        if (isset($config['services'])) {
            $this->registerModuleServices($config['services']);
        }

        $module = new GenericModule($moduleName, dirname($manifestFile), $config, $manifest);
        $this->moduleManager->registerModule($module);

        return $module;
    }

    /**
     * Load module from config file (legacy).
     */
    protected function loadModuleFromConfig(string $moduleName, string $configFile): ModuleInterface
    {
        $config = require $configFile;
        
        // Register services in container
        if (isset($config['services'])) {
            $this->registerModuleServices($config['services']);
        }

        $module = new GenericModule($moduleName, dirname($configFile), $config);
        $this->moduleManager->registerModule($module);

        return $module;
    }

    /**
     * Register module services in container.
     */
    protected function registerModuleServices(array $services): void
    {
        foreach ($services as $id => $definition) {
            if (is_callable($definition)) {
                $this->container->set($id, $definition);
            } else {
                $this->container->set($id, $definition);
            }
        }
    }

    /**
     * Get module path.
     */
    protected function getModulePath(string $moduleName): string
    {
        $settings = $this->container->get('settings');
        return $settings['paths']['modules'] . '/' . $moduleName;
    }

    /**
     * Get service from container.
     */
    protected function getService(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if service exists in container.
     */
    protected function hasService(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Assert that a service is registered.
     */
    protected function assertServiceRegistered(string $serviceId): void
    {
        $this->assertTrue(
            $this->hasService($serviceId),
            "Service '{$serviceId}' is not registered in the container"
        );
    }

    /**
     * Assert that a service is an instance of expected class.
     */
    protected function assertServiceInstanceOf(string $serviceId, string $expectedClass): void
    {
        $this->assertServiceRegistered($serviceId);
        $service = $this->getService($serviceId);
        $this->assertInstanceOf($expectedClass, $service);
    }

    /**
     * Assert that module is loaded.
     */
    protected function assertModuleLoaded(string $moduleName): void
    {
        $this->assertTrue(
            $this->moduleManager->isModuleLoaded($moduleName),
            "Module '{$moduleName}' is not loaded"
        );
    }

    /**
     * Assert that module has manifest.
     */
    protected function assertModuleHasManifest(string $moduleName): void
    {
        $manifest = $this->moduleManager->getModuleManifest($moduleName);
        $this->assertNotNull($manifest, "Module '{$moduleName}' does not have a manifest");
    }

    /**
     * Get module manifest.
     */
    protected function getModuleManifest(string $moduleName): ?ModuleManifest
    {
        return $this->moduleManager->getModuleManifest($moduleName);
    }
}
