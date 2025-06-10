<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use Odan\Session\SessionInterface;
use ResponsiveSk\Slim4Paths\Paths;
use Psr\Log\LoggerInterface;

/**
 * Language Services Configuration.
 */
return [
    // Locale Service
    LocaleService::class => function (Container $container): LocaleService {
        return new LocaleService(
            $container->get(Paths::class),
            $container->get(LoggerInterface::class)
        );
    },
];
