#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Theme Management CLI
 * 
 * Manages themes: build, watch, switch, list, create
 * 
 * Usage:
 *   php bin/theme.php list
 *   php bin/theme.php build [theme]
 *   php bin/theme.php watch [theme]
 *   php bin/theme.php switch <theme>
 *   php bin/theme.php create <name>
 */

// Ensure we're running from project root
if (!file_exists(__DIR__ . '/../composer.json')) {
    echo "❌ Error: Must be run from project root\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

use HdmBoot\SharedKernel\Services\ThemeManager;

class ThemeCLI
{
    private ThemeManager $themeManager;
    private array $commands = [
        'list' => 'List all available themes',
        'build' => 'Build theme assets (default: current theme)',
        'watch' => 'Watch theme for changes (default: current theme)',
        'switch' => 'Switch active theme',
        'create' => 'Create new theme',
        'info' => 'Show theme information',
        'assets' => 'Show theme assets',
        'help' => 'Show this help message'
    ];

    public function __construct()
    {
        $this->themeManager = new ThemeManager();
    }

    public function run(array $args): void
    {
        $command = $args[1] ?? 'help';
        $argument = $args[2] ?? null;

        switch ($command) {
            case 'list':
                $this->listThemes();
                break;
            
            case 'build':
                $this->buildTheme($argument);
                break;
            
            case 'watch':
                $this->watchTheme($argument);
                break;
            
            case 'switch':
                $this->switchTheme($argument);
                break;
            
            case 'create':
                $this->createTheme($argument);
                break;
            
            case 'info':
                $this->showThemeInfo($argument);
                break;
            
            case 'assets':
                $this->showThemeAssets($argument);
                break;
            
            case 'help':
            default:
                $this->showHelp();
                break;
        }
    }

    private function listThemes(): void
    {
        echo "🎨 HDM Boot Themes\n";
        echo "==================\n\n";

        $themes = $this->themeManager->getAvailableThemes();
        $activeTheme = $this->themeManager->getActiveTheme();

        foreach ($themes as $theme) {
            $isActive = $theme === $activeTheme;
            $icon = $isActive ? '✅' : '📦';
            $status = $isActive ? ' (active)' : '';
            
            echo "{$icon} {$theme}{$status}\n";
            
            // Show theme info
            $config = $this->themeManager->getThemeConfig($theme);
            echo "   📝 {$config['description']}\n";
            echo "   🔧 " . implode(', ', $config['stack']) . "\n";
            echo "\n";
        }

        echo "💡 Use 'php bin/theme.php switch <theme>' to change active theme\n";
        echo "🔨 Use 'php bin/theme.php build <theme>' to build theme assets\n";
    }

    private function buildTheme(?string $theme): void
    {
        $theme = $theme ?? $this->themeManager->getActiveTheme();
        
        echo "🔨 Building theme: {$theme}\n";
        echo "========================\n\n";

        try {
            $success = $this->themeManager->buildTheme($theme);
            
            if ($success) {
                echo "✅ Theme '{$theme}' built successfully!\n\n";
                
                // Show build output info
                $assets = $this->themeManager->getThemeAssets($theme);
                echo "📦 Generated assets:\n";
                
                foreach ($assets['css'] as $css) {
                    echo "   📄 {$css}\n";
                }
                
                foreach ($assets['js'] as $js) {
                    echo "   📄 {$js}\n";
                }
                
                echo "\n💡 Assets are ready for production!\n";
            } else {
                echo "❌ Failed to build theme '{$theme}'\n";
                exit(1);
            }
        } catch (\Exception $e) {
            echo "❌ Build error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function watchTheme(?string $theme): void
    {
        $theme = $theme ?? $this->themeManager->getActiveTheme();
        
        echo "👀 Watching theme: {$theme}\n";
        echo "========================\n\n";
        echo "🔥 Development server starting...\n";
        echo "💡 Press Ctrl+C to stop\n\n";

        try {
            $this->themeManager->watchTheme($theme);
        } catch (\Exception $e) {
            echo "❌ Watch error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function switchTheme(?string $theme): void
    {
        if (!$theme) {
            echo "❌ Error: Theme name required\n";
            echo "💡 Usage: php bin/theme.php switch <theme>\n";
            exit(1);
        }

        try {
            $this->themeManager->setActiveTheme($theme);
            echo "✅ Switched to theme: {$theme}\n";
            
            // Show theme info
            $config = $this->themeManager->getThemeConfig($theme);
            echo "📝 {$config['description']}\n";
            echo "🔧 " . implode(', ', $config['stack']) . "\n";
            
        } catch (\Exception $e) {
            echo "❌ Switch error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function createTheme(?string $name): void
    {
        if (!$name) {
            echo "❌ Error: Theme name required\n";
            echo "💡 Usage: php bin/theme.php create <name>\n";
            exit(1);
        }

        echo "🎨 Creating new theme: {$name}\n";
        echo "============================\n\n";

        // TODO: Implement theme creation
        echo "⚠️  Theme creation not implemented yet\n";
        echo "💡 For now, copy an existing theme and modify it\n";
    }

    private function showThemeInfo(?string $theme): void
    {
        $theme = $theme ?? $this->themeManager->getActiveTheme();
        
        echo "ℹ️  Theme Information: {$theme}\n";
        echo "==============================\n\n";

        try {
            $config = $this->themeManager->getThemeConfig($theme);
            
            echo "📝 Name: {$config['name']}\n";
            echo "📄 Description: {$config['description']}\n";
            echo "🏷️  Version: {$config['version']}\n";
            echo "👤 Author: {$config['author']}\n";
            echo "\n";
            
            echo "🔧 Technology Stack:\n";
            foreach ($config['stack'] as $tech) {
                echo "   • {$tech}\n";
            }
            echo "\n";
            
            echo "✨ Features:\n";
            foreach ($config['features'] as $feature => $enabled) {
                $icon = $enabled ? '✅' : '❌';
                $featureName = ucwords(str_replace('_', ' ', $feature));
                echo "   {$icon} {$featureName}\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function showThemeAssets(?string $theme): void
    {
        $theme = $theme ?? $this->themeManager->getActiveTheme();
        
        echo "📦 Theme Assets: {$theme}\n";
        echo "========================\n\n";

        try {
            $assets = $this->themeManager->getThemeAssets($theme);
            
            echo "🎨 CSS Files:\n";
            foreach ($assets['css'] as $css) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $css;
                $size = file_exists($path) ? $this->formatFileSize(filesize($path)) : 'N/A';
                echo "   📄 {$css} ({$size})\n";
            }
            echo "\n";
            
            echo "⚡ JavaScript Files:\n";
            foreach ($assets['js'] as $js) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $js;
                $size = file_exists($path) ? $this->formatFileSize(filesize($path)) : 'N/A';
                echo "   📄 {$js} ({$size})\n";
            }
            echo "\n";
            
            echo "🔗 HTML Include:\n";
            echo $this->themeManager->renderThemeAssets($theme);
            
        } catch (\Exception $e) {
            echo "❌ Error: {$e->getMessage()}\n";
            exit(1);
        }
    }

    private function showHelp(): void
    {
        echo "🎨 HDM Boot Theme Management CLI\n";
        echo "================================\n\n";
        
        echo "Usage: php bin/theme.php <command> [arguments]\n\n";
        
        echo "Available commands:\n";
        foreach ($this->commands as $command => $description) {
            echo sprintf("  %-10s %s\n", $command, $description);
        }
        
        echo "\nExamples:\n";
        echo "  php bin/theme.php list\n";
        echo "  php bin/theme.php build default\n";
        echo "  php bin/theme.php watch\n";
        echo "  php bin/theme.php switch bootstrap\n";
        echo "  php bin/theme.php info default\n";
        echo "\n";
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$cli = new ThemeCLI();
$cli->run($argv);
