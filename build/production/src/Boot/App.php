<?php

declare(strict_types=1);

namespace HdmBoot\Boot;

use DI\Container;
use Dotenv\Dotenv;
use HdmBoot\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Paths\Paths;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;

/**
 * Simplified Bootstrap Application Class.
 *
 * Handles application initialization and request processing with minimal complexity.
 */
final class App
{
    /** @var SlimApp<Container> */
    private SlimApp $slimApp;

    private Container $container;

    private Paths $paths;

    private bool $isInitialized = false;

    public function __construct(?string $rootPath = null)
    {
        // Use PathsFactory for secure path handling
        if ($rootPath !== null) {
            // For testing or custom root path
            /** @var array{base_path: string, paths: array<string, string>} $pathsConfig */
            $pathsConfig = require $rootPath . '/config/paths.php';
            $this->paths = new Paths($pathsConfig['base_path'], $pathsConfig['paths']);
        } else {
            // Use PathsFactory for production
            $this->paths = PathsFactory::create();
        }

        // Load paths configuration for legacy compatibility
        /** @var array{base_path: string, paths: array<string, string>} $pathsConfig */
        $pathsConfig = require $this->paths->config('paths.php');

        $this->paths = new Paths($pathsConfig['base_path'], $pathsConfig['paths']);

        $this->loadEnvironment();
        $this->container = $this->createContainer();
        $this->slimApp = $this->createSlimApp();
    }

    /**
     * Initialize the application.
     */
    public function initialize(): self
    {
        if ($this->isInitialized) {
            return $this;
        }

        // Setup module system FIRST (loads module services)
        $this->setupModuleSystem();

        // Load routes
        $this->loadRoutes();

        // Setup middleware stack AFTER module services are loaded
        $this->setupMiddleware();

        // Setup error handling
        $this->setupErrorHandling();

        // Setup monitoring and health checks
        $this->setupMonitoring();

        // Setup event system
        $this->setupEventSystem();

        $this->isInitialized = true;

        return $this;
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }

