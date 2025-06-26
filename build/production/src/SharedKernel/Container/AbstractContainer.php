<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Container;

/**
 * Abstract Container Interface for HDM Boot Protocol.
 * 
 * Defines the contract for dependency injection containers.
 * Allows swapping between different DI implementations (Slim4, Symfony, Laravel, etc.)
 */
abstract class AbstractContainer
{
    /**
     * Get service from container.
     */
    abstract public function get(string $id): mixed;
    
    /**
     * Check if service exists in container.
     */
    abstract public function has(string $id): bool;
    
    /**
     * Set service in container.
     */
    abstract public function set(string $id, mixed $value): void;
    
    /**
     * Register service factory in container.
     */
    abstract public function factory(string $id, callable $factory): void;
    
    /**
     * Register singleton service in container.
     */
    abstract public function singleton(string $id, callable $factory): void;
    
    /**
     * Get container type identifier.
     */
    abstract public function getContainerType(): string;
    
    /**
     * Get underlying container instance (for advanced usage).
     */
    abstract public function getUnderlyingContainer(): mixed;
    
    /**
     * Register multiple services at once.
     * 
     * @param array<string, mixed> $services
     */
    public function registerServices(array $services): void
    {
        foreach ($services as $id => $service) {
            if (is_callable($service)) {
                $this->factory($id, $service);
            } else {
                $this->set($id, $service);
            }
        }
    }
    
    /**
     * Register HDM Boot core services.
     */
    public function registerCoreServices(): void
    {
        // This will be implemented by concrete containers
        // Each container type can have its own way of registering services
    }
    
    /**
     * Get service with type checking.
     * 
     * @template T
     * @param class-string<T> $id
     * @return T
     */
    public function getTyped(string $id): mixed
    {
        $service = $this->get($id);
        
        if (!$service instanceof $id) {
            throw new \RuntimeException(
                "Service '{$id}' is not an instance of expected type"
            );
        }
        
        return $service;
    }
    
    /**
     * Get optional service (returns null if not found).
     */
    public function getOptional(string $id): mixed
    {
        return $this->has($id) ? $this->get($id) : null;
    }
    
    /**
     * Register service with automatic interface binding.
     * 
     * @param class-string $interface
     * @param class-string $implementation
     */
    public function bind(string $interface, string $implementation): void
    {
        $this->factory($interface, function() use ($implementation) {
            return $this->get($implementation);
        });
    }
    
    /**
     * Register service with dependencies auto-resolution.
     * 
     * @param class-string $id
     */
    public function autowire(string $id): void
    {
        $this->factory($id, function() use ($id) {
            return $this->resolveClass($id);
        });
    }
    
    /**
     * Resolve class with dependency injection.
     * 
     * @param class-string $className
     */
    protected function resolveClass(string $className): object
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Class '{$className}' does not exist");
        }
        
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        
        if ($constructor === null) {
            return new $className();
        }
        
        $parameters = $constructor->getParameters();
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencyClass = $type->getName();
                $dependencies[] = $this->get($dependencyClass);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    "Cannot resolve parameter '{$parameter->getName()}' for class '{$className}'"
                );
            }
        }
        
        return new $className(...$dependencies);
    }
    
    /**
     * Get container information for debugging.
     * 
     * @return array<string, mixed>
     */
    public function getContainerInfo(): array
    {
        return [
            'type' => $this->getContainerType(),
            'services_registered' => $this->getRegisteredServices(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
    
    /**
     * Get list of registered services.
     * 
     * @return array<string>
     */
    abstract protected function getRegisteredServices(): array;
    
    /**
     * Clear all services (for testing).
     */
    abstract public function clear(): void;
    
    /**
     * Create container snapshot (for testing/rollback).
     * 
     * @return array<string, mixed>
     */
    abstract public function snapshot(): array;
    
    /**
     * Restore container from snapshot.
     * 
     * @param array<string, mixed> $snapshot
     */
    abstract public function restore(array $snapshot): void;
}
