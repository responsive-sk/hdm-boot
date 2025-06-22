<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Domain\ValueObjects;

use HdmBoot\SharedKernel\Services\PathsFactory;

/**
 * Template Name Value Object.
 *
 * Represents a validated template name with path and extension.
 */
final readonly class TemplateName
{
    public function __construct(
        private string $name
    ) {
        $this->validate($name);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $name): self
    {
        return new self($name);
    }

    /**
     * Get template name.
     */
    public function toString(): string
    {
        return $this->name;
    }

    /**
     * Get template name without extension.
     */
    public function getNameWithoutExtension(): string
    {
        return pathinfo($this->name, PATHINFO_FILENAME);
    }

    /**
     * Get template extension.
     */
    public function getExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Get template directory.
     */
    public function getDirectory(): string
    {
        $dir = pathinfo($this->name, PATHINFO_DIRNAME);

        return $dir === '.' ? '' : $dir;
    }

    /**
     * Check if template has extension.
     */
    public function hasExtension(): bool
    {
        return !empty($this->getExtension());
    }

    /**
     * Add extension if not present.
     */
    public function withExtension(string $extension): self
    {
        if ($this->hasExtension()) {
            return $this;
        }

        return new self($this->name . '.' . ltrim($extension, '.'));
    }

    /**
     * Change extension.
     */
    public function changeExtension(string $extension): self
    {
        $nameWithoutExt = $this->getNameWithoutExtension();
        $directory = $this->getDirectory();

        $newName = $directory ? $this->buildSecurePath($directory, $nameWithoutExt) : $nameWithoutExt;
        $newName .= '.' . ltrim($extension, '.');

        return new self($newName);
    }

    /**
     * Check if template is in subdirectory.
     */
    public function isInSubdirectory(): bool
    {
        return str_contains($this->name, '/');
    }

    /**
     * Validate template name.
     */
    private function validate(string $name): void
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Template name cannot be empty');
        }

        if (str_contains($name, '..')) {
            throw new \InvalidArgumentException('Template name cannot contain ".." for security reasons');
        }

        if (str_contains($name, '\\')) {
            throw new \InvalidArgumentException('Template name must use forward slashes');
        }

        if (preg_match('/[<>:"|?*]/', $name)) {
            throw new \InvalidArgumentException('Template name contains invalid characters');
        }
    }

    /**
     * String representation.
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Equality check.
     */
    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    /**
     * Build secure path for template names.
     *
     * Prevents path traversal attacks by validating components.
     */
    private function buildSecurePath(string $directory, string $filename): string
    {
        // Validate directory for security (already validated in constructor, but double-check)
        if (str_contains($directory, '..') || str_contains($filename, '..')) {
            throw new \InvalidArgumentException("Path traversal detected in template path");
        }

        // Use PathsFactory for secure cross-platform path joining
        $paths = PathsFactory::create();
        return $paths->getPath($directory, $filename);
    }
}
