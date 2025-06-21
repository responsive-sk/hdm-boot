<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Optional\Blog\Services;

/**
 * Simple Template Renderer for Blog Module.
 *
 * Lightweight template renderer without dependencies.
 * Used specifically for Blog module templates.
 */
class SimpleTemplateRenderer
{
    private string $templateDir;

    public function __construct(string $templateDir)
    {
        $this->templateDir = rtrim($templateDir, '/');
    }

    /**
     * Render template and return HTML string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->templateDir . '/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$template}");
        }

        // Extract data to variables
        extract($data);

        // Capture template output
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        if ($content === false) {
            throw new \RuntimeException('Failed to render template');
        }

        return $content;
    }

    /**
     * Check if template exists.
     */
    public function exists(string $template): bool
    {
        $templatePath = $this->templateDir . '/' . $template . '.php';
        return file_exists($templatePath);
    }

    /**
     * Escape HTML for output.
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
