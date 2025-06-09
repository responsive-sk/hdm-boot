<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Security\Enum;

/**
 * Security Type Enum.
 *
 * Defines different types of security checks and throttling.
 * Adapted from the original Security module.
 */
enum SecurityType: string
{
    case GLOBAL_LOGIN = 'global_login';
    case GLOBAL_EMAIL = 'global_email';
    case GLOBAL_REQUESTS = 'global_requests';
    case USER_LOGIN = 'user_login'; // User or IP fail
    case USER_EMAIL = 'user_email';
    case API_REQUESTS = 'api_requests'; // New for API throttling
    case JWT_VALIDATION = 'jwt_validation'; // New for JWT security
}
