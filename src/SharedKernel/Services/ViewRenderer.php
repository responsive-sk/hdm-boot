<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Services;

use HdmBoot\SharedKernel\Helpers\ThemeHelper;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * View Renderer Service.
 *
 * Handles theme-aware view rendering with fallback support.
 */
class ViewRenderer
{
    private ThemeManager $themeManager;
    private Paths $paths;

    public function __construct()
    {
        $this->themeManager = new ThemeManager();
        $this->paths = PathsFactory::create();
    }

    /**
     * Render a view with theme support.
     * 
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->resolveViewPath($view);
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        // Extract data to variables
        extract($data);
        
        // Make theme helper available in views
        $theme = ThemeHelper::class;
        
        // Start output buffering
        ob_start();
        
        try {
            include $viewPath;
            $output = ob_get_clean() ?: '';

            // Debug: Add HTML comments to see structure
            $output = "<!-- VIEW: {$view} -->\n" . $output . "\n<!-- END VIEW: {$view} -->";

            return $output;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering view {$view}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Resolve view path with theme fallback.
     */
    private function resolveViewPath(string $view): string
    {
        $activeTheme = $this->themeManager->getActiveTheme();

        // Convert dot notation to path
        $viewFile = str_replace('.', '/', $view) . '.php';

        // Try theme-specific view first
        $themeViewPath = $this->paths->base() . "/resources/themes/{$activeTheme}/views/{$viewFile}";
        if (file_exists($themeViewPath)) {
            return $themeViewPath;
        }

        // Try shared views
        $sharedViewPath = $this->paths->base() . "/resources/views/{$viewFile}";
        if (file_exists($sharedViewPath)) {
            return $sharedViewPath;
        }

        // Try module views (for backward compatibility)
        $moduleViewPath = $this->paths->base() . "/src/Modules/Optional/Blog/Views/{$viewFile}";
        if (file_exists($moduleViewPath)) {
            return $moduleViewPath;
        }

        // Return theme path even if it doesn't exist (for error reporting)
        return $themeViewPath;
    }

    /**
     * Check if view exists.
     */
    public function exists(string $view): bool
    {
        try {
            $viewPath = $this->resolveViewPath($view);
            return file_exists($viewPath);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Render layout with content.
     * 
     * @param array<string, mixed> $data
     */
    public function renderWithLayout(string $layout, string $content, array $data = []): string
    {
        $data['content'] = $content;
        return $this->render($layout, $data);
    }
}
