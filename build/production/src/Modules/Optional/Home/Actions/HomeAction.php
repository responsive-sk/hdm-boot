<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Optional\Home\Actions;

use HdmBoot\SharedKernel\Services\ViewRenderer;
use HdmBoot\SharedKernel\Helpers\ThemeHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Home Action.
 * 
 * Handles home page requests with theme-aware rendering.
 */
class HomeAction
{
    private ViewRenderer $viewRenderer;

    public function __construct()
    {
        $this->viewRenderer = new ViewRenderer();
    }

    /**
     * Home page.
     */
    public function index(): string
    {
        // Get theme info for display
        $themeConfig = ThemeHelper::getThemeConfig();
        $availableThemes = ThemeHelper::getAvailableThemes();
        
        // Sample features data
        $features = [
            [
                'icon' => '🚀',
                'title' => 'Modern Architecture',
                'description' => 'Built with PHP 8.4, Slim Framework, and modern design patterns for optimal performance and maintainability.'
            ],
            [
                'icon' => '🎨',
                'title' => 'Theme System',
                'description' => 'Laravel-style theme structure with per-theme dependencies, Vite builds, and hot reload support.'
            ],
            [
                'icon' => '📝',
                'title' => 'Content Management',
                'description' => 'Orbit-style CMS with file-based storage, Markdown support, and automatic content discovery.'
            ],
            [
                'icon' => '⚡',
                'title' => 'Developer Experience',
                'description' => 'CLI tools, PHPStan level 8, CS Fixer, comprehensive testing, and excellent documentation.'
            ],
            [
                'icon' => '🔧',
                'title' => 'API First',
                'description' => 'RESTful APIs, JSON responses, proper HTTP status codes, and OpenAPI documentation.'
            ],
            [
                'icon' => '🛡️',
                'title' => 'Security & Quality',
                'description' => 'Type safety, input validation, CSRF protection, and production-ready security features.'
            ]
        ];

        // Sample stats
        $stats = [
            ['number' => '100%', 'label' => 'Type Safe'],
            ['number' => '8.4', 'label' => 'PHP Version'],
            ['number' => '< 100ms', 'label' => 'Load Time'],
            ['number' => '95+', 'label' => 'Lighthouse Score']
        ];

        return $this->viewRenderer->render('home.index', [
            'themeConfig' => $themeConfig,
            'availableThemes' => $availableThemes,
            'features' => $features,
            'stats' => $stats,
        ]);
    }

    /**
     * API endpoint for home data.
     */
    public function api(): string
    {
        $data = [
            'success' => true,
            'data' => [
                'framework' => 'HDM Boot',
                'version' => '1.0.0',
                'php_version' => PHP_VERSION,
                'theme' => ThemeHelper::getActiveTheme(),
                'features' => [
                    'theme_system',
                    'content_management', 
                    'api_first',
                    'type_safe',
                    'modern_stack'
                ]
            ]
        ];

        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT) ?: '{"error": "JSON encoding failed"}';
    }
}
