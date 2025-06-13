<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Modules\Core\Security\Actions\LoginAction;
use MvaBootstrap\Modules\Core\Security\Actions\LogoutAction;
use MvaBootstrap\Modules\Core\Security\Actions\MeAction;
use MvaBootstrap\Modules\Core\Security\Actions\RefreshTokenAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction as WebLogoutAction;
use MvaBootstrap\Modules\Core\Security\Infrastructure\Middleware\UserAuthenticationMiddleware;
use MvaBootstrap\Modules\Core\Security\Middleware\AuthenticationMiddleware;
use MvaBootstrap\Modules\Core\Security\Middleware\AuthorizationMiddleware;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationService;
use MvaBootstrap\Modules\Core\Security\Services\AuthenticationValidator;
use MvaBootstrap\Modules\Core\Security\Services\AuthorizationService;
use MvaBootstrap\Modules\Core\Session\Services\CsrfService;
use MvaBootstrap\Modules\Core\Session\Services\SessionService;
use MvaBootstrap\Modules\Core\Security\Services\JwtService;
use MvaBootstrap\Modules\Core\Security\Services\SecurityLoginChecker;
use MvaBootstrap\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use MvaBootstrap\Modules\Core\User\Services\UserService;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/*
 * Security Module Configuration.
 *
 * Defines services, dependencies, and configuration for the Security module.
 */
