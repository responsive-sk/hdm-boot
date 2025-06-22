<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\SharedKernel\Events\EventDispatcherInterface;
use HdmBoot\SharedKernel\Database\DatabaseInterface;
use HdmBoot\Modules\Example\Domain\Repository\ExampleRepositoryInterface;
use HdmBoot\Modules\Example\Infrastructure\Repository\ExampleRepository;
use HdmBoot\Modules\Example\Infrastructure\Services\ExampleService;
use HdmBoot\Modules\Example\Actions\ExampleAction;
use Psr\Container\ContainerInterface;

/**
 * Example Module Services Configuration
 *
 * This file defines the module's services for the DI container.
 *
 * @return array<string, callable(ContainerInterface): object>
 */
return [
    // Actions
    ExampleAction::class => static function (ContainerInterface $container): ExampleAction {
        return new ExampleAction(
            $container->get(ExampleService::class)
        );
    },

    // Services
    ExampleService::class => static function (ContainerInterface $container): ExampleService {
        return new ExampleService(
            $container->get(ExampleRepositoryInterface::class),
            $container->get(EventDispatcherInterface::class)
        );
    },

    // Repositories
    ExampleRepositoryInterface::class => static function (ContainerInterface $container): ExampleRepositoryInterface {
        return new ExampleRepository(
            $container->get(DatabaseInterface::class)
        );
    }
];
