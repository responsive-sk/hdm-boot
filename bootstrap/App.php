<?php

declare(strict_types=1);

namespace MvaBootstrap\Bootstrap;

use DI\Container;
use Dotenv\Dotenv;
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

    private bool $isInitialized = false;

    public function __construct(?string $rootPath = null)
    {
        $rootPath = $rootPath ?? dirname(__DIR__);
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

        // Load routes
        $this->loadRoutes();

        // Setup middleware stack
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
        $configPath = dirname(__DIR__) . '/config/container.php';
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
        $routesFile = dirname(__DIR__) . '/config/routes.php';
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
        $this->slimApp->add($this->container->get(\MvaBootstrap\Shared\Middleware\LocaleMiddleware::class));
        $this->slimApp->add(\Odan\Session\Middleware\SessionStartMiddleware::class);
        $this->slimApp->addRoutingMiddleware();
        $this->slimApp->addBodyParsingMiddleware();
    }

    private function setupErrorHandling(): void
    {
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        $this->slimApp->addErrorMiddleware($isDebug, true, true);
    }
}
