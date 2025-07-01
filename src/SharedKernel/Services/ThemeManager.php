<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Services;

use ResponsiveSk\Slim4Paths\Paths;

/**
 * Theme Manager Service.
 *
 * Manages theme selection, asset loading, and theme-specific functionality.
 * Supports Laravel-style resources/themes structure with per-theme node_modules.
 */
class ThemeManager
{
    private string $activeTheme = 'default';

    /** @var array<string> */
    private array $availableThemes = [];

    /** @var array<string, array<string, mixed>> */
    private array $themeManifests = [];

    private Paths $paths;

    public function __construct()
    {
        $this->paths = PathsFactory::create();
        $this->loadAvailableThemes();
        $this->loadThemeManifests();
    }

    /**
     * Set the active theme.
     */
    public function setActiveTheme(string $theme): void
    {
        if (!$this->isThemeAvailable($theme)) {
            throw new \InvalidArgumentException("Theme '{$theme}' is not available");
        }

        $this->activeTheme = $theme;
    }

    /**
     * Get the active theme name.
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    /**
     * Get all available themes.
     *
     * @return array<string>
     */
    public function getAvailableThemes(): array
    {
        return $this->availableThemes;
    }

    /**
     * Check if a theme is available.
     */
    public function isThemeAvailable(string $theme): bool
    {
        return in_array($theme, $this->availableThemes, true);
    }

    /**
     * Get theme assets (CSS and JS files).
     *
     * @return array{css: array<string>, js: array<string>}
     */
    public function getThemeAssets(?string $theme = null): array
    {
        $theme ??= $this->activeTheme;

        if (!$this->isThemeAvailable($theme)) {
            throw new \InvalidArgumentException("Theme '{$theme}' is not available");
        }

        $manifest = $this->getThemeManifest($theme);
        $assets = [
            'css' => [],
            'js'  => [],
        ];

        if ($manifest) {
            // Get assets from Vite manifest
            foreach ($manifest as $key => $asset) {
                if (is_array($asset) && isset($asset['file']) && is_string($asset['file'])) {
                    $assetPath = "/assets/themes/{$theme}/{$asset['file']}";

                    if (str_ends_with($asset['file'], '.css')) {
                        $assets['css'][] = $assetPath;
                    } elseif (str_ends_with($asset['file'], '.js')) {
                        $assets['js'][] = $assetPath;
                    }
                }
            }
        } else {
            // Fallback to default asset paths
            $assets['css'][] = "/assets/themes/{$theme}/css/app.css";
            $assets['js'][] = "/assets/themes/{$theme}/js/app.js";
        }

        return $assets;
    }

    /**
     * Get theme configuration.
     *
     * @return array<string, mixed>
     */
    public function getThemeConfig(?string $theme = null): array
    {
        $theme ??= $this->activeTheme;
        $configPath = $this->paths->base() . "/resources/themes/{$theme}/theme.json";

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            if ($content !== false) {
                $config = json_decode($content, true);

                if (is_array($config)) {
                    /** @var array<string, mixed> $config */
                    return $config;
                }
                return [];
            }
        }

