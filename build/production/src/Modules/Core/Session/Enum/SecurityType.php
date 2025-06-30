<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Session\Enum;

/**
 * Security Type Enum for Session Module.
 *
 * Defines security-related types used in session and CSRF operations.
 */
enum SecurityType: string
{
    case GLOBAL_LOGIN = 'global_login';
    case GLOBAL_EMAIL = 'global_email';
    case GLOBAL_REQUESTS = 'global_requests';
    case USER_LOGIN = 'user_login';
    case USER_EMAIL = 'user_email';
    case CSRF_TOKEN_INVALID = 'csrf_token_invalid';
    case SESSION_EXPIRED = 'session_expired';
    case SESSION_INVALID = 'session_invalid';
}
