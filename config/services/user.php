<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Shared\Services\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * User Services Configuration.
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
];
