<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Modules;

use MvaBootstrap\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Module manifest containing metadata about a module.
 */
class ModuleManifest
{
    /**
     * @param array<string> $dependencies
     * @param array<string> $authors
     * @param array<string> $tags
     * @param array<string> $provides
     * @param array<string, mixed> $requires
     */
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

    /**
     * @return array<string>
     */
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

    /**
     * @return array<string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return array<string>
     */
    public function getProvides(): array
    {
        return $this->provides;
    }

    /**
     * @return array<string, mixed>
     */
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
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $moduleDir): self
    {
        // Ensure arrays are properly typed with safe filtering
        $dependenciesRaw = $data['dependencies'] ?? [];
        $authorsRaw = $data['authors'] ?? [];
        $tagsRaw = $data['tags'] ?? [];
        $providesRaw = $data['provides'] ?? [];
        $requiresRaw = $data['requires'] ?? [];

        // Filter and type-cast arrays properly
        /** @var array<string> $dependencies */
        $dependencies = is_array($dependenciesRaw)
            ? array_values(array_filter($dependenciesRaw, 'is_string'))
            : [];

        /** @var array<string> $authors */
        $authors = is_array($authorsRaw)
            ? array_values(array_filter($authorsRaw, 'is_string'))
            : [];

        /** @var array<string> $tags */
        $tags = is_array($tagsRaw)
            ? array_values(array_filter($tagsRaw, 'is_string'))
            : [];

        /** @var array<string> $provides */
        $provides = is_array($providesRaw)
            ? array_values(array_filter($providesRaw, 'is_string'))
            : [];

        /** @var array<string, mixed> $requires */
        $requires = is_array($requiresRaw) ? $requiresRaw : [];

        // Resolve file paths securely using Paths-compatible approach
        $routesFile = null;
        $configFile = null;

        if (isset($data['routes'])) {
            $routesPath = $data['routes'];
            if (is_string($routesPath)) {
                $routesFile = str_starts_with($routesPath, '/')
                    ? $routesPath  // Absolute path
                    : self::securePath($moduleDir, $routesPath);  // Secure relative path
            }
        }

        if (isset($data['config'])) {
            $configPath = $data['config'];
            if (is_string($configPath)) {
                $configFile = str_starts_with($configPath, '/')
                    ? $configPath  // Absolute path
                    : self::securePath($moduleDir, $configPath);  // Secure relative path
            }
        }

        return new self(
            name: is_string($data['name'] ?? null) ? $data['name'] : basename($moduleDir),
            version: is_string($data['version'] ?? null) ? $data['version'] : '1.0.0',
            dependencies: $dependencies,
            routesFile: $routesFile,
            configFile: $configFile,
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
            authors: $authors,
            tags: $tags,
            provides: $provides,
            requires: $requires,
            enabled: is_bool($data['enabled'] ?? null) ? $data['enabled'] : true
        );
    }

    /**
     * Convert manifest to array.
     *
     * @return array<string, mixed>
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
     *
     * @return array<string>
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

    /**
     * Secure path resolution helper.
     *
     * Prevents path traversal attacks by validating relative paths.
     * This is a temporary solution until full Paths integration.
     */
    private static function securePath(string $basePath, string $relativePath): string
    {
        // Validate relative path for security
        if (str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException("Path traversal detected in: {$relativePath}");
        }

        if (str_contains($relativePath, '~')) {
            throw new \InvalidArgumentException("Home directory access not allowed: {$relativePath}");
        }

        // Clean and normalize the path
        $relativePath = ltrim($relativePath, '/\\');

        // Use PathsFactory for secure cross-platform path joining
        $paths = PathsFactory::create();
        $fullPath = $paths->getPath($basePath, $relativePath);

        return $fullPath;
    }
}
