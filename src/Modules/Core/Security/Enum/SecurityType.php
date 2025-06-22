<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Enum;

/**
 * Security Type Enumeration
 *
 * Defines different types of security events and violations.
 */
enum SecurityType: string
{
    case USER_EMAIL = 'user_email';
    case USER_LOGIN = 'user_login';
    case GLOBAL_EMAIL = 'global_email';
    case GLOBAL_LOGIN = 'global_login';
    case GLOBAL_REQUESTS = 'global_requests';
    case BRUTE_FORCE = 'brute_force';
    case INVALID_TOKEN = 'invalid_token';
    case PERMISSION_DENIED = 'permission_denied';
    case CSRF_VIOLATION = 'csrf_violation';
    case PATH_TRAVERSAL = 'path_traversal';
    case INJECTION_ATTEMPT = 'injection_attempt';

    /**
     * Get human-readable description of the security type.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER_EMAIL => 'User email-based security event',
            self::USER_LOGIN => 'User login-based security event',
            self::GLOBAL_EMAIL => 'Global email-based security event',
            self::GLOBAL_LOGIN => 'Global login-based security event',
            self::GLOBAL_REQUESTS => 'Global request-based security event',
            self::BRUTE_FORCE => 'Brute force attack attempt',
            self::INVALID_TOKEN => 'Invalid authentication token',
            self::PERMISSION_DENIED => 'Permission denied access attempt',
            self::CSRF_VIOLATION => 'Cross-Site Request Forgery violation',
            self::PATH_TRAVERSAL => 'Path traversal attack attempt',
            self::INJECTION_ATTEMPT => 'Code injection attack attempt',
        };
    }

    /**
     * Get severity level of the security type.
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::USER_EMAIL, self::USER_LOGIN => 'medium',
            self::GLOBAL_EMAIL, self::GLOBAL_LOGIN, self::GLOBAL_REQUESTS => 'high',
            self::BRUTE_FORCE, self::INJECTION_ATTEMPT, self::PATH_TRAVERSAL => 'critical',
            self::INVALID_TOKEN, self::PERMISSION_DENIED, self::CSRF_VIOLATION => 'high',
        };
    }

    /**
     * Check if this security type requires immediate action.
     */
    public function requiresImmediateAction(): bool
    {
        return match ($this) {
            self::BRUTE_FORCE, self::INJECTION_ATTEMPT, self::PATH_TRAVERSAL => true,
            default => false,
        };
    }
}
