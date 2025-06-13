<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Session\Infrastructure\Middleware\SessionStartMiddleware;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
use MvaBootstrap\Modules\Core\Session\Services\SessionService;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Session Module Configuration.
 *
 * Provides session management, CSRF protection, and session persistence services.
 */
return [
    // Service definitions for DI container
    'services' => [
        // === SESSION CORE SERVICES ===

        // Session Interface - Main session service
        SessionInterface::class => function (): SessionInterface {
            $sessionOptions = [
                'name'            => $_ENV['SESSION_NAME'] ?? 'boot_session',
                'lifetime'        => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 hours
                'save_path'       => null,  // Use default save path
                'domain'          => null,  // Use default domain (localhost)
                'secure'          => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
                'httponly'        => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
                'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
                'cache_limiter'   => 'nocache',  // Prevent caching issues
            ];

            return new PhpSession($sessionOptions);
        },

        // Session Manager Interface (same as SessionInterface for PhpSession)
        SessionManagerInterface::class => function (Container $container): SessionManagerInterface {
            return $container->get(SessionInterface::class);
        },

        // === SESSION SERVICES ===

        // Session Service - User session management
        SessionService::class => function (Container $container): SessionService {
            return new SessionService(
                $container->get(SessionInterface::class)
            );
        },

        // CSRF Service - Cross-Site Request Forgery protection
        CsrfService::class => function (Container $container): CsrfService {
            return new CsrfService(
                $container->get(SessionInterface::class)
            );
        },

        // === MIDDLEWARE ===

        // Session Start Middleware - Automatic session initialization
        SessionStartMiddleware::class => function (Container $container): SessionStartMiddleware {
            return new SessionStartMiddleware(
                $container->get(SessionInterface::class),
                $container->get(LoggerInterface::class)
            );
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