return [
    'name'        => 'Security',
    'version'     => '1.0.0',
    'description' => 'Core security module with JWT authentication and authorization',

    // Service definitions for DI container
    'services' => [


        // Authentication Validator
        AuthenticationValidator::class => function (): AuthenticationValidator {
            return new AuthenticationValidator();
        },

        // JWT Service
        JwtService::class => function (Container $c): JwtService {
            $settings = $c->get('settings');

            return new JwtService(
                secret: $settings['security']['jwt_secret'],
                expirySeconds: $settings['security']['jwt_expiry']
            );
        },

        // Security Login Checker
        SecurityLoginChecker::class => function (Container $c): SecurityLoginChecker {
            return new SecurityLoginChecker($c->get(PDO::class));
        },

        // Authorization Service
        AuthorizationService::class => function (Container $c): AuthorizationService {
            return new AuthorizationService($c->get(LoggerInterface::class));
        },

        // Authentication Service
        AuthenticationService::class => function (Container $c): AuthenticationService {
            return new AuthenticationService(
                userService: $c->get(UserService::class),
                jwtService: $c->get(JwtService::class),
                securityChecker: $c->get(SecurityLoginChecker::class),
                logger: $c->get(LoggerInterface::class)
            );
        },

        // Middleware
        AuthenticationMiddleware::class => function (Container $c): AuthenticationMiddleware {
            return new AuthenticationMiddleware($c->get(AuthenticationService::class));
        },

        // Actions
        LoginAction::class => function (Container $c): LoginAction {
            return new LoginAction($c->get(AuthenticationService::class));
        },

        LogoutAction::class => function (Container $c): LogoutAction {
            return new LogoutAction($c->get(AuthenticationService::class));
        },

        RefreshTokenAction::class => function (Container $c): RefreshTokenAction {
            return new RefreshTokenAction($c->get(AuthenticationService::class));
        },

        MeAction::class => function (Container $c): MeAction {
            return new MeAction($c->get(AuthorizationService::class));
        },

        // === WEB ACTIONS ===

        // Login Page Action
        LoginPageAction::class => function (Container $c): LoginPageAction {
            return new LoginPageAction(
                $c->get(TemplateRenderer::class),
                $c->get(SessionService::class),
                $c->get(CsrfService::class)
            );
        },

        // Login Submit Action
        LoginSubmitAction::class => function (Container $c): LoginSubmitAction {
            return new LoginSubmitAction(
                $c->get(TemplateRenderer::class),
                $c->get(SessionInterface::class),
                $c->get(CsrfService::class),
                $c->get(AuthenticationService::class),
                $c->get(AuthenticationValidator::class),
                $c->get(LoggerInterface::class),
                $c->get(LoggerInterface::class)
            );
        },

        // Web Logout Action
        WebLogoutAction::class => function (Container $c): WebLogoutAction {
            return new WebLogoutAction(
                $c->get(SessionInterface::class),
                $c->get(CsrfService::class),
                $c->get(LoggerInterface::class)
            );
        },

        // === MIDDLEWARE ===

        // User Authentication Middleware
        UserAuthenticationMiddleware::class => function (Container $c): UserAuthenticationMiddleware {
            return new UserAuthenticationMiddleware(
                $c->get(SessionInterface::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(UserService::class),
                $c->get(LoggerInterface::class)
            );
        },
    ],

    // Module dependencies
    'dependencies' => [
        'User',    // Requires User module for authentication
        'Session', // Requires Session module for CSRF and session services
    ],

    // Module settings
    'settings' => [
        'jwt' => [
            'algorithm'         => 'HS256',
            'expiry_seconds'    => 3600, // 1 hour
            'refresh_threshold' => 300, // 5 minutes before expiry
        ],
        'throttling' => [
            'user_login_attempts_limit'   => 5,
            'user_login_window_minutes'   => 15,
            'global_login_attempts_limit' => 50,
            'global_login_window_minutes' => 5,
        ],
        'security' => [
            'require_email_verification'  => false,
            'password_reset_expiry_hours' => 1,
            'session_timeout_minutes'     => 120,
        ],
    ],

    // Database tables that this module manages
    'database_tables' => [
        'security_login_attempts',
    ],

    // Permissions defined by this module
    'permissions' => [
        'security.login'            => 'User login access',
        'security.logout'           => 'User logout access',
        'security.refresh'          => 'Token refresh access',
        'admin.security'            => 'Security administration access',
        'admin.security.statistics' => 'View security statistics',
        'admin.security.cleanup'    => 'Clean security logs',
    ],

    // Events that this module can emit
    'events' => [
        'security.login.success'        => 'Fired when user logs in successfully',
        'security.login.failed'         => 'Fired when login attempt fails',
        'security.logout'               => 'Fired when user logs out',
        'security.token.refreshed'      => 'Fired when JWT token is refreshed',
        'security.throttling.triggered' => 'Fired when security throttling is triggered',
        'security.suspicious.activity'  => 'Fired when suspicious activity is detected',
    ],

    // API endpoints provided by this module
    'api_endpoints' => [
        'POST /api/auth/login'               => 'User authentication with JWT token generation',
        'POST /api/auth/logout'              => 'User logout (requires authentication)',
        'POST /api/auth/refresh'             => 'JWT token refresh (requires authentication)',
        'GET /api/auth/me'                   => 'Get current user information (requires authentication)',
        'GET /api/admin/security/statistics' => 'Security statistics (admin only)',
        'POST /api/admin/security/cleanup'   => 'Clean old security logs (admin only)',
        'GET /api/test/auth'                 => 'Authentication test endpoint (dev only)',
    ],

    // Middleware provided by this module
    'middleware' => [
        AuthenticationMiddleware::class => 'JWT token authentication',
        AuthorizationMiddleware::class  => 'Role-based authorization',
    ],

    // Module status
    'status' => [
        'implemented' => [
            'JWT token generation and validation',
            'User authentication with password verification',
            'Security throttling and login attempt tracking',
            'Role-based authorization system',
            'Authentication and authorization middleware',
            'Login, logout, refresh, and me endpoints',
            'Security statistics and monitoring',
            'Integration with User module',
            'Comprehensive error handling',
            'Security exception handling',
        ],
        'planned' => [
            'Token blacklisting for logout',
            'Advanced captcha integration',
            'Two-factor authentication (2FA)',
            'OAuth2 integration',
            'API rate limiting',
            'Advanced security monitoring',
            'Security audit logging',
            'Brute force protection enhancements',
            'Session management improvements',
            'Security policy configuration',
        ],
    ],

    // Security configuration
    'security_config' => [
        'jwt_secret_min_length'        => 32,
        'token_header_name'            => 'Authorization',
        'token_prefix'                 => 'Bearer ',
        'failed_attempts_cleanup_days' => 7,
        'security_log_retention_days'  => 30,
    ],
];
