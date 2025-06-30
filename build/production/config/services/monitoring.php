<?php

declare(strict_types=1);

use HdmBoot\Modules\Core\Monitoring\Actions\StatusAction;

return [
    StatusAction::class => \DI\autowire()
        ->constructorParameter('settings', \DI\get('settings')),
];
