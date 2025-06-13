<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Example;

use MvaBootstrap\SharedKernel\Contracts\ModuleBootstrapInterface;
use MvaBootstrap\SharedKernel\Events\EventDispatcherInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use RuntimeException;

/**
 * Example Bootstrap Template
 *
 * Replace 'Example' namespace with your module name.
 * This class initializes the module, registers its services,
 * middleware and routes.
 */
final class Bootstrap implements ModuleBootstrapInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {}

    public function bootstrap(App $app): void
    {
        // Register module services
        $this->registerServices();

        // Register module routes
        $this->registerRoutes($app);

        // Register module middleware
        $this->registerMiddleware($app);

        // Register module event listeners
        $this->registerEventListeners();
    }

    /**
     * @throws RuntimeException If services config file is missing or invalid
     */
    private function registerServices(): void
    {
        $configFile = __DIR__ . '/Infrastructure/Config/services.php';

        if (!file_exists($configFile)) {
            throw new RuntimeException("Services config file not found: {$configFile}");
        }

        /** @var array<string,callable> $services */
        $services = require $configFile;

        foreach ($services as $id => $factory) {
            if (!$this->container->has($id)) {
                $this->container->set($id, $factory);
            }
        }
    }

    /**
     * @throws RuntimeException If routes config file is missing or invalid
     */
    private function registerRoutes(App $app): void
    {
        $configFile = __DIR__ . '/Infrastructure/Config/routes.php';

        if (!file_exists($configFile)) {
            throw new RuntimeException("Routes config file not found: {$configFile}");
        }

        /** @var callable $routes */
        $routes = require $configFile;
        $routes($app);
    }

    /**
     * @throws RuntimeException If middleware config file is missing or invalid
     */
    private function registerMiddleware(App $app): void
    {
        $configFile = __DIR__ . '/Infrastructure/Config/middleware.php';

        if (!file_exists($configFile)) {
            throw new RuntimeException("Middleware config file not found: {$configFile}");
        }

        /** @var array<class-string> $middleware */
        $middleware = require $configFile;

        foreach ($middleware as $mw) {
            $app->add($this->container->get($mw));
        }
    }

    /**
     * @throws RuntimeException If events config file is missing or invalid
     */
    private function registerEventListeners(): void
    {
        $configFile = __DIR__ . '/Infrastructure/Config/events.php';

        if (!file_exists($configFile)) {
            throw new RuntimeException("Events config file not found: {$configFile}");
        }

        /** @var array<class-string,array<class-string>> $listeners */
        $listeners = require $configFile;

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        foreach ($listeners as $event => $handlers) {
            foreach ($handlers as $handler) {
                $dispatcher->addListener($event, $this->container->get($handler));
            }
        }
    }
}
