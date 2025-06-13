<?php

declare(strict_types=1);

namespace MvaBootstrap\Bootstrap;

use DI\Container;
use Dotenv\Dotenv;
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
        $rootPath ??= dirname(__DIR__, 2); // Go to project root
        $this->paths = new Paths($rootPath);
        $this->loadEnvironment($rootPath);
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

        // Setup middleware stack (now module services are available)
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
    private function loadEnvironment(string $rootPath): void
    {
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // Set error reporting for development
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    private function createContainer(): Container
    {
        $configPath = $this->paths->base() . '/config/container.php';

        return require $configPath;
    }

    /** @return SlimApp<Container> */
    private function createSlimApp(): SlimApp
    {
        AppFactory::setContainer($this->container);
        $app = AppFactory::create();
        assert($app instanceof SlimApp);

        return $app;
    }

    private function loadRoutes(): void
    {
        $routesFile = $this->paths->base() . '/config/routes.php';
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            if (is_callable($routes)) {
                $routes($this->slimApp);
            }
        }
    }

    private function setupMiddleware(): void
    {
        // Application middleware stack (order matters!)
        $this->slimApp->add($this->container->get(\MvaBootstrap\Modules\Core\Language\Infrastructure\Middleware\LocaleMiddleware::class));
        $this->slimApp->add(\Odan\Session\Middleware\SessionStartMiddleware::class);
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
            $monitoringBootstrap = new \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Bootstrap\MonitoringBootstrap(
                $this->container,
                $this->container->get(\Psr\Log\LoggerInterface::class)
            );

            $monitoringBootstrap->bootstrap();
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
            $eventBootstrap = new \MvaBootstrap\SharedKernel\Events\EventBootstrap(
                $this->container,
                $this->container->get(\Psr\Log\LoggerInterface::class)
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
            $moduleManager = new \MvaBootstrap\SharedKernel\Modules\ModuleManager(
                $this->container->get(\Psr\Log\LoggerInterface::class),
                $this->paths->base() . '/src/Modules'
            );

            // Discover and load modules
            $moduleManager->discoverModules();

            // Initialize modules
            $moduleManager->initializeModules();

            // Store module manager in container for later use
            $this->container->set(\MvaBootstrap\SharedKernel\Modules\ModuleManager::class, $moduleManager);

            // Load module services into container
            $serviceLoader = $this->container->get(\MvaBootstrap\SharedKernel\Modules\ModuleServiceLoader::class);
            $serviceLoader->loadServices($this->container);

        } catch (\Exception $e) {
            // Log error but don't fail application startup
            error_log('Failed to setup module system: ' . $e->getMessage());
        }
    }
}
