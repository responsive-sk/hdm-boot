<?php

declare(strict_types=1);

use DI\Container;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;

/**
 * Session Services Configuration.
 */
return [
    // Session Interface
    SessionInterface::class => function (): SessionInterface {
        $sessionOptions = [
            'name' => $_ENV['SESSION_NAME'] ?? 'boot_session',
            'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 hours
            'save_path' => null,  // Use default save path
            'domain' => null,     // Use default domain (localhost)
            'secure' => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
            'httponly' => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
            'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
            'cache_limiter' => 'nocache',  // Prevent caching issues
        ];
        
        // Debug: Log session configuration
        error_log('SessionInterface creation: ' . json_encode($sessionOptions));
        
        return new PhpSession($sessionOptions);
    },

    // Session Manager Interface (same as SessionInterface for PhpSession)
    SessionManagerInterface::class => function (Container $container): SessionManagerInterface {
        return $container->get(SessionInterface::class);
    },

    // Session Service
    \MvaBootstrap\Modules\Core\Security\Services\SessionService::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\SessionService {
        return new \MvaBootstrap\Modules\Core\Security\Services\SessionService(
            $container->get(SessionInterface::class)
        );
    },
];
