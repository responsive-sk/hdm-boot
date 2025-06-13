<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Core\Monitoring\Actions\StatusAction;

return [
    StatusAction::class => \DI\autowire()
        ->constructorParameter('settings', \DI\get('settings')),
];
