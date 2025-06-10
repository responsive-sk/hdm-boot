<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use MvaBootstrap\Shared\Services\DatabaseManager;
use Psr\Log\LoggerInterface;
use Slim\Views\PhpRenderer;

/**
 * User Module Configuration.
 */
return [
    // User Repository Interface
    UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
        return new SqliteUserRepository(
            $container->get(\PDO::class)
        );
    },

    // User Service
    UserService::class => function (Container $container): UserService {
        return new UserService(
            $container->get(UserRepositoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    },

    // Profile Page Action
    ProfilePageAction::class => function (Container $container): ProfilePageAction {
        return new ProfilePageAction(
            $container->get(PhpRenderer::class),
            $container->get(\Odan\Session\SessionInterface::class),
            $container->get(UserService::class)
        );
    },
];
