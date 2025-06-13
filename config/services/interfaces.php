<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use MvaBootstrap\Modules\Core\Security\Services\AuthorizationService;
use MvaBootstrap\Modules\Core\Security\Services\JwtService;
use MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker;
use MvaBootstrap\Modules\Core\Session\Services\SessionService;
use MvaBootstrap\Modules\Core\User\Repository\SqliteUserRepository;
use MvaBootstrap\Modules\Core\User\Repository\UserRepositoryInterface;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use MvaBootstrap\SharedKernel\Services\DatabaseManager;
use MvaBootstrap\SharedKernel\Services\TemplateRenderer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use ResponsiveSk\Slim4Paths\Paths;
use Slim\Views\PhpRenderer;

/*
 * Interface-based Dependency Injection Bindings.
 *
 * This file defines automatic bindings for interfaces to their implementations,
 * following proper DI/IoC principles.
 */
return [
    // === REPOSITORY INTERFACES ===
    UserRepositoryInterface::class => function (Container $container): UserRepositoryInterface {
        return new SqliteUserRepository(
            $container->get(\PDO::class)
        );
    },

    // === SERVICE INTERFACES ===

    // Note: Session services moved to Session module

    // Logging Interface
    LoggerInterface::class => function (Container $container): LoggerInterface {
        $paths = $container->get(Paths::class);
        $logPath = $paths->get('logs') . '/app.log';

        $logger = new \Monolog\Logger('app');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logPath, \Monolog\Level::Debug));

        return $logger;
    },

    // HTTP Response Factory
    ResponseFactoryInterface::class => function (): ResponseFactoryInterface {
        return \Slim\Factory\AppFactory::determineResponseFactory();
    },

    // === MODULE CONTRACTS ===

    // User Module Contracts
    \MvaBootstrap\Modules\Core\User\Contracts\Services\UserServiceInterface::class => function (Container $container): \MvaBootstrap\Modules\Core\User\Contracts\Services\UserServiceInterface {
        return $container->get(UserService::class);
    },

    // Security Module Contracts
    \MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface {
        return $container->get(AuthenticationService::class);
    },

    \MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthorizationServiceInterface::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Contracts\Services\AuthorizationServiceInterface {
        return $container->get(AuthorizationService::class);
    },

    // Language Module Contracts
    \MvaBootstrap\Modules\Core\Language\Contracts\Services\LocaleServiceInterface::class => function (Container $container): \MvaBootstrap\Modules\Core\Language\Contracts\Services\LocaleServiceInterface {
        return $container->get(LocaleService::class);
    },

    // === CONCRETE SERVICE BINDINGS ===

    // Core Services (auto-wired through constructor injection)
    UserService::class           => \DI\autowire(),
    AuthenticationService::class => \DI\autowire(),
    AuthorizationService::class  => \DI\autowire(),
    JwtService::class            => \DI\autowire(),
    SecurityLoginChecker::class  => \DI\autowire(),
    SessionService::class        => \DI\autowire(),
    LocaleService::class         => \DI\autowire(),
    DatabaseManager::class       => \DI\autowire(),
    TemplateRenderer::class      => \DI\autowire(),
    PhpRenderer::class           => \DI\autowire(),

    // === DOMAIN SERVICES ===

    // Security Domain Services
    \MvaBootstrap\Modules\Core\Security\Domain\Services\AuthenticationDomainService::class => \DI\autowire(),

    // User Domain Services
    \MvaBootstrap\Modules\Core\User\Domain\Services\UserDomainService::class => \DI\autowire(),

    // === CQRS INFRASTRUCTURE ===

    // Event Dispatcher
    \Psr\EventDispatcher\EventDispatcherInterface::class => function (Container $container): \Psr\EventDispatcher\EventDispatcherInterface {
        return new \MvaBootstrap\Shared\CQRS\Events\EventDispatcher(
            $container->get(LoggerInterface::class)
        );
    },

    // Command Handlers
    \MvaBootstrap\Modules\Core\User\Application\Handlers\RegisterUserHandler::class => \DI\autowire(),

    // Query Handlers
    \MvaBootstrap\Modules\Core\User\Application\Handlers\GetUserProfileHandler::class => \DI\autowire(),

    // === MODULE ISOLATION ===

    // Module Registry
    \MvaBootstrap\Shared\Contracts\Modules\ModuleRegistry::class => \DI\autowire(),

    // Module Event Bus
    \MvaBootstrap\Shared\Events\Modules\ModuleEventBus::class => \DI\autowire(),

    // Module Instances
    \MvaBootstrap\Modules\Core\User\UserModule::class         => \DI\autowire(),
    \MvaBootstrap\Modules\Core\Security\SecurityModule::class => \DI\autowire(),

    // === ERROR HANDLING ===

    // Error Response Handler (moved to Core module)
    \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class => \DI\autowire(),

    // Error Handler Middleware (moved to Core module)
    \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware::class => function (Container $container) {
        return new \MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware(
            $container->get(\MvaBootstrap\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class),
            $container->get(LoggerInterface::class),
            false // displayErrorDetails - set to true for development
        );
    },

    // === MONITORING & LOGGING ===

    // Note: Specialized loggers are now defined in config/services/logging.php
    // which properly uses Paths configuration for correct log file locations

    // Health Check Manager (moved to Core module)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager::class => \DI\autowire(),

    // Health Check Action (moved to Core module)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Actions\HealthCheckAction::class => \DI\autowire(),

    // Performance Monitor (moved to Core module)
    \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor::class => function (Container $container) {
        return new \MvaBootstrap\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor(
            $container->get('performance.logger')
        );
    },

    // === DOCUMENTATION ===

    // Documentation Viewer Action (moved to Core module)
    \MvaBootstrap\Modules\Core\Documentation\Infrastructure\Actions\DocsViewerAction::class => \DI\autowire(),

    // === MIDDLEWARE AUTO-WIRING ===

    // Language Middleware (moved to Core module)
    \MvaBootstrap\Modules\Core\Language\Infrastructure\Middleware\LocaleMiddleware::class => \DI\autowire(),

    // Security Middleware (moved to Core module)
    \MvaBootstrap\Modules\Core\Security\Infrastructure\Middleware\UserAuthenticationMiddleware::class => \DI\autowire(),

    // Authorization Middleware (if it exists)
    \MvaBootstrap\Modules\Core\Security\Middleware\AuthorizationMiddleware::class => \DI\autowire(),

    // === ACTION AUTO-WIRING ===

    // Security Actions (Application Layer)
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction::class           => \DI\autowire(),
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction::class         => \DI\autowire(),
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction::class              => \DI\autowire(),
    \MvaBootstrap\Modules\Core\Security\Application\Actions\LoginSubmitAction::class => \DI\autowire(),

    // User Actions
    \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction::class => \DI\autowire(),

    // Language Actions
    \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction::class => \DI\autowire(),
    \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction::class        => \DI\autowire(),
];
