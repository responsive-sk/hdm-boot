<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Container;

use ResponsiveSk\Slim4Paths\Paths;

/**
 * Container Factory for HDM Boot Protocol.
 *
 * Creates different DI container implementations based on client requirements.
 * Supports Slim4, Symfony, Laravel, and custom containers.
 */
final class ContainerFactory
{
    /**
     * Create container based on type.
     *
     * @param array<string, mixed> $options
     */
    public static function create(string $type = 'slim4', array $options = []): AbstractContainer
    {
        return match (strtolower($type)) {
            'slim4', 'phpdi' => self::createSlim4Container($options),
            'symfony' => self::createSymfonyContainer($options),
            'laravel' => self::createLaravelContainer($options),
            'laminas' => self::createLaminasContainer($options),
            'pimple'  => self::createPimpleContainer($options),
            'custom'  => self::createCustomContainer($options),
            default   => throw new \InvalidArgumentException("Unsupported container type: {$type}"),
        };
    }

    /**
     * Create Slim4 container (default).
     *
     * @param array<string, mixed> $options
     */
    private static function createSlim4Container(array $options): Slim4Container
    {
        // Create Paths service for proper path management
        $paths = new Paths(dirname(__DIR__, 2));

        $container = new Slim4Container(null, $paths);
        $container->registerCoreServices();

        // Apply custom options
        if (isset($options['definitions']) && is_array($options['definitions'])) {
            // Ensure definitions array has proper string keys
            $definitions = [];
            foreach ($options['definitions'] as $key => $value) {
                if (is_string($key)) {
                    $definitions[$key] = $value;
                }
            }
            $container->addDefinitions($definitions);
        }

        if (isset($options['compilation_path']) && is_string($options['compilation_path'])) {
            $container->enableCompilation($options['compilation_path']);
        }

        return $container;
    }

    /**
     * Create Symfony container.
     *
     * @param array<string, mixed> $options
     */
    private static function createSymfonyContainer(array $options): AbstractContainer
    {
        // TODO: Implement SymfonyContainer
        throw new \RuntimeException('Symfony container not yet implemented');
    }

    /**
     * Create Laravel container.
     *
     * @param array<string, mixed> $options
     */
    private static function createLaravelContainer(array $options): AbstractContainer
    {
        // TODO: Implement LaravelContainer
        throw new \RuntimeException('Laravel container not yet implemented');
    }

    /**
     * Create Laminas container.
     *
     * @param array<string, mixed> $options
     */
    private static function createLaminasContainer(array $options): AbstractContainer
    {
        // TODO: Implement LaminasContainer
        throw new \RuntimeException('Laminas container not yet implemented');
    }

    /**
     * Create Pimple container.
     *
     * @param array<string, mixed> $options
     */
    private static function createPimpleContainer(array $options): AbstractContainer
    {
        // TODO: Implement PimpleContainer
        throw new \RuntimeException('Pimple container not yet implemented');
    }

    /**
     * Create custom container.
     *
     * @param array<string, mixed> $options
     */
    private static function createCustomContainer(array $options): AbstractContainer
    {
        if (!isset($options['class']) || !is_string($options['class'])) {
            throw new \InvalidArgumentException('Custom container requires "class" option as string');
        }

        $className = $options['class'];

        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Custom container class does not exist: {$className}");
        }

        if (!is_subclass_of($className, AbstractContainer::class)) {
            throw new \InvalidArgumentException(
                "Custom container must extend AbstractContainer: {$className}"
            );
        }

        $constructorArgs = $options['constructor_args'] ?? [];

        return new $className(...$constructorArgs);
    }

    /**
     * Auto-detect container type based on available packages.
     */
    public static function autoDetect(): string
    {
        // Check for Slim4/PHP-DI
        if (class_exists(\DI\Container::class)) {
            return 'slim4';
        }

        // Check for Symfony
        if (class_exists(\Symfony\Component\DependencyInjection\Container::class)) {
            return 'symfony';
        }

        // Check for Laravel
        if (class_exists(\Illuminate\Container\Container::class)) {
            return 'laravel';
        }

        // Check for Laminas
        if (class_exists(\Laminas\ServiceManager\ServiceManager::class)) {
            return 'laminas';
        }

        // Check for Pimple
        if (class_exists(\Pimple\Container::class)) {
            return 'pimple';
        }

        // Default to Slim4
        return 'slim4';
    }

    /**
     * Create container with auto-detection.
     *
     * @param array<string, mixed> $options
     */
    public static function createAuto(array $options = []): AbstractContainer
    {
        $type = self::autoDetect();

        return self::create($type, $options);
    }

    /**
     * Get supported container types.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSupportedTypes(): array
    {
        return [
            'slim4' => [
                'name'        => 'Slim4 (PHP-DI)',
                'description' => 'Default HDM Boot container with PHP-DI',
                'status'      => 'implemented',
                'package'     => 'php-di/php-di',
            ],
            'symfony' => [
                'name'        => 'Symfony DI Container',
                'description' => 'Symfony dependency injection container',
                'status'      => 'planned',
                'package'     => 'symfony/dependency-injection',
            ],
            'laravel' => [
                'name'        => 'Laravel Container',
                'description' => 'Laravel/Illuminate container',
                'status'      => 'planned',
                'package'     => 'illuminate/container',
            ],
            'laminas' => [
                'name'        => 'Laminas ServiceManager',
                'description' => 'Laminas dependency injection container',
                'status'      => 'planned',
                'package'     => 'laminas/laminas-servicemanager',
            ],
            'pimple' => [
                'name'        => 'Pimple Container',
                'description' => 'Lightweight dependency injection container',
                'status'      => 'planned',
                'package'     => 'pimple/pimple',
            ],
            'custom' => [
                'name'        => 'Custom Container',
                'description' => 'Client-provided container implementation',
                'status'      => 'supported',
                'package'     => 'user-defined',
            ],
        ];
    }

    /**
     * Validate container configuration.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function validateConfiguration(string $type, array $options = []): array
    {
        $errors = [];

        switch (strtolower($type)) {
            case 'slim4':
                // Validate Slim4 options
                if (isset($options['compilation_path']) && !is_string($options['compilation_path'])) {
                    $errors['compilation_path'] = 'compilation_path must be a string';
                }
                break;

            case 'custom':
                if (!isset($options['class'])) {
                    $errors['class'] = 'Custom container requires "class" option';
                }
                break;
        }

        return $errors;
    }
}
