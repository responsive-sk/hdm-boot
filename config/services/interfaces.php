<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Language\Services\LocaleService;
use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use HdmBoot\Modules\Core\Security\Services\AuthorizationService;
use HdmBoot\Modules\Core\Security\Services\JwtService;
use HdmBoot\Modules\Core\Security\Services\SecurityLoginChecker;
use HdmBoot\Modules\Core\Session\Services\SessionService;
use HdmBoot\Modules\Core\User\Repository\SqliteUserRepository;
use HdmBoot\Modules\Core\User\Repository\UserRepositoryInterface;
use HdmBoot\Modules\Core\User\Services\UserService;
use HdmBoot\SharedKernel\Services\DatabaseManager;
use HdmBoot\SharedKernel\Services\TemplateRenderer;
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
        /** @var \PDO $pdo */
        $pdo = $container->get(\PDO::class);

        return new SqliteUserRepository($pdo);
    },

    // === SERVICE INTERFACES ===

    // Session Interface - Map to our enhanced session implementation
    \ResponsiveSk\Slim4Session\SessionInterface::class => function (): \ResponsiveSk\Slim4Session\SessionInterface {
        $environment = $_ENV['APP_ENV'] ?? 'production';

        $sessionConfig = [
            'name'            => $_ENV['SESSION_NAME'] ?? 'boot_session',
            'cookie_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200), // 2 hours
            'cookie_secure'   => ($_ENV['SESSION_COOKIE_SECURE'] ?? 'false') === 'true',
            'cookie_httponly' => ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? 'true') === 'true',
            'cookie_samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax',
            'cache_expire'    => 180,
            'use_strict_mode' => true,
        ];

        return match ($environment) {
            'development' => \ResponsiveSk\Slim4Session\SessionFactory::createForDevelopment($sessionConfig),
            'testing'     => \ResponsiveSk\Slim4Session\SessionFactory::createForTesting(),
            default       => \ResponsiveSk\Slim4Session\SessionFactory::createForProduction($sessionConfig),
        };
    },

    // Odan Session Interface - Map to our enhanced session implementation (backward compatibility)
    \Odan\Session\SessionInterface::class => function (Container $container): \ResponsiveSk\Slim4Session\SessionInterface {
        /** @var \ResponsiveSk\Slim4Session\SessionInterface $session */
        $session = $container->get(\ResponsiveSk\Slim4Session\SessionInterface::class);

        return $session;
    },

    // Note: Session services moved to Session module

    // Logging Interface
    LoggerInterface::class => function (Container $container): LoggerInterface {
        /** @var Paths $paths */
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
    \HdmBoot\Modules\Core\User\Contracts\Services\UserServiceInterface::class => function (Container $container): \HdmBoot\Modules\Core\User\Contracts\Services\UserServiceInterface {
        return $container->get(UserService::class);
    },

    // Security Module Contracts
    \HdmBoot\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface::class => function (Container $container): \HdmBoot\Modules\Core\Security\Contracts\Services\AuthenticationServiceInterface {
        return $container->get(AuthenticationService::class);
    },

    \HdmBoot\Modules\Core\Security\Contracts\Services\AuthorizationServiceInterface::class => function (Container $container): \HdmBoot\Modules\Core\Security\Contracts\Services\AuthorizationServiceInterface {
        return $container->get(AuthorizationService::class);
    },

    // Language Module Contracts
    \HdmBoot\Modules\Core\Language\Contracts\Services\LocaleServiceInterface::class => function (Container $container): \HdmBoot\Modules\Core\Language\Contracts\Services\LocaleServiceInterface {
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
    \HdmBoot\Modules\Core\Security\Domain\Services\AuthenticationDomainService::class => \DI\autowire(),

    // User Domain Services
    \HdmBoot\Modules\Core\User\Domain\Services\UserDomainService::class => \DI\autowire(),

    // === CQRS INFRASTRUCTURE ===

    // Event Dispatcher
    \Psr\EventDispatcher\EventDispatcherInterface::class => function (Container $container): \Psr\EventDispatcher\EventDispatcherInterface {
        return new \HdmBoot\Shared\CQRS\Events\EventDispatcher(
            $container->get(LoggerInterface::class)
        );
    },

    // Command Handlers
    \HdmBoot\Modules\Core\User\Application\Handlers\RegisterUserHandler::class => \DI\autowire(),

    // Query Handlers
    \HdmBoot\Modules\Core\User\Application\Handlers\GetUserProfileHandler::class => \DI\autowire(),

    // === MODULE ISOLATION ===

    // Module Registry
    \HdmBoot\Shared\Contracts\Modules\ModuleRegistry::class => \DI\autowire(),

    // Module Event Bus
    \HdmBoot\Shared\Events\Modules\ModuleEventBus::class => \DI\autowire(),

    // Module Instances
    \HdmBoot\Modules\Core\User\UserModule::class         => \DI\autowire(),
    \HdmBoot\Modules\Core\Security\SecurityModule::class => \DI\autowire(),

    // === ERROR HANDLING ===

    // Error Response Handler (moved to Core module)
    \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class => \DI\autowire(),

    // Error Handler Middleware (moved to Core module)
    \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Middleware\ErrorHandlerMiddleware(
            $container->get(\HdmBoot\Modules\Core\ErrorHandling\Infrastructure\Handlers\ErrorResponseHandler::class),
            $container->get(LoggerInterface::class),
            false // displayErrorDetails - set to true for development
        );
    },

    // === MONITORING & LOGGING ===

    // Note: Specialized loggers are now defined in config/services/logging.php
    // which properly uses Paths configuration for correct log file locations

    // Health Check Manager (moved to Core module)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\HealthChecks\HealthCheckManager::class => \DI\autowire(),

    // Health Check Action (moved to Core module)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\Actions\HealthCheckAction::class => \DI\autowire(),

    // Performance Monitor (moved to Core module)
    \HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor::class => function (Container $container) {
        return new \HdmBoot\Modules\Core\Monitoring\Infrastructure\Metrics\PerformanceMonitor(
            $container->get('performance.logger')
        );
    },

    // === DOCUMENTATION ===

    // Documentation Viewer Action (moved to Core module)
    \HdmBoot\Modules\Core\Documentation\Infrastructure\Actions\DocsViewerAction::class => \DI\autowire(),

    // === MIDDLEWARE AUTO-WIRING ===

    // Language Middleware (moved to Core module)
    \HdmBoot\Modules\Core\Language\Infrastructure\Middleware\LocaleMiddleware::class => \DI\autowire(),

    // Security Middleware (moved to Core module)
    \HdmBoot\Modules\Core\Security\Infrastructure\Middleware\UserAuthenticationMiddleware::class => \DI\autowire(),

    // Authorization Middleware (if it exists)
    \HdmBoot\Modules\Core\Security\Middleware\AuthorizationMiddleware::class => \DI\autowire(),

    // === ACTION AUTO-WIRING ===

    // Security Actions (Application Layer)
    \HdmBoot\Modules\Core\Security\Actions\Web\LoginPageAction::class           => \DI\autowire(),
    \HdmBoot\Modules\Core\Security\Actions\Web\LoginSubmitAction::class         => \DI\autowire(),
    \HdmBoot\Modules\Core\Security\Actions\Web\LogoutAction::class              => \DI\autowire(),
    \HdmBoot\Modules\Core\Security\Application\Actions\LoginSubmitAction::class => \DI\autowire(),

    // User Actions
    \HdmBoot\Modules\Core\User\Actions\Web\ProfilePageAction::class => \DI\autowire(),

    // Language Actions
    \HdmBoot\Modules\Core\Language\Actions\Api\LanguageSettingsAction::class => \DI\autowire(),
    \HdmBoot\Modules\Core\Language\Actions\Api\TranslateAction::class        => \DI\autowire(),
];
