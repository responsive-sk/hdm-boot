<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
use MvaBootstrap\Modules\Core\Template\Application\Actions\RenderTemplateAction;
use MvaBootstrap\Modules\Core\Template\Domain\Contracts\TemplateEngineInterface;
use MvaBootstrap\Modules\Core\Template\Domain\Contracts\TemplateRendererInterface;
use MvaBootstrap\Modules\Core\Template\Domain\Services\TemplateService;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Engines\PhpTemplateEngine;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Engines\TwigTemplateEngine;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use MvaBootstrap\SharedKernel\Events\ModuleEventBus;
use MvaBootstrap\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Session\SessionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/*
 * Template Module Configuration.
 */
return [
    // === MODULE METADATA ===

    'name'        => 'Template',
    'version'     => '1.0.0',
    'description' => 'Template rendering module with support for multiple template engines and DDD architecture',
    'author'      => 'MVA Bootstrap Team',
    'license'     => 'MIT',

    // === MODULE DEPENDENCIES ===

    'dependencies' => [
        'Session', // For CSRF protection
    ],

    // === MODULE SETTINGS ===

    'settings' => [
        'enabled'           => true,
        'default_engine'    => 'php', // 'php' or 'twig'
        'template_path'     => 'templates', // Will be resolved by TemplateRenderer using Paths service
        'cache_enabled'     => true,
        'cache_path'        => 'var/cache/templates', // Will be resolved by TemplateRenderer using Paths service
        'debug'             => $_ENV['APP_DEBUG'] === 'true',
        'auto_reload'       => $_ENV['APP_DEBUG'] === 'true',
        'strict_variables'  => true,
        'supported_engines' => [
            'php'  => 'Native PHP template engine',
            'twig' => 'Twig template engine (requires twig/twig package)',
        ],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // Template Engine Interface (configurable)
        TemplateEngineInterface::class => function (Container $container): TemplateEngineInterface {
            // Use PHP engine as default (Twig requires additional package)
            $csrfService = $container->get(CsrfService::class);
            $session = $container->get(SessionInterface::class);
            assert($csrfService instanceof CsrfService);
            assert($session instanceof SessionInterface);

            $engine = new PhpTemplateEngine($csrfService, $session);

            // Configure engine with template settings
            $paths = $container->get(Paths::class);
            assert($paths instanceof Paths);
            /** @var array<string, mixed> $engineConfig */
            $engineConfig = [
                'template_path' => $paths->base() . '/templates',
                'cache_enabled' => true,
                'debug'         => false,
            ];
            $engine->configure($engineConfig);

            return $engine;
        },

        // PHP Template Engine
        PhpTemplateEngine::class => function (Container $container): PhpTemplateEngine {
            $moduleManager = $container->get(\MvaBootstrap\SharedKernel\Modules\ModuleManager::class);
            assert($moduleManager instanceof \MvaBootstrap\SharedKernel\Modules\ModuleManager);
            $config = $moduleManager->getModuleConfig('Template');

            $csrfService = $container->get(CsrfService::class);
            $session = $container->get(SessionInterface::class);
            assert($csrfService instanceof CsrfService);
            assert($session instanceof SessionInterface);

            $engine = new PhpTemplateEngine($csrfService, $session);

            $settings = $config['settings'] ?? [];
            $settingsArray = is_array($settings) ? $settings : [];
            /** @var array<string, mixed> $typedSettings */
            $typedSettings = $settingsArray;
            $engine->configure($typedSettings);

            return $engine;
        },

        // Twig Template Engine
        TwigTemplateEngine::class => function (Container $container): TwigTemplateEngine {
            $moduleManager = $container->get(\MvaBootstrap\SharedKernel\Modules\ModuleManager::class);
            assert($moduleManager instanceof \MvaBootstrap\SharedKernel\Modules\ModuleManager);
            $config = $moduleManager->getModuleConfig('Template');

            $engine = new TwigTemplateEngine();
            $settings = $config['settings'] ?? [];
            $settingsArray = is_array($settings) ? $settings : [];
            /** @var array<string, mixed> $typedSettings */
            $typedSettings = $settingsArray;
            $engine->configure($typedSettings);

            return $engine;
        },

        // Template Service (Domain Service)
        TemplateService::class => function (Container $container): TemplateService {
            $templateEngine = $container->get(TemplateEngineInterface::class);
            $eventBus = $container->get(ModuleEventBus::class);
            $logger = $container->get(LoggerInterface::class);

            assert($templateEngine instanceof TemplateEngineInterface);
            assert($eventBus instanceof ModuleEventBus);
            assert($logger instanceof LoggerInterface);

            return new TemplateService($templateEngine, $eventBus, $logger);
        },

        // Template Renderer Interface
        TemplateRendererInterface::class => function (Container $container): TemplateRendererInterface {
            $templateService = $container->get(TemplateService::class);
            assert($templateService instanceof TemplateService);
            return $templateService;
        },

        // Legacy Template Renderer (for backward compatibility)
        TemplateRenderer::class => function (Container $container): TemplateRenderer {
            $paths = $container->get(Paths::class);
            $csrfService = $container->get(CsrfService::class);
            $session = $container->get(SessionInterface::class);

            assert($paths instanceof Paths);
            assert($csrfService instanceof CsrfService);
            assert($session instanceof SessionInterface);

            return new TemplateRenderer(
                $paths->templates(),
                $csrfService,
                $session,
                $paths
            );
        },

        // Render Template Action
        RenderTemplateAction::class => function (Container $container): RenderTemplateAction {
            $templateService = $container->get(TemplateService::class);
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            $logger = $container->get(LoggerInterface::class);

            assert($templateService instanceof TemplateService);
            assert($responseFactory instanceof ResponseFactoryInterface);
            assert($logger instanceof LoggerInterface);

            return new RenderTemplateAction($templateService, $responseFactory, $logger);
        },
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        TemplateRendererInterface::class => TemplateService::class,
        TemplateEngineInterface::class   => PhpTemplateEngine::class,
    ],

    // === EVENT SYSTEM ===

    'published_events' => [
        'template.rendered'      => 'Fired when a template is successfully rendered',
        'template.error'         => 'Fired when template rendering fails',
        'template.cache_cleared' => 'Fired when template cache is cleared',
    ],

    'event_subscriptions' => [
        // No external event subscriptions currently
    ],

    // === API ENDPOINTS ===

    'api_endpoints' => [
        'POST /api/template/render'  => 'Render template with provided data',
        'GET /api/template/info'     => 'Get template engine information',
        'DELETE /api/template/cache' => 'Clear template cache',
    ],

    // === MIDDLEWARE ===

    'middleware' => [
        // No specific middleware currently
    ],

    // === PERMISSIONS ===

    'permissions' => [
        'template.render'      => 'Render templates',
        'template.admin'       => 'Administrative access to template system',
        'template.cache.clear' => 'Clear template cache',
        'template.debug'       => 'Access template debugging information',
    ],

    // === DATABASE ===

    'database_tables' => [
        // No database tables - templates are file-based
    ],

    // === MODULE STATUS ===

    'status' => [
        'implemented' => [
            'Complete DDD architecture (Domain, Application, Infrastructure)',
            'Domain Value Objects (TemplateName, TemplateData)',
            'Domain Services (TemplateService)',
            'Domain Contracts (TemplateRendererInterface, TemplateEngineInterface)',
            'Domain Events (TemplateRenderedEvent)',
            'Application DTOs (RenderTemplateRequest)',
            'Application Actions (RenderTemplateAction)',
            'Infrastructure Engines (PhpTemplateEngine, TwigTemplateEngine)',
            'Legacy TemplateRenderer (backward compatibility)',
            'Event-driven architecture integration',
            'Configurable template engines',
            'Security integration (CSRF, session)',
        ],

        'planned' => [
            'Template caching optimization',
            'Template inheritance system',
            'Template debugging tools',
            'Template performance metrics',
            'Template asset management',
            'Template hot reloading',
            'Template linting and validation',
            'Template marketplace integration',
        ],
    ],

    // === ROUTES ===

    'routes' => [
        [
            'method'     => 'POST',
            'pattern'    => '/api/template/render',
            'handler'    => RenderTemplateAction::class,
            'middleware' => [],
            'name'       => 'template.render',
        ],
    ],

    // === INITIALIZATION ===

    'initialize' => function (): void {
        // Create template directories using PathsFactory
        $paths = PathsFactory::create();

        $directories = [
            $paths->templates(),
            $paths->cache() . '/templates',
            $paths->cache() . '/twig',
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0o755, true);
            }
        }
    },

    // === HEALTH CHECK ===

    'health_check' => function (): array {
        $paths = PathsFactory::create();
        $templatesDir = $paths->templates();
        $cacheDir = $paths->cache();

        $health = [
            'template_directory_exists'   => is_dir($templatesDir),
            'template_directory_readable' => is_readable($templatesDir),
            'cache_directory_writable'    => is_writable($cacheDir),
            'php_engine_available'        => true,
            'twig_engine_available'       => class_exists('\Twig\Environment'),
            'last_check'                  => date('Y-m-d H:i:s'),
        ];

        // Check template files
        $templateCount = 0;
        if (is_dir($templatesDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($templatesDir)
            );
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile() && in_array($file->getExtension(), ['php', 'phtml', 'twig'])) {
                    ++$templateCount;
                }
            }
        }

        $health['template_files_count'] = $templateCount;

        return $health;
    },
];
