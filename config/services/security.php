<?php

declare(strict_types=1);

use DI\Container;
use Odan\Session\SessionInterface;

/**
 * Security Services Configuration.
 */
return [
    // CSRF Service
    \MvaBootstrap\Modules\Core\Security\Services\CsrfService::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\CsrfService {
        $session = $container->get(SessionInterface::class);
        return new \MvaBootstrap\Modules\Core\Security\Services\CsrfService($session);
    },

    // Authentication Validator
    \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator::class => function (): \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator {
        return new \MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator();
    },

    // JWT Service
    \MvaBootstrap\Modules\Core\Security\Services\JwtService::class => function (): \MvaBootstrap\Modules\Core\Security\Services\JwtService {
        return new \MvaBootstrap\Modules\Core\Security\Services\JwtService(
            $_ENV['JWT_SECRET'] ?? throw new \RuntimeException('JWT_SECRET environment variable is required'),
            (int) ($_ENV['JWT_EXPIRY'] ?? 3600)
        );
    },

    // Security Login Checker
    \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker {
        return new \MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker(
            $container->get(\PDO::class)
        );
    },
];
