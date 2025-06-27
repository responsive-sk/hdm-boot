<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Language\Application\Actions\Api\TranslateAction;
use HdmBoot\Modules\Core\Language\Domain\Services\TranslationService;
use HdmBoot\Modules\Core\Language\Infrastructure\Listeners\LocaleChangedListener;
use HdmBoot\Modules\Core\Language\Infrastructure\Middleware\LocaleMiddleware;
use HdmBoot\Modules\Core\Language\Services\LocaleService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/*
 * Language Module Configuration.
 */
return [
    // === MODULE METADATA ===

    'name'        => 'Language',
    'version'     => '1.0.0',
    'description' => 'Internationalization and localization module with translation management and locale switching',
    'author'      => 'HDM Boot Team',
    'license'     => 'MIT',

    // === MODULE DEPENDENCIES ===

    'dependencies' => [
        // No module dependencies - Language is a core module
    ],

    // === MODULE SETTINGS ===

    'settings' => [
        'enabled'            => true,
        'default_locale'     => 'en_US',
        'fallback_locale'    => 'en_US',
        'cache_translations' => true,
        'cache_ttl'          => 3600,
        'auto_detect_locale' => true,
        'supported_locales'  => [
            'en_US' => 'English (United States)',
            'sk_SK' => 'Slovenčina (Slovensko)',
            'cs_CZ' => 'Čeština (Česká republika)',
            'de_DE' => 'Deutsch (Deutschland)',
            'fr_FR' => 'Français (France)',
        ],
    ],

    // === SERVICE DEFINITIONS ===

    'services' => [
        // Translation Service
        TranslationService::class => \DI\autowire(),

        // TODO: Implement LanguageSettingsAction
        // LanguageSettingsAction::class => function (Container $container): LanguageSettingsAction {
        //     /** @var LocaleService $localeService */
        //     $localeService = $container->get(LocaleService::class);
        //     /** @var LoggerInterface $logger */
        //     $logger = $container->get(LoggerInterface::class);
        //     return new LanguageSettingsAction($localeService, $logger);
        // },

        // Translate Action
        TranslateAction::class => function (Container $container): TranslateAction {
            /** @var TranslationService $translationService */
            $translationService = $container->get(TranslationService::class);
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);

            return new TranslateAction($translationService, $responseFactory, $logger);
        },

        // Locale Changed Listener
        LocaleChangedListener::class => function (Container $container): LocaleChangedListener {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);

            return new LocaleChangedListener($logger);
        },

        // Locale Middleware
        LocaleMiddleware::class => \DI\autowire(),
    ],

    // === PUBLIC SERVICES ===

    'public_services' => [
        'HdmBoot\Modules\Core\Language\Domain\Contracts\TranslationRepositoryInterface' => TranslationService::class,
    ],

    // === EVENT SYSTEM ===

    'published_events' => [
        'language.locale_changed'      => 'Fired when user changes their locale',
        'language.translation_added'   => 'Fired when a new translation is added',
        'language.translation_updated' => 'Fired when a translation is updated',
        'language.translation_deleted' => 'Fired when a translation is deleted',
    ],

    'event_subscriptions' => [
        'user.created' => [LocaleChangedListener::class, 'handleUserCreated'],
    ],

    // === API ENDPOINTS ===

    'api_endpoints' => [
        'POST /api/translate'            => 'Translate text using translation key',
        'GET /api/language'              => 'Get current language settings',
        'POST /api/language'             => 'Change language settings',
        'GET /api/translations'          => 'List all translations for locale',
        'POST /api/translations'         => 'Add new translation',
        'PUT /api/translations/{key}'    => 'Update translation',
        'DELETE /api/translations/{key}' => 'Delete translation',
    ],

    // === MIDDLEWARE ===

    'middleware' => [
        LocaleMiddleware::class => 'Automatically detects and sets user locale from session, headers, or default',
    ],

    // === PERMISSIONS ===

    'permissions' => [
        'language.read'      => 'Read language settings and translations',
        'language.write'     => 'Change language settings',
        'translation.read'   => 'Read translations',
        'translation.write'  => 'Create and update translations',
        'translation.delete' => 'Delete translations',
        'translation.admin'  => 'Administrative access to translation management',
    ],

    // === DATABASE ===

    'database_tables' => [
        'translations',
        'locales',
        'translation_cache',
    ],

    // === MODULE STATUS ===

    'status' => [
        'implemented' => [
            'Domain Value Objects (Locale, TranslationKey)',
            'Domain Models (Translation)',
            'Domain Services (TranslationService)',
            'Domain Events (LocaleChangedEvent, TranslationAddedEvent)',
            'Application DTOs (TranslateRequest, LanguageSettingsRequest)',
            'Application Commands and Queries',
            'Application Actions (TranslateAction)',
            'Event Listeners (LocaleChangedListener)',
            'Locale Middleware',
            'Complete DDD structure',
        ],

        'planned' => [
            'Translation Repository implementation',
            'Translation caching',
            'Pluralization support',
            'Translation file import/export',
            'Translation management UI',
            'Automatic translation detection',
            'Translation statistics and analytics',
        ],
    ],

    // === ROUTES ===

    'routes' => [
        [
            'method'     => 'POST',
            'pattern'    => '/api/translate',
            'handler'    => TranslateAction::class,
            'middleware' => [LocaleMiddleware::class],
        ],
        // TODO: Implement LanguageSettingsAction routes
        // [
        //     'method'     => 'GET',
        //     'pattern'    => '/api/language',
        //     'handler'    => 'HdmBoot\Modules\Core\Language\Application\Actions\Api\LanguageSettingsAction',
        //     'middleware' => [LocaleMiddleware::class],
        // ],
        // [
        //     'method'     => 'POST',
        //     'pattern'    => '/api/language',
        //     'handler'    => 'HdmBoot\Modules\Core\Language\Application\Actions\Api\LanguageSettingsAction',
        //     'middleware' => [LocaleMiddleware::class],
        // ],
    ],

    // === TRANSLATIONS ===

    'translations' => [
        'en_US' => 'translations/en_US.php',
        'sk_SK' => 'translations/sk_SK.php',
        'cs_CZ' => 'translations/cs_CZ.php',
        'de_DE' => 'translations/de_DE.php',
        'fr_FR' => 'translations/fr_FR.php',
    ],

    // === INITIALIZATION ===

    'initialize' => function (): void {
        // Create translation cache directory using Paths service
        $paths = new \ResponsiveSk\Slim4Paths\Paths(__DIR__ . '/../../..');
        $cacheDir = $paths->path('cache/translations');
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0o755, true);
        }

        // Set default locale if not set
        if (!isset($_SESSION['locale'])) {
            $_SESSION['locale'] = 'en_US';
        }
    },

    // === HEALTH CHECK ===

    'health_check' => function (): array {
        $paths = new \ResponsiveSk\Slim4Paths\Paths(__DIR__ . '/../../..');
        return [
            'translation_cache_writable' => is_writable($paths->path('cache/translations')),
            'default_locale_available'   => isset($_SESSION['locale']),
            'supported_locales_count'    => 5,
            'last_check'                 => date('Y-m-d H:i:s'),
        ];
    },
];
