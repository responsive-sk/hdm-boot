<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Helpers;

use HdmBoot\SharedKernel\Services\ThemeManager;

/**
 * Theme Helper.
 *
 * Provides convenient methods for theme-related functionality in templates.
 */
class ThemeHelper
{
    private static ?ThemeManager $themeManager = null;

    /**
     * Get theme manager instance.
     */
    private static function getThemeManager(): ThemeManager
    {
        if (self::$themeManager === null) {
            self::$themeManager = new ThemeManager();
        }

        return self::$themeManager;
    }

    /**
     * Render theme assets (CSS and JS) for HTML head.
     */
    public static function renderAssets(?string $theme = null): string
    {
        return self::getThemeManager()->renderThemeAssets($theme);
    }

    /**
     * Get theme CSS files.
     *
     * @return array<string>
     */
    public static function getCssFiles(?string $theme = null): array
    {
        $assets = self::getThemeManager()->getThemeAssets($theme);

        return $assets['css'];
    }

    /**
     * Get theme JS files.
     *
     * @return array<string>
     */
    public static function getJsFiles(?string $theme = null): array
    {
        $assets = self::getThemeManager()->getThemeAssets($theme);

        return $assets['js'];
    }

    /**
     * Get active theme name.
     */
    public static function getActiveTheme(): string
    {
        return self::getThemeManager()->getActiveTheme();
    }

    /**
     * Get theme configuration.
     *
     * @return array<string, mixed>
     */
    public static function getThemeConfig(?string $theme = null): array
    {
        return self::getThemeManager()->getThemeConfig($theme);
    }

    /**
     * Check if theme is available.
     */
    public static function isThemeAvailable(string $theme): bool
    {
        return self::getThemeManager()->isThemeAvailable($theme);
    }

    /**
     * Get all available themes.
     *
     * @return array<string>
     */
    public static function getAvailableThemes(): array
    {
        return self::getThemeManager()->getAvailableThemes();
    }

    /**
     * Generate theme CSS link tag.
     */
    public static function cssLink(?string $theme = null): string
    {
        $cssFiles = self::getCssFiles($theme);
        $html = '';

        foreach ($cssFiles as $css) {
            $html .= "<link rel=\"stylesheet\" href=\"{$css}\">\n";
        }

        return $html;
    }

    /**
     * Generate theme JS script tag.
     */
    public static function jsScript(?string $theme = null): string
    {
        $jsFiles = self::getJsFiles($theme);
        $html = '';

        foreach ($jsFiles as $js) {
            $html .= "<script type=\"module\" src=\"{$js}\"></script>\n";
        }

        return $html;
    }

    /**
     * Get theme body class.
     */
    public static function getBodyClass(?string $theme = null): string
    {
        $theme ??= self::getActiveTheme();

        return "theme-{$theme}";
    }

    /**
     * Get theme data attributes for HTML tag.
     */
    public static function getHtmlAttributes(?string $theme = null): string
    {
        $theme ??= self::getActiveTheme();
        $config = self::getThemeConfig($theme);

        $versionRaw = $config['version'] ?? '1.0.0';
        $version = is_string($versionRaw) ? $versionRaw : '1.0.0';

        $attributes = [
            "data-theme=\"{$theme}\"",
            "data-theme-version=\"{$version}\"",
        ];

        $features = $config['features'] ?? [];
        if (is_array($features) && isset($features['dark_mode']) && $features['dark_mode']) {
            $attributes[] = 'data-theme-dark-mode="true"';
        }

        return implode(' ', $attributes);
    }

    /**
     * Generate theme meta tags.
     */
    public static function getMetaTags(?string $theme = null): string
    {
        $config = self::getThemeConfig($theme);
        $html = '';

        // Theme meta
        $nameRaw = $config['name'] ?? 'Default Theme';
        $name = is_string($nameRaw) ? $nameRaw : 'Default Theme';

        $versionRaw = $config['version'] ?? '1.0.0';
        $version = is_string($versionRaw) ? $versionRaw : '1.0.0';

        $authorRaw = $config['author'] ?? 'HDM Boot Team';
        $author = is_string($authorRaw) ? $authorRaw : 'HDM Boot Team';

        $html .= "<meta name=\"theme-name\" content=\"{$name}\">\n";
        $html .= "<meta name=\"theme-version\" content=\"{$version}\">\n";
        $html .= "<meta name=\"theme-author\" content=\"{$author}\">\n";

        // Responsive meta
        $features = $config['features'] ?? [];
        if (is_array($features) && isset($features['responsive']) && $features['responsive']) {
            $html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        }

        // Color scheme meta
        if (is_array($features) && isset($features['dark_mode']) && $features['dark_mode']) {
            $html .= "<meta name=\"color-scheme\" content=\"light dark\">\n";
        } else {
            $html .= "<meta name=\"color-scheme\" content=\"light\">\n";
        }

        return $html;
    }

