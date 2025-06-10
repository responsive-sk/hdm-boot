<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Shared\Middleware\LocaleMiddleware;
use MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware;
use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use MvaBootstrap\Modules\Core\Security\Services\SessionService;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware Services Configuration.
 */
return [
    // Locale Middleware
    LocaleMiddleware::class => function (Container $container): LocaleMiddleware {
        return new LocaleMiddleware(
            $container->get(LocaleService::class),
            $container->get(SessionInterface::class),
            $container->get(UserService::class),
            $container->get(LoggerInterface::class)
        );
    },

    // User Authentication Middleware
    UserAuthenticationMiddleware::class => function (Container $container): UserAuthenticationMiddleware {
        return new UserAuthenticationMiddleware(
            $container->get(SessionInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(UserService::class),
            $container->get(LoggerInterface::class)
        );
    },
];
