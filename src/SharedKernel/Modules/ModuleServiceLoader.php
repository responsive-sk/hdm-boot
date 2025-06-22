<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Modules;

use DI\Container;
use Psr\Log\LoggerInterface;

/**
 * Module Service Loader.
 *
 * Centrally loads services, routes, middleware from module configs.
 * Eliminates the need for fragmented config/services/ files.
 */
final class ModuleServiceLoader
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Load all module services into container.
     */
    public function loadServices(Container $container): void
    {
        $this->logger->info('Loading module services');

        $loadedServices = 0;
        $modules = $this->moduleManager->getAllModules();

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            if (isset($config['services']) && is_array($config['services'])) {
                /** @var array<string, mixed> $typedServices */
                $typedServices = $config['services'];
                $this->loadModuleServices($container, $module->getName(), $typedServices);
                $loadedServices += count($config['services']);
            }
        }

        $this->logger->info('Module services loaded', [
            'total_services' => $loadedServices,
            'modules_count' => count($modules),
        ]);
    }

    /**
     * Load all module routes.
     *
     * @return array<array<string, mixed>>
     */
    public function loadRoutes(): array
    {
        $this->logger->info('Loading module routes');

        $allRoutes = [];
        $modules = $this->moduleManager->getAllModules();

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            if (isset($config['routes']) && is_array($config['routes'])) {
                $moduleRoutes = $this->loadModuleRoutes($module->getName(), $config['routes']);
                $allRoutes = array_merge($allRoutes, $moduleRoutes);
            }
        }

        $this->logger->info('Module routes loaded', [
            'total_routes' => count($allRoutes),
            'modules_count' => count($modules),
        ]);

        return $allRoutes;
    }

    /**
     * Load all module middleware.
     *
     * @return array<string, string>
     */
    public function loadMiddleware(): array
    {
        $this->logger->info('Loading module middleware');

        $allMiddleware = [];
        $modules = $this->moduleManager->getAllModules();

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            if (isset($config['middleware']) && is_array($config['middleware'])) {
                /** @var array<string, string> $typedMiddleware */
                $typedMiddleware = $config['middleware'];
                $moduleMiddleware = $this->loadModuleMiddleware($module->getName(), $typedMiddleware);
                $allMiddleware = array_merge($allMiddleware, $moduleMiddleware);
            }
        }

        $this->logger->info('Module middleware loaded', [
            'total_middleware' => count($allMiddleware),
            'modules_count' => count($modules),
        ]);

        return $allMiddleware;
    }

    /**
     * Get all module API endpoints for documentation.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getApiEndpoints(): array
    {
        $allEndpoints = [];
        $modules = $this->moduleManager->getAllModules();

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            if (isset($config['api_endpoints']) && is_array($config['api_endpoints'])) {
                $allEndpoints[$module->getName()] = [
                    'module' => $module->getName(),
                    'version' => $config['version'] ?? '1.0.0',
                    'endpoints' => $config['api_endpoints'],
                ];
            }
        }

        return $allEndpoints;
    }

    /**
     * Get all module permissions.
     *
     * @return array<string, array<string, string>>
     */
    public function getPermissions(): array
    {
        $allPermissions = [];
        $modules = $this->moduleManager->getAllModules();

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            if (isset($config['permissions']) && is_array($config['permissions'])) {
                /** @var array<string, string> $typedPermissions */
                $typedPermissions = $config['permissions'];
                $allPermissions[$module->getName()] = $typedPermissions;
            }
        }

        return $allPermissions;
    }

    /**
     * Load services for a specific module.
     *
     * @param array<string, mixed> $services
     */
    private function loadModuleServices(Container $container, string $moduleName, array $services): void
    {
        foreach ($services as $serviceId => $definition) {
            try {
                $container->set($serviceId, $definition);

                $this->logger->debug('Service loaded', [
                    'module' => $moduleName,
                    'service' => $serviceId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to load service', [
                    'module' => $moduleName,
                    'service' => $serviceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Load routes for a specific module.
     *
     * @param array<mixed> $routes
     * @return array<array<string, mixed>>
     */
    private function loadModuleRoutes(string $moduleName, array $routes): array
    {
        $processedRoutes = [];

        foreach ($routes as $route) {
            if (!is_array($route)) {
                $this->logger->warning('Invalid route definition', [
                    'module' => $moduleName,
                    'route' => $route,
                ]);
                continue;
            }

            // Add module context to route with proper typing
            /** @var array<string, mixed> $typedRoute */
            $typedRoute = $route;
            $typedRoute['module'] = $moduleName;
            $processedRoutes[] = $typedRoute;

            $this->logger->debug('Route loaded', [
                'module' => $moduleName,
                'method' => $route['method'] ?? 'UNKNOWN',
                'pattern' => $route['pattern'] ?? 'UNKNOWN',
                'handler' => $route['handler'] ?? 'UNKNOWN',
            ]);
        }

        return $processedRoutes;
    }

    /**
     * Load middleware for a specific module.
     *
     * @param array<string, string> $middleware
     * @return array<string, string>
     */
    private function loadModuleMiddleware(string $moduleName, array $middleware): array
    {
        $processedMiddleware = [];

        foreach ($middleware as $middlewareClass => $description) {
            $processedMiddleware[$middlewareClass] = $description;

            $this->logger->debug('Middleware loaded', [
                'module' => $moduleName,
                'middleware' => $middlewareClass,
                'description' => $description,
            ]);
        }

        return $processedMiddleware;
    }

    /**
     * Get module statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $modules = $this->moduleManager->getAllModules();
        $totalServices = 0;
        $totalRoutes = 0;
        $totalMiddleware = 0;
        $totalPermissions = 0;

        foreach ($modules as $module) {
            $config = $this->moduleManager->getModuleConfig($module->getName());

            $services = $config['services'] ?? [];
            $routes = $config['routes'] ?? [];
            $middleware = $config['middleware'] ?? [];
            $permissions = $config['permissions'] ?? [];

            $totalServices += is_array($services) ? count($services) : 0;
            $totalRoutes += is_array($routes) ? count($routes) : 0;
            $totalMiddleware += is_array($middleware) ? count($middleware) : 0;
            $totalPermissions += is_array($permissions) ? count($permissions) : 0;
        }

        return [
            'modules_count' => count($modules),
            'total_services' => $totalServices,
            'total_routes' => $totalRoutes,
            'total_middleware' => $totalMiddleware,
            'total_permissions' => $totalPermissions,
            'modules_with_services' => count(array_filter($modules, fn($m) => !empty($this->moduleManager->getModuleConfig($m->getName())['services'] ?? []))),
            'modules_with_routes' => count(array_filter($modules, fn($m) => !empty($this->moduleManager->getModuleConfig($m->getName())['routes'] ?? []))),
            'modules_with_middleware' => count(array_filter($modules, fn($m) => !empty($this->moduleManager->getModuleConfig($m->getName())['middleware'] ?? []))),
        ];
    }
}