        $this->slimApp->run();
    }

    /**
     * Get the Slim application instance.
     */
    /** @return SlimApp<Container> */
    public function getSlimApp(): SlimApp
    {
        return $this->slimApp;
    }

    /**
     * Get the DI container.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Load environment and create core components.
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->paths->base());
        $dotenv->safeLoad();

        // Set error reporting for development
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    private function createContainer(): Container
    {
        $configPath = $this->paths->config('container.php');

        /** @var Container $container */
        $container = require $configPath;

        // @phpstan-ignore-next-line instanceof.alwaysTrue
        if (!$container instanceof Container) {
            throw new \RuntimeException('Container configuration must return DI\Container instance');
        }

        return $container;
    }

    /** @return SlimApp<Container> */
    private function createSlimApp(): SlimApp
    {
        AppFactory::setContainer($this->container);
        $app = AppFactory::create();

        // Ensure we have the correct container type
        if (!$app->getContainer() instanceof Container) {
            throw new \RuntimeException('Expected DI\Container but got different container type');
        }

        // @phpstan-ignore-next-line
        return $app;
    }

    private function loadRoutes(): void
    {
        // Load main application routes
        $routesFile = $this->paths->config('routes.php');
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            if (is_callable($routes)) {
                $routes($this->slimApp);
            }
        }

        // Load module routes
        $this->loadModuleRoutes();
    }

    /**
     * Load routes from all registered modules.
     */
    private function loadModuleRoutes(): void
    {
        try {
            $moduleManager = $this->container->get(\HdmBoot\SharedKernel\Modules\ModuleManager::class);
            if (!$moduleManager instanceof \HdmBoot\SharedKernel\Modules\ModuleManager) {
                return;
            }

            $modules = $moduleManager->getAllModules();
            $logger = $this->container->get(\Psr\Log\LoggerInterface::class);

            if (!$logger instanceof \Psr\Log\LoggerInterface) {
                throw new \RuntimeException('Logger service not properly configured');
            }

            foreach ($modules as $module) {
                $this->loadModuleRouteFile($module, $logger);
            }

            $logger->info('Module routes loaded', [
                'modules_count' => count($modules),
            ]);
        } catch (\Exception $e) {
            error_log('Failed to load module routes: ' . $e->getMessage());
        }
    }

    /**
     * Load routes from a specific module.
     */
    private function loadModuleRouteFile(\HdmBoot\SharedKernel\Contracts\ModuleInterface $module, \Psr\Log\LoggerInterface $logger): void
    {
        try {
            // Get module manifest to find routes file
            $moduleManager = $this->container->get(\HdmBoot\SharedKernel\Modules\ModuleManager::class);

            if (!$moduleManager instanceof \HdmBoot\SharedKernel\Modules\ModuleManager) {
                return;
            }

            $manifest = $moduleManager->getModuleManifest($module->getName());

            if ($manifest) {
                // Use manifest to get routes file
                $routesFile = $manifest->getRoutesFile();
            } else {
                // Fallback: check module config for routes
                $config = $moduleManager->getModuleConfig($module->getName());
                if (isset($config['routes']) && is_string($config['routes'])) {
                    // Use secure path construction with Paths service - build path step by step
                    $moduleRelativePath = 'Modules/' . str_replace('\\', '/', $module->getName());
                    $moduleDir = $this->paths->getPath($this->paths->src(), $moduleRelativePath);
                    $routesFile = $this->paths->getPath($moduleDir, $config['routes']);
                } else {
                    return; // No routes defined
                }
            }

            if (!$routesFile || !file_exists($routesFile)) {
                return; // No routes file, skip
            }

            $routes = require $routesFile;
            if (is_callable($routes)) {
                $routes($this->slimApp);

                $logger->debug('Module routes loaded', [
                    'module'      => $module->getName(),
                    'routes_file' => $routesFile,
                ]);
            }
        } catch (\Exception $e) {
            $logger->warning('Failed to load module routes', [
                'module' => $module->getName(),
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function setupMiddleware(): void
    {
        // Application middleware stack (order matters!)
        try {
            $localeMiddleware = $this->container->get(\HdmBoot\Modules\Core\Language\Infrastructure\Middleware\LocaleMiddleware::class);
            if ($localeMiddleware instanceof \Psr\Http\Server\MiddlewareInterface) {
                $this->slimApp->add($localeMiddleware);
            }
        } catch (\Exception $e) {
            // Log error but don't fail - LocaleMiddleware is optional
            error_log('Failed to load LocaleMiddleware: ' . $e->getMessage());
        }

        // Note: SessionStartMiddleware moved to route-specific middleware
        // Only routes that need session (login, profile, admin) will load session

        $this->slimApp->addRoutingMiddleware();
        $this->slimApp->addBodyParsingMiddleware();
    }

    private function setupErrorHandling(): void
    {
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $this->slimApp->addErrorMiddleware($isDebug, true, true);
    }

    /**
     * Setup monitoring and health checks.
     */
    private function setupMonitoring(): void
    {
        try {
            $monitoringBootstrap = $this->container->get(\HdmBoot\Modules\Core\Monitoring\Infrastructure\Bootstrap\MonitoringBootstrap::class);
            if ($monitoringBootstrap instanceof \HdmBoot\Modules\Core\Monitoring\Infrastructure\Bootstrap\MonitoringBootstrap) {
                $monitoringBootstrap->bootstrap();
            }
        } catch (\Exception $e) {
            // Log error but don't fail application startup
            error_log('Failed to setup monitoring: ' . $e->getMessage());
        }
    }

    /**
     * Setup event system.
     */
    private function setupEventSystem(): void
    {
        try {
            $logger = $this->container->get(\Psr\Log\LoggerInterface::class);
            if (!$logger instanceof \Psr\Log\LoggerInterface) {
                throw new \RuntimeException('Logger service not properly configured');
            }

            $eventBootstrap = new \HdmBoot\SharedKernel\Events\EventBootstrap(
                $this->container,
                $logger
            );

            $eventBootstrap->bootstrap();
        } catch (\Exception $e) {
            // Log error but don't fail application startup
            error_log('Failed to setup event system: ' . $e->getMessage());
        }
    }

    /**
     * Setup module system.
     */
    private function setupModuleSystem(): void
    {
        try {
            $logger = $this->container->get(\Psr\Log\LoggerInterface::class);
            if (!$logger instanceof \Psr\Log\LoggerInterface) {
                throw new \RuntimeException('Logger service not properly configured');
            }

            $moduleManager = new \HdmBoot\SharedKernel\Modules\ModuleManager(
                $logger,
                $this->paths->src('Modules')
            );

            // Set container for module initialization
            $moduleManager->setContainer($this->container);

            // Discover and load modules
            $moduleManager->discoverModules();

            // Initialize modules
            $moduleManager->initializeModules();

            // Store module manager in container for later use
            $this->container->set(\HdmBoot\SharedKernel\Modules\ModuleManager::class, $moduleManager);

            // Create service loader manually (since ModuleManager is now available)
            $logger = $this->container->get(\Psr\Log\LoggerInterface::class);
            assert($logger instanceof \Psr\Log\LoggerInterface);
            $serviceLoader = new \HdmBoot\SharedKernel\Modules\ModuleServiceLoader($moduleManager, $logger);

            // Debug: List loaded modules
            $modules = $moduleManager->getAllModules();
            error_log('Loaded modules: ' . implode(', ', array_keys($modules)));

            // Load module services into container
            $serviceLoader->loadServices($this->container);
        } catch (\Exception $e) {
            // Log error but don't fail application startup
            error_log('Failed to setup module system: ' . $e->getMessage());
        }
    }
}
