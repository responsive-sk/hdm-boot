<?php

declare(strict_types=1);

namespace MvaBootstrap\Bootstrap;

use DI\Container;
use Dotenv\Dotenv;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;

/**
 * Main Bootstrap Application Class.
 *
 * Handles application initialization, module loading, and request processing.
 */
final class App
{
    /** @var SlimApp<Container> */
    private SlimApp $slimApp;

    private Container $container;

    private ModuleManager $moduleManager;

    private bool $isInitialized = false;

    public function __construct(string $rootPath)
    {
        $this->loadEnvironment($rootPath);
        $this->container = $this->createContainer();
        $this->moduleManager = new ModuleManager($this->container);
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

        // Load core modules first (will be silent if modules don't exist yet)
        $this->moduleManager->loadCoreModules();

        // Load optional modules based on configuration
        $this->moduleManager->loadOptionalModules();

        // Register all module routes
        $this->moduleManager->registerRoutes($this->slimApp);

        // Load main routes configuration
        $this->loadRoutes();

        // Setup middleware
        $this->setupMiddleware();

        // Setup error handling
        $this->setupErrorHandling();

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
     * Load environment variables.
     */
    private function loadEnvironment(string $rootPath): void
    {
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // Set error reporting for development
        if ($this->isDebugMode()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    /**
     * Create DI container.
     */
    private function createContainer(): Container
    {
        return require __DIR__ . '/../config/container.php';
    }

    /**
     * Create Slim application.
     */
    /** @return SlimApp<Container> */
    private function createSlimApp(): SlimApp
    {
        AppFactory::setContainer($this->container);

        $app = AppFactory::create();
        assert($app instanceof SlimApp);
        return $app;
    }

    /**
     * Load routes from configuration.
     */
    private function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/../config/routes.php';

        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            if (is_callable($routes)) {
                $routes($this->slimApp);
            }
        }
    }

    /**
     * Setup middleware stack.
     */
    private function setupMiddleware(): void
    {
        // Add locale middleware (early in stack for language detection)
        $this->slimApp->add($this->container->get(\MvaBootstrap\Shared\Middleware\LocaleMiddleware::class));

        // Add session start middleware
        $this->slimApp->add(\Odan\Session\Middleware\SessionStartMiddleware::class);

        // Add routing middleware
        $this->slimApp->addRoutingMiddleware();

        // Add body parsing middleware
        $this->slimApp->addBodyParsingMiddleware();
    }

    /**
     * Setup error handling.
     */
    private function setupErrorHandling(): void
    {
        $this->slimApp->addErrorMiddleware(
            displayErrorDetails: $this->isDebugMode(),
            logErrors: true,
            logErrorDetails: true
        );
    }

    /**
     * Check if application is in debug mode.
     */
    private function isDebugMode(): bool
    {
        return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }
}
