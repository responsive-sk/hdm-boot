<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Modules;

/**
 * Module manifest containing metadata about a module.
 */
class ModuleManifest
{
    public function __construct(
        private readonly string $name,
        private readonly string $version,
        private readonly array $dependencies = [],
        private readonly ?string $routesFile = null,
        private readonly ?string $configFile = null,
        private readonly ?string $description = null,
        private readonly array $authors = [],
        private readonly array $tags = [],
        private readonly array $provides = [],
        private readonly array $requires = [],
        private readonly bool $enabled = true
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getRoutesFile(): ?string
    {
        return $this->routesFile;
    }

    public function getConfigFile(): ?string
    {
        return $this->configFile;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getProvides(): array
    {
        return $this->provides;
    }

    public function getRequires(): array
    {
        return $this->requires;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Create manifest from array data.
     */
    public static function fromArray(array $data, string $moduleDir): self
    {
        return new self(
            name: $data['name'] ?? basename($moduleDir),
            version: $data['version'] ?? '1.0.0',
            dependencies: $data['dependencies'] ?? [],
            routesFile: isset($data['routes']) ? $moduleDir . '/' . $data['routes'] : null,
            configFile: isset($data['config']) ? $moduleDir . '/' . $data['config'] : null,
            description: $data['description'] ?? null,
            authors: $data['authors'] ?? [],
            tags: $data['tags'] ?? [],
            provides: $data['provides'] ?? [],
            requires: $data['requires'] ?? [],
            enabled: $data['enabled'] ?? true
        );
    }

    /**
     * Convert manifest to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'dependencies' => $this->dependencies,
            'routes' => $this->routesFile,
            'config' => $this->configFile,
            'description' => $this->description,
            'authors' => $this->authors,
            'tags' => $this->tags,
            'provides' => $this->provides,
            'requires' => $this->requires,
            'enabled' => $this->enabled,
        ];
    }

    /**
     * Validate manifest data.
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Module name is required';
        }

        if (empty($this->version)) {
            $errors[] = 'Module version is required';
        }

        if ($this->configFile && !file_exists($this->configFile)) {
            $errors[] = "Config file not found: {$this->configFile}";
        }

        if ($this->routesFile && !file_exists($this->routesFile)) {
            $errors[] = "Routes file not found: {$this->routesFile}";
        }

        return $errors;
    }
}
