<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Contracts\Events;

/**
 * Security Module Events - Public Event Names.
 *
 * Defines the event names that the Security module publishes
 * for other modules to subscribe to.
 */
final class SecurityModuleEvents
{
    /**
     * Fired when user login attempt is successful.
     */
    public const LOGIN_SUCCESSFUL = 'security.login_successful';

    /**
     * Fired when user login attempt fails.
     */
    public const LOGIN_FAILED = 'security.login_failed';

    /**
     * Fired when user is logged out.
     */
    public const LOGOUT_SUCCESSFUL = 'security.logout_successful';

    /**
     * Fired when JWT token is generated.
     */
    public const TOKEN_GENERATED = 'security.token_generated';

    /**
     * Fired when JWT token validation fails.
     */
    public const TOKEN_VALIDATION_FAILED = 'security.token_validation_failed';

    /**
     * Fired when authorization check fails.
     */
    public const AUTHORIZATION_FAILED = 'security.authorization_failed';

    /**
     * Fired when suspicious activity is detected.
     */
    public const SUSPICIOUS_ACTIVITY = 'security.suspicious_activity';

    /**
     * Fired when rate limit is exceeded.
     */
    public const RATE_LIMIT_EXCEEDED = 'security.rate_limit_exceeded';

    /**
     * Fired when CSRF token validation fails.
     */
    public const CSRF_VALIDATION_FAILED = 'security.csrf_validation_failed';

    /**
     * Get all available event names.
     *
     * @return array<string>
     */
    public static function getAllEvents(): array
    {
        return [
            self::LOGIN_SUCCESSFUL,
            self::LOGIN_FAILED,
            self::LOGOUT_SUCCESSFUL,
            self::TOKEN_GENERATED,
            self::TOKEN_VALIDATION_FAILED,
            self::AUTHORIZATION_FAILED,
            self::SUSPICIOUS_ACTIVITY,
            self::RATE_LIMIT_EXCEEDED,
            self::CSRF_VALIDATION_FAILED,
        ];
    }
}
