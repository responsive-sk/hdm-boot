<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Shared\Services\TemplateRenderer;
use MvaBootstrap\Modules\Core\Security\Services\CsrfService;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator;
use MvaBootstrap\Modules\Core\Security\Services\SessionService;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use MvaBootstrap\Modules\Core\Language\Services\LocaleService;
use Odan\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\PhpRenderer;

/**
 * Action Services Configuration.
 */
return [
    // Authentication Service
    \MvaBootstrap\Modules\Core\Security\Services\AuthenticationService::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Services\AuthenticationService {
        return new \MvaBootstrap\Modules\Core\Security\Services\AuthenticationService(
            $container->get(UserService::class),
            $container->get(\MvaBootstrap\Modules\Core\Security\Services\JwtService::class),
            $container->get(\MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker::class),
            $container->get(LoggerInterface::class)
        );
    },

    // Login Page Action
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction {
        return new \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction(
            $container->get(TemplateRenderer::class),
            $container->get(SessionService::class),
            $container->get(CsrfService::class)
        );
    },

    // Login Submit Action
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction {
        return new \MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction(
            $container->get(TemplateRenderer::class),
            $container->get(SessionInterface::class),
            $container->get(CsrfService::class),
            $container->get(\MvaBootstrap\Modules\Core\Security\Services\AuthenticationService::class),
            $container->get(AuthenticationValidator::class),
            $container->get(LoggerInterface::class),
            $container->get(LoggerInterface::class)
        );
    },

    // Logout Action
    \MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction::class => function (Container $container): \MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction {
        return new \MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction(
            $container->get(SessionInterface::class),
            $container->get(CsrfService::class),
            $container->get(LoggerInterface::class)
        );
    },

    // Profile Page Action
    \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction::class => function (Container $container): \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction {
        return new \MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction(
            $container->get(PhpRenderer::class),
            $container->get(SessionInterface::class),
            $container->get(UserService::class)
        );
    },

    // Language Settings Action
    \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction::class => function (Container $container): \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction {
        return new \MvaBootstrap\Modules\Core\Language\Actions\Api\LanguageSettingsAction(
            $container->get(LocaleService::class)
        );
    },

    // Translate Action
    \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction::class => function (Container $container): \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction {
        return new \MvaBootstrap\Modules\Core\Language\Actions\Api\TranslateAction(
            $container->get(LocaleService::class)
        );
    },
];
