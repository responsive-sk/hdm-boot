<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Container;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Slim4 Container Implementation for HDM Boot Protocol.
 *
 * Wraps PHP-DI container used by Slim4 framework.
 * Provides HDM Boot protocol compliance while maintaining Slim4 compatibility.
 */
final class Slim4Container extends AbstractContainer
{
    private Container $container;
    private ?Paths $paths = null;

    /**
     * Service registry for tracking registered services.
     *
     * @var array<string, mixed>
     */
    private array $serviceRegistry = [];

    public function __construct(?Container $container = null, ?Paths $paths = null)
    {
        $this->paths = $paths;
        $this->container = $container ?? $this->createDefaultContainer();
    }

    /**
     * Create default PHP-DI container.
     */
    private function createDefaultContainer(): Container
    {
        $builder = new ContainerBuilder();

        // Enable compilation in production
        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            // Use Paths service if available, fallback to relative path
            if ($this->paths !== null) {
                $cacheDir = $this->paths->path('cache/container');
            } else {
                $cacheDir = dirname(__DIR__, 2) . '/var/cache/container';
            }

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            $builder->enableCompilation($cacheDir);
        }

        // Enable definition cache in production
        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            $builder->enableDefinitionCache();
        }

        return $builder->build();
    }

    /**
     * Get service from container.
     */
    public function get(string $id): mixed
    {
        try {
            return $this->container->get($id);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to resolve service '{$id}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if service exists in container.
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Set service in container.
     */
    public function set(string $id, mixed $value): void
    {
        $this->container->set($id, $value);
        $this->serviceRegistry[$id] = 'value';
    }

    /**
     * Register service factory in container.
     */
    public function factory(string $id, callable $factory): void
    {
        $this->container->set($id, $factory);
        $this->serviceRegistry[$id] = 'factory';
    }

    /**
     * Register singleton service in container.
     */
    public function singleton(string $id, callable $factory): void
    {
        // PHP-DI creates singletons by default, so factory() is sufficient
        $this->factory($id, $factory);
        $this->serviceRegistry[$id] = 'singleton';
    }

    /**
     * Get container type identifier.
     */
    public function getContainerType(): string
    {
        return 'slim4-phpdi';
    }

    /**
     * Get underlying container instance.
     */
    public function getUnderlyingContainer(): Container
    {
        return $this->container;
    }

    /**
     * Register HDM Boot core services.
     */
    public function registerCoreServices(): void
    {
        // Register Paths service
        $this->factory(\ResponsiveSk\Slim4Paths\Paths::class, function () {
            return new \ResponsiveSk\Slim4Paths\Paths(__DIR__ . '/../../..');
        });

        // Register PermissionManager
        $this->factory(\HdmBoot\SharedKernel\System\PermissionManager::class, function () {
            $paths = $this->get(\ResponsiveSk\Slim4Paths\Paths::class);
            if (!$paths instanceof \ResponsiveSk\Slim4Paths\Paths) {
                throw new \RuntimeException('Paths service must be instance of ResponsiveSk\Slim4Paths\Paths');
            }

            return new \HdmBoot\SharedKernel\System\PermissionManager($paths);
        });

        // Register DatabaseManagerFactory
        $this->factory(\HdmBoot\SharedKernel\Database\DatabaseManagerFactory::class, function () {
            $paths = $this->get(\ResponsiveSk\Slim4Paths\Paths::class);
            if (!$paths instanceof \ResponsiveSk\Slim4Paths\Paths) {
                throw new \RuntimeException('Paths service must be instance of ResponsiveSk\Slim4Paths\Paths');
            }

            return new \HdmBoot\SharedKernel\Database\DatabaseManagerFactory($paths);
        });

        // Register individual database managers
        $this->factory('database.mark', function () {
            $factory = $this->get(\HdmBoot\SharedKernel\Database\DatabaseManagerFactory::class);
            if (!$factory instanceof \HdmBoot\SharedKernel\Database\DatabaseManagerFactory) {
                throw new \RuntimeException('DatabaseManagerFactory service not properly configured');
            }

            return $factory->createMarkManager();
        });

        $this->factory('database.user', function () {
            $factory = $this->get(\HdmBoot\SharedKernel\Database\DatabaseManagerFactory::class);
            if (!$factory instanceof \HdmBoot\SharedKernel\Database\DatabaseManagerFactory) {
                throw new \RuntimeException('DatabaseManagerFactory service not properly configured');
            }

            return $factory->createUserManager();
        });

        $this->factory('database.app', function () {
            $factory = $this->get(\HdmBoot\SharedKernel\Database\DatabaseManagerFactory::class);
            if (!$factory instanceof \HdmBoot\SharedKernel\Database\DatabaseManagerFactory) {
                throw new \RuntimeException('DatabaseManagerFactory service not properly configured');
            }

            return $factory->createSystemManager();
        });

        // Register PSR-3 Logger (if available)
        if (interface_exists(\Psr\Log\LoggerInterface::class)) {
            $this->factory(\Psr\Log\LoggerInterface::class, function () {
                // Default to error_log logger if no other logger is configured
                return new class () implements \Psr\Log\LoggerInterface {
                    use \Psr\Log\LoggerTrait;

                    /**
                     * @param mixed $level
                     * @param mixed $message
                     * @param array<string, mixed> $context
                     */
                    public function log($level, $message, array $context = []): void
                    {
                        $levelStr = is_string($level) ? $level : 'INFO';
                        $messageStr = is_string($message) ? $message : (is_object($message) && method_exists($message, '__toString') ? (string) $message : 'UNKNOWN');
                        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
                        error_log("[{$levelStr}] {$messageStr}{$contextStr}");
                    }
                };
            });
        }
    }

    /**
     * Get list of registered services.
     */
    protected function getRegisteredServices(): array
    {
        return array_keys($this->serviceRegistry);
    }

    /**
     * Clear all services (for testing).
     */
    public function clear(): void
    {
        // Create new container instance
        $this->container = $this->createDefaultContainer();
        $this->serviceRegistry = [];
    }

    /**
     * Create container snapshot.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'services'       => $this->serviceRegistry,
            'container_type' => $this->getContainerType(),
        ];
    }

    /**
     * Restore container from snapshot.
     *
     * @param array<string, mixed> $snapshot
     */
    public function restore(array $snapshot): void
    {
        // Note: PHP-DI doesn't support full restoration
        // This is a limitation of the underlying container
        if (isset($snapshot['services']) && is_array($snapshot['services'])) {
            // Ensure all values are properly typed
            $services = [];
            foreach ($snapshot['services'] as $key => $value) {
                if (is_string($key)) {
                    $services[$key] = $value;
                }
            }
            $this->serviceRegistry = $services;
        } else {
            $this->serviceRegistry = [];
        }
    }

    /**
     * Get PHP-DI specific container builder.
     *
     * @return ContainerBuilder<Container>
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return new ContainerBuilder();
    }

    /**
     * Add PHP-DI definitions.
     *
     * @param array<string, mixed> $definitions
     */
    public function addDefinitions(array $definitions): void
    {
        foreach ($definitions as $id => $definition) {
            $this->container->set($id, $definition);
            $this->serviceRegistry[$id] = 'definition';
        }
    }

    /**
     * Enable container compilation (production optimization).
     */
    public function enableCompilation(string $compilationPath): void
    {
        // This would require rebuilding the container
        // For now, just store the path for future use
        $this->set('container.compilation_path', $compilationPath);
    }

    /**
     * Get PSR-11 compatible container interface.
     */
    public function getPsr11Container(): ContainerInterface
    {
        return $this->container;
    }
}
