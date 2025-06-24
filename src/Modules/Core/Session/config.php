<?php

/**
 * Session Module Configuration.
 *
 * Provides session management, CSRF protection, and session persistence services.
 */

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Session\Infrastructure\Middleware\SessionStartMiddleware;
use HdmBoot\Modules\Core\Session\Services\CsrfService;
use HdmBoot\Modules\Core\Session\Services\SessionService;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Session\SessionFactory;
use ResponsiveSk\Slim4Session\SessionInterface;

return [
    // Service definitions for DI container
    'services' => [
        // === SESSION CORE SERVICES ===

        // Session Interface - Map to our enhanced session implementation
        SessionInterface::class => function (): SessionInterface {
            $environment = $_ENV['APP_ENV'] ?? 'production';

            $sessionConfig = [
                'name'            => $_ENV['SESSION_NAME'] ?? 'boot_session',
                'cookie_lifetime' => (int) (is_string($_ENV['SESSION_LIFETIME'] ?? null) ? $_ENV['SESSION_LIFETIME'] : '7200'), // 2 hours
                'cookie_secure'   => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
                'cookie_httponly' => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
                'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
                'cache_expire'    => 180,
                'use_strict_mode' => true,
            ];

            return match ($environment) {
                'development' => SessionFactory::createForDevelopment($sessionConfig),
                'testing'     => SessionFactory::createForTesting(),
                default       => SessionFactory::createForProduction($sessionConfig),
            };
        },

        // === SESSION SERVICES ===

        // Session Service - User session management
        SessionService::class => function (Container $container): SessionService {
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return new SessionService($session);
        },

        // CSRF Service - Cross-Site Request Forgery protection
        CsrfService::class => function (Container $container): CsrfService {
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);

            return new CsrfService($session);
        },

        // === MIDDLEWARE ===

        // Session Start Middleware - Automatic session initialization
        SessionStartMiddleware::class => function (Container $container): SessionStartMiddleware {
            /** @var SessionInterface $session */
            $session = $container->get(SessionInterface::class);
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);

            return new SessionStartMiddleware($session, $logger);
        },
    ],

    // Module dependencies
    'dependencies' => [],

    // Module settings
    'settings' => [
        'session' => [
            'name'            => 'boot_session',
            'lifetime'        => 7200, // 2 hours
            'cookie_secure'   => false, // Set to true in production with HTTPS
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cache_limiter'   => 'nocache',
        ],
        'csrf' => [
            'token_length'    => 32,
            'max_tokens'      => 10,
            'session_key'     => 'csrf_tokens',
        ],
    ],

    // Database tables that this module manages
    'database_tables' => [],

    // Routes provided by this module
    'routes' => [],

    // Middleware provided by this module
    'middleware' => [
        SessionStartMiddleware::class => 'Automatic session initialization',
    ],

    // Module status
    'status' => [
        'implemented' => [
            'Session management with PhpSession',
            'CSRF token generation and validation',
            'Session persistence and security',
            'Session start middleware',
            'Configurable session options',
            'Environment-driven configuration',
        ],
        'planned' => [
            'Session storage backends (Redis, Database)',
            'Session encryption',
            'Session analytics and monitoring',
        ],
    ],
];
