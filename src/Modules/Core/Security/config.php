<?php

declare(strict_types=1);

use DI\Container;
use HdmBoot\Modules\Core\Security\Actions\LoginAction;
use HdmBoot\Modules\Core\Security\Actions\LogoutAction;
use HdmBoot\Modules\Core\Security\Actions\MeAction;
use HdmBoot\Modules\Core\Security\Actions\RefreshTokenAction;
use HdmBoot\Modules\Core\Security\Actions\Web\LoginPageAction;
use HdmBoot\Modules\Core\Security\Actions\Web\LoginSubmitAction;
use HdmBoot\Modules\Core\Security\Actions\Web\LogoutAction as WebLogoutAction;
use HdmBoot\Modules\Core\Security\Infrastructure\Middleware\UserAuthenticationMiddleware;
use HdmBoot\Modules\Core\Security\Middleware\AuthenticationMiddleware;
use HdmBoot\Modules\Core\Security\Middleware\AuthorizationMiddleware;
use HdmBoot\Modules\Core\Security\Services\AuthenticationService;
use HdmBoot\Modules\Core\Security\Services\AuthenticationValidator;
use HdmBoot\Modules\Core\Security\Services\AuthorizationService;
use HdmBoot\Modules\Core\Session\Services\CsrfService;
use HdmBoot\Modules\Core\Session\Services\SessionService;
use HdmBoot\Modules\Core\Security\Services\JwtService;
use HdmBoot\Modules\Core\Security\Services\SecurityLoginChecker;
use HdmBoot\Modules\Core\Template\Infrastructure\Services\TemplateRenderer;
use HdmBoot\Modules\Core\User\Services\UserService;
use ResponsiveSk\Slim4Session\SessionInterface;
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
            /** @var array<string, mixed> $settings */
            $settings = $c->get('settings');

            // Safe access to settings with defaults
            /** @var array<string, mixed> $securitySettings */
            $securitySettings = is_array($settings['security'] ?? null) ? $settings['security'] : [];

            // Extract JWT secret with proper type checking
            $jwtSecretFromSettings = $securitySettings['jwt_secret'] ?? null;
            $jwtSecretFromEnv = $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production';
            $jwtSecret = is_string($jwtSecretFromSettings)
                ? $jwtSecretFromSettings
                : (is_string($jwtSecretFromEnv) ? $jwtSecretFromEnv : 'default-secret-change-in-production');

            // Extract JWT expiry with proper type checking
            $jwtExpiryFromSettings = $securitySettings['jwt_expiry'] ?? null;
            $jwtExpiryFromEnv = $_ENV['JWT_EXPIRY'] ?? '3600';
            $jwtExpiry = is_int($jwtExpiryFromSettings)
                ? $jwtExpiryFromSettings
                : (is_numeric($jwtExpiryFromEnv) ? (int) $jwtExpiryFromEnv : 3600);

            return new JwtService(
                secret: $jwtSecret,
                expirySeconds: $jwtExpiry
            );
        },

        // Security Login Checker
        SecurityLoginChecker::class => function (Container $c): SecurityLoginChecker {
            /** @var PDO $pdo */
            $pdo = $c->get(PDO::class);

            return new SecurityLoginChecker($pdo);
        },

        // Authorization Service
        AuthorizationService::class => function (Container $c): AuthorizationService {
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new AuthorizationService($logger);
        },

        // Authentication Service
        AuthenticationService::class => function (Container $c): AuthenticationService {
            /** @var UserService $userService */
            $userService = $c->get(UserService::class);
            /** @var JwtService $jwtService */
            $jwtService = $c->get(JwtService::class);
            /** @var SecurityLoginChecker $securityChecker */
            $securityChecker = $c->get(SecurityLoginChecker::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new AuthenticationService(
                userService: $userService,
                jwtService: $jwtService,
                securityChecker: $securityChecker,
                logger: $logger
            );
        },

        // Note: AuthenticationMiddleware moved to bottom to avoid duplicate key

        // Actions
        LoginAction::class => function (Container $c): LoginAction {
            /** @var AuthenticationService $authService */
            $authService = $c->get(AuthenticationService::class);
            /** @var AuthenticationValidator $validator */
            $validator = $c->get(AuthenticationValidator::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);
            /** @var LoggerInterface $securityLogger */
            $securityLogger = $c->get('logger.security');

            return new LoginAction($authService, $validator, $logger, $securityLogger);
        },

        LogoutAction::class => function (Container $c): LogoutAction {
            /** @var AuthenticationService $authService */
            $authService = $c->get(AuthenticationService::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);
            /** @var LoggerInterface $securityLogger */
            $securityLogger = $c->get('logger.security');

            return new LogoutAction($authService, $logger, $securityLogger);
        },

        RefreshTokenAction::class => function (Container $c): RefreshTokenAction {
            /** @var AuthenticationService $authService */
            $authService = $c->get(AuthenticationService::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);
            /** @var LoggerInterface $securityLogger */
            $securityLogger = $c->get('logger.security');

            return new RefreshTokenAction($authService, $logger, $securityLogger);
        },

        MeAction::class => function (Container $c): MeAction {
            /** @var UserService $userService */
            $userService = $c->get(UserService::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new MeAction($userService, $logger);
        },

        // === WEB ACTIONS ===

        // Login Page Action
        LoginPageAction::class => function (Container $c): LoginPageAction {
            /** @var TemplateRenderer $templateRenderer */
            $templateRenderer = $c->get(TemplateRenderer::class);
            /** @var SessionService $sessionService */
            $sessionService = $c->get(SessionService::class);

            return new LoginPageAction($templateRenderer, $sessionService);
        },

        // Login Submit Action
        LoginSubmitAction::class => function (Container $c): LoginSubmitAction {
            /** @var TemplateRenderer $templateRenderer */
            $templateRenderer = $c->get(TemplateRenderer::class);
            /** @var SessionInterface $session */
            $session = $c->get(SessionInterface::class);
            /** @var CsrfService $csrfService */
            $csrfService = $c->get(CsrfService::class);
            /** @var AuthenticationService $authService */
            $authService = $c->get(AuthenticationService::class);
            /** @var AuthenticationValidator $validator */
            $validator = $c->get(AuthenticationValidator::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);
            /** @var LoggerInterface $securityLogger */
            $securityLogger = $c->get('logger.security');

            return new LoginSubmitAction(
                $templateRenderer,
                $session,
                $authService,
                $validator,
                $logger,
                $securityLogger
            );
        },

        // Web Logout Action
        WebLogoutAction::class => function (Container $c): WebLogoutAction {
            /** @var SessionInterface $session */
            $session = $c->get(SessionInterface::class);
            /** @var CsrfService $csrfService */
            $csrfService = $c->get(CsrfService::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new WebLogoutAction($session, $csrfService, $logger);
        },

        // === MIDDLEWARE ===

        // User Authentication Middleware
        UserAuthenticationMiddleware::class => function (Container $c): UserAuthenticationMiddleware {
            /** @var SessionInterface $session */
            $session = $c->get(SessionInterface::class);
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $c->get(ResponseFactoryInterface::class);
            /** @var UserService $userService */
            $userService = $c->get(UserService::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new UserAuthenticationMiddleware($session, $responseFactory, $userService, $logger);
        },

        // Authentication Middleware - JWT token-based authentication
        AuthenticationMiddleware::class => function (Container $c): AuthenticationMiddleware {
            /** @var AuthenticationService $authService */
            $authService = $c->get(AuthenticationService::class);
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $c->get(ResponseFactoryInterface::class);
            /** @var LoggerInterface $logger */
            $logger = $c->get(LoggerInterface::class);

            return new AuthenticationMiddleware($authService, $responseFactory, $logger);
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