        return $this->getDefaultThemeConfig($theme);
    }

    /**
     * Get theme view path.
     */
    public function getThemeViewPath(?string $theme = null): string
    {
        $theme ??= $this->activeTheme;

        return $this->paths->base() . "/resources/themes/{$theme}/views";
    }

    /**
     * Get theme asset source path.
     */
    public function getThemeAssetPath(?string $theme = null): string
    {
        $theme ??= $this->activeTheme;

        return $this->paths->base() . "/resources/themes/{$theme}/assets";
    }

    /**
     * Get theme build output path.
     */
    public function getThemeBuildPath(?string $theme = null): string
    {
        $theme ??= $this->activeTheme;

        return $this->paths->public() . "/assets/themes/{$theme}";
    }

    /**
     * Build theme assets.
     */
    public function buildTheme(?string $theme = null): bool
    {
        $theme ??= $this->activeTheme;

        if (!$this->isThemeAvailable($theme)) {
            throw new \InvalidArgumentException("Theme '{$theme}' is not available");
        }

        $themePath = $this->paths->base() . "/resources/themes/{$theme}";

        if (!file_exists("{$themePath}/package.json")) {
            throw new \RuntimeException("Theme '{$theme}' does not have package.json");
        }

        // Change to theme directory and run build
        $originalDir = getcwd();
        if ($originalDir !== false) {
            chdir($themePath);

            try {
                // Install dependencies if node_modules doesn't exist
                if (!is_dir('node_modules')) {
                    $installResult = shell_exec('pnpm install 2>&1');
                    if ($installResult === null) {
                        throw new \RuntimeException('Failed to install theme dependencies');
                    }
                }

                // Run build
                $buildResult = shell_exec('pnpm run build 2>&1');

                if ($buildResult === null) {
                    throw new \RuntimeException('Failed to build theme');
                }

                // Check if build was successful
                $buildPath = $this->getThemeBuildPath($theme);
                if (!is_dir($buildPath)) {
                    throw new \RuntimeException('Build output directory not found');
                }

                return true;
            } finally {
                chdir($originalDir);
            }
        }

        return false;
    }

    /**
     * Watch theme for changes (development mode).
     */
    public function watchTheme(?string $theme = null): void
    {
        $theme ??= $this->activeTheme;

        if (!$this->isThemeAvailable($theme)) {
            throw new \InvalidArgumentException("Theme '{$theme}' is not available");
        }

        $themePath = $this->paths->base() . "/resources/themes/{$theme}";

        if (!file_exists("{$themePath}/package.json")) {
            throw new \RuntimeException("Theme '{$theme}' does not have package.json");
        }

        // Change to theme directory and start watch
        $originalDir = getcwd();
        if ($originalDir !== false) {
            chdir($themePath);

            try {
                // Start development server
                passthru('pnpm run dev');
            } finally {
                chdir($originalDir);
            }
        }
    }

    /**
     * Generate theme CSS/JS tags for HTML.
     */
    public function renderThemeAssets(?string $theme = null): string
    {
        $assets = $this->getThemeAssets($theme);
        $html = '';

        // CSS files - preload critical CSS
        foreach ($assets['css'] as $css) {
            // Preload CSS for better performance
            $html .= "<link rel=\"preload\" href=\"{$css}\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet'\">\n";
            $html .= "<noscript><link rel=\"stylesheet\" href=\"{$css}\"></noscript>\n";
        }

        // JS files - defer non-critical scripts
        foreach ($assets['js'] as $js) {
            // Check if this is a critical script (main app)
            if (str_contains($js, 'app-')) {
                // Critical script - load immediately but defer execution
                $html .= "<script type=\"module\" src=\"{$js}\" defer></script>\n";
            } else {
                // Non-critical script - load with defer and lower priority
                $html .= "<script type=\"module\" src=\"{$js}\" defer async></script>\n";
            }
        }

        return $html;
    }

    /**
     * Load available themes from filesystem.
     */
    private function loadAvailableThemes(): void
    {
        $themesPath = $this->paths->base() . '/resources/themes';

        if (!is_dir($themesPath)) {
            $this->availableThemes = ['default'];

            return;
        }

        $themes = [];
        $directories = scandir($themesPath);

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $themePath = "{$themesPath}/{$dir}";

            if (is_dir($themePath) && file_exists("{$themePath}/package.json")) {
                $themes[] = $dir;
            }
        }

        $this->availableThemes = $themes ?: ['default'];
    }

    /**
     * Load theme manifests (Vite build manifests).
     */
    private function loadThemeManifests(): void
    {
        foreach ($this->availableThemes as $theme) {
            $manifestPath = $this->getThemeBuildPath($theme) . '/.vite/manifest.json';

            if (file_exists($manifestPath)) {
                $content = file_get_contents($manifestPath);
                if ($content !== false) {
                    $manifest = json_decode($content, true);
                    if (is_array($manifest)) {
                        /** @var array<string, mixed> $manifest */
                        $this->themeManifests[$theme] = $manifest;
                    } else {
                        $this->themeManifests[$theme] = [];
                    }
                }
            }
        }
    }

    /**
     * Get theme manifest.
     *
     * @return array<string, mixed>|null
     */
    private function getThemeManifest(string $theme): ?array
    {
        return $this->themeManifests[$theme] ?? null;
    }

    /**
     * Get default theme configuration.
     *
     * @return array<string, mixed>
     */
    private function getDefaultThemeConfig(string $theme): array
    {
        return [
            'name'        => ucfirst($theme),
            'description' => "HDM Boot {$theme} theme",
            'version'     => '1.0.0',
            'author'      => 'HDM Boot',
            'stack'       => $this->getThemeStack($theme),
            'features'    => [
                'responsive' => true,
                'dark_mode'  => false,
                'animations' => true,
            ],
        ];
    }

    /**
     * Get theme technology stack.
     *
     * @return array<string>
     */
    private function getThemeStack(string $theme): array
    {
        $stacks = [
            'default'   => ['Tailwind CSS', 'GSAP', 'Alpine.js', 'Vite'],
            'bootstrap' => ['Bootstrap', 'jQuery', 'Vite'],
            'minimal'   => ['Pure CSS', 'Vanilla JS'],
        ];

        return $stacks[$theme] ?? ['Unknown'];
    }
}