    /**
     * Get theme color for meta theme-color.
     */
    public static function getThemeColor(?string $theme = null): string
    {
        $config = self::getThemeConfig($theme);

        // Try to get primary color from config
        $colors = $config['colors'] ?? [];
        if (is_array($colors) && isset($colors['primary']) && is_array($colors['primary']) && isset($colors['primary']['500'])) {
            $color = $colors['primary']['500'];
            return is_string($color) ? $color : '#3b82f6';
        }

        // Fallback colors by theme
        $fallbackColors = [
            'default'   => '#3b82f6',
            'bootstrap' => '#0d6efd',
            'minimal'   => '#000000',
        ];

        $theme ??= self::getActiveTheme();

        return $fallbackColors[$theme] ?? '#3b82f6';
    }

    /**
     * Generate complete HTML head section for theme.
     */
    public static function renderHead(string $title = 'HDM Boot', ?string $theme = null): string
    {
        $themeColor = self::getThemeColor($theme);
        $metaTags = self::getMetaTags($theme);
        $assets = self::renderAssets($theme);

        return <<<HTML
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <meta name="theme-color" content="{$themeColor}">
            {$metaTags}
            {$assets}
            HTML;
    }

    /**
     * Check if current theme supports feature.
     */
    public static function supportsFeature(string $feature, ?string $theme = null): bool
    {
        $config = self::getThemeConfig($theme);
        $features = $config['features'] ?? [];

        return is_array($features) && isset($features[$feature]) && $features[$feature];
    }

    /**
     * Get theme stack information.
     *
     * @return array<string>
     */
    public static function getThemeStack(?string $theme = null): array
    {
        $config = self::getThemeConfig($theme);
        $stack = $config['stack'] ?? [];

        if (!is_array($stack)) {
            return [];
        }

        // Ensure all items are strings
        $stringStack = [];
        foreach ($stack as $item) {
            if (is_string($item)) {
                $stringStack[] = $item;
            }
        }

        return $stringStack;
    }

    /**
     * Generate theme switcher HTML.
     */
    public static function renderThemeSwitcher(): string
    {
        $themes = self::getAvailableThemes();
        $activeTheme = self::getActiveTheme();

        if (count($themes) <= 1) {
            return '';
        }

        $html = '<div class="theme-switcher">';
        $html .= '<label for="theme-select">Theme:</label>';
        $html .= '<select id="theme-select" onchange="switchTheme(this.value)">';

        foreach ($themes as $theme) {
            $selected = $theme === $activeTheme ? ' selected' : '';
            $config = self::getThemeConfig($theme);
            $nameRaw = $config['name'] ?? ucfirst($theme);
            $name = is_string($nameRaw) ? $nameRaw : ucfirst($theme);
            $html .= "<option value=\"{$theme}\"{$selected}>{$name}</option>";
        }

        $html .= '</select>';
        $html .= '</div>';

        // Add JavaScript for theme switching
        $html .= <<<JS
            <script>
            function switchTheme(theme) {
                // Set cookie
                document.cookie = `hdm_boot_theme=\${theme}; path=/; max-age=2592000`; // 30 days
                
                // Reload page to apply new theme
                window.location.reload();
            }
            </script>
            JS;

        return $html;
    }

    /**
     * Get theme-specific CSS custom properties.
     */
    public static function getCssCustomProperties(?string $theme = null): string
    {
        $config = self::getThemeConfig($theme);
        $css = ':root {';

        // Add colors as CSS custom properties
        $colors = $config['colors'] ?? [];
        if (is_array($colors)) {
            foreach ($colors as $colorName => $colorValue) {
                if (!is_string($colorName)) {
                    continue;
                }

                if (is_array($colorValue)) {
                    foreach ($colorValue as $shade => $value) {
                        if (is_string($shade) && is_string($value)) {
                            $css .= "--color-{$colorName}-{$shade}: {$value};";
                        }
                    }
                } elseif (is_string($colorValue)) {
                    $css .= "--color-{$colorName}: {$colorValue};";
                }
            }
        }

        $css .= '}';

        return $css;
    }
}
