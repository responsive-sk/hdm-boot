<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Domain\Contracts;

/**
 * Template Engine Interface.
 *
 * Contract for different template engine implementations (Twig, Smarty, etc.).
 */
interface TemplateEngineInterface
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
    public function templateExists(string $template): bool;

    /**
     * Get template engine name.
     *
     * @return string Engine name (e.g., 'twig', 'smarty', 'php')
     */
    public function getEngineName(): string;

    /**
     * Get supported file extensions.
     *
     * @return array<string> Array of supported extensions
     */
    public function getSupportedExtensions(): array;

    /**
     * Configure template engine.
     *
     * @param array<string, mixed> $config Configuration options
     */
    public function configure(array $config): void;
}
