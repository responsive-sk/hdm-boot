<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Domain\Contracts;

/**
 * Template Renderer Interface.
 *
 * Contract for template rendering services.
 */
interface TemplateRendererInterface
{
    /**
     * Render template with data.
     *
     * @param string $template Template name/path
     * @param array<string, mixed> $data Template variables
     *
     * @return string Rendered template content
     */
    public function render(string $template, array $data = []): string;

    /**
     * Check if template exists.
     *
     * @param string $template Template name/path
     *
     * @return bool True if template exists
     */
    public function exists(string $template): bool;

    /**
     * Add global template variable.
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     */
    public function addGlobal(string $name, $value): void;

    /**
     * Set template directory.
     *
     * @param string $directory Template directory path
     */
    public function setTemplateDirectory(string $directory): void;

    /**
     * Get template directory.
     *
     * @return string Template directory path
     */
    public function getTemplateDirectory(): string;

    /**
     * Enable or disable template caching.
     *
     * @param bool $enabled Cache enabled flag
     */
    public function setCacheEnabled(bool $enabled): void;

    /**
     * Check if template caching is enabled.
     *
     * @return bool True if caching is enabled
     */
    public function isCacheEnabled(): bool;

    /**
     * Clear template cache.
     */
    public function clearCache(): void;
}
