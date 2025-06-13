<?php

declare(strict_types=1);

/**
 * Language/Localization Configuration.
 *
 * Based on samuelgfeller locale configuration with enterprise enhancements.
 */

return [
    // Default locale settings
    'default_locale'   => $_ENV['DEFAULT_LOCALE'] ?? 'en_US',
    'default_timezone' => $_ENV['DEFAULT_TIMEZONE'] ?? 'Europe/Bratislava',

    // Available locales
    'available_locales' => [
        'en_US' => [
            'name'        => 'English (United States)',
            'native_name' => 'English',
            'flag'        => '🇺🇸',
            'enabled'     => true,
        ],
        'sk_SK' => [
            'name'        => 'Slovak (Slovakia)',
            'native_name' => 'Slovenčina',
            'flag'        => '🇸🇰',
            'enabled'     => ($_ENV['ENABLE_SLOVAK'] ?? 'true') === 'true',
        ],
        'cs_CZ' => [
            'name'        => 'Czech (Czech Republic)',
            'native_name' => 'Čeština',
            'flag'        => '🇨🇿',
            'enabled'     => ($_ENV['ENABLE_CZECH'] ?? 'true') === 'true',
        ],
        'de_DE' => [
            'name'        => 'German (Germany)',
            'native_name' => 'Deutsch',
            'flag'        => '🇩🇪',
            'enabled'     => ($_ENV['ENABLE_GERMAN'] ?? 'false') === 'true',
        ],
        'fr_FR' => [
            'name'        => 'French (France)',
            'native_name' => 'Français',
            'flag'        => '🇫🇷',
            'enabled'     => ($_ENV['ENABLE_FRENCH'] ?? 'false') === 'true',
        ],
        'es_ES' => [
            'name'        => 'Spanish (Spain)',
            'native_name' => 'Español',
            'flag'        => '🇪🇸',
            'enabled'     => ($_ENV['ENABLE_SPANISH'] ?? 'false') === 'true',
        ],
        'it_IT' => [
            'name'        => 'Italian (Italy)',
            'native_name' => 'Italiano',
            'flag'        => '🇮🇹',
            'enabled'     => ($_ENV['ENABLE_ITALIAN'] ?? 'false') === 'true',
        ],
        'pl_PL' => [
            'name'        => 'Polish (Poland)',
            'native_name' => 'Polski',
            'flag'        => '🇵🇱',
            'enabled'     => ($_ENV['ENABLE_POLISH'] ?? 'false') === 'true',
        ],
    ],

    // Translation settings
    'translations' => [
        'path'             => $_ENV['TRANSLATIONS_PATH'] ?? 'resources/translations',
        'domain'           => $_ENV['TRANSLATION_DOMAIN'] ?? 'messages',
        'codeset'          => 'UTF-8',
        'fallback_enabled' => ($_ENV['TRANSLATION_FALLBACK'] ?? 'true') === 'true',
        'cache_enabled'    => ($_ENV['TRANSLATION_CACHE'] ?? 'true') === 'true',
        'cache_ttl'        => (int) ($_ENV['TRANSLATION_CACHE_TTL'] ?? 3600), // 1 hour
    ],

    // Gettext settings
    'gettext' => [
        'enabled'       => ($_ENV['GETTEXT_ENABLED'] ?? 'true') === 'true',
        'require_files' => ($_ENV['GETTEXT_REQUIRE_FILES'] ?? 'false') === 'true',
        'auto_generate' => ($_ENV['GETTEXT_AUTO_GENERATE'] ?? 'false') === 'true',
    ],

    // Date and time formatting
    'formatting' => [
        'date_format'         => $_ENV['DATE_FORMAT'] ?? 'Y-m-d',
        'time_format'         => $_ENV['TIME_FORMAT'] ?? 'H:i:s',
        'datetime_format'     => $_ENV['DATETIME_FORMAT'] ?? 'Y-m-d H:i:s',
        'currency_format'     => $_ENV['CURRENCY_FORMAT'] ?? '€ %s',
        'number_decimals'     => (int) ($_ENV['NUMBER_DECIMALS'] ?? 2),
        'decimal_separator'   => $_ENV['DECIMAL_SEPARATOR'] ?? '.',
        'thousands_separator' => $_ENV['THOUSANDS_SEPARATOR'] ?? ',',
    ],

    // Regional settings
    'regional' => [
        'currency'           => $_ENV['DEFAULT_CURRENCY'] ?? 'EUR',
        'country'            => $_ENV['DEFAULT_COUNTRY'] ?? 'SK',
        'phone_prefix'       => $_ENV['DEFAULT_PHONE_PREFIX'] ?? '+421',
        'postal_code_format' => $_ENV['POSTAL_CODE_FORMAT'] ?? '/^[0-9]{5}$/',
    ],

    // Language detection
    'detection' => [
        'auto_detect'          => ($_ENV['AUTO_DETECT_LANGUAGE'] ?? 'true') === 'true',
        'use_browser_language' => ($_ENV['USE_BROWSER_LANGUAGE'] ?? 'true') === 'true',
        'use_user_preference'  => ($_ENV['USE_USER_PREFERENCE'] ?? 'true') === 'true',
        'use_session'          => ($_ENV['USE_SESSION_LANGUAGE'] ?? 'true') === 'true',
        'use_cookie'           => ($_ENV['USE_COOKIE_LANGUAGE'] ?? 'true') === 'true',
        'cookie_name'          => $_ENV['LANGUAGE_COOKIE_NAME'] ?? 'app_language',
        'cookie_lifetime'      => (int) ($_ENV['LANGUAGE_COOKIE_LIFETIME'] ?? 2592000), // 30 days
    ],

    // API settings
    'api' => [
        'enabled'         => ($_ENV['LANGUAGE_API_ENABLED'] ?? 'true') === 'true',
        'rate_limit'      => (int) ($_ENV['LANGUAGE_API_RATE_LIMIT'] ?? 100), // requests per hour
        'require_auth'    => ($_ENV['LANGUAGE_API_REQUIRE_AUTH'] ?? 'false') === 'true',
        'cache_responses' => ($_ENV['LANGUAGE_API_CACHE'] ?? 'true') === 'true',
    ],

    // Development settings
    'development' => [
        'debug_mode'               => ($_ENV['LANGUAGE_DEBUG'] ?? 'false') === 'true',
        'log_missing_translations' => ($_ENV['LOG_MISSING_TRANSLATIONS'] ?? 'true') === 'true',
        'show_translation_keys'    => ($_ENV['SHOW_TRANSLATION_KEYS'] ?? 'false') === 'true',
        'extract_strings'          => ($_ENV['EXTRACT_TRANSLATION_STRINGS'] ?? 'false') === 'true',
    ],

    // Enterprise features
    'enterprise' => [
        'audit_language_changes'    => ($_ENV['AUDIT_LANGUAGE_CHANGES'] ?? 'true') === 'true',
        'user_language_preferences' => ($_ENV['USER_LANGUAGE_PREFERENCES'] ?? 'true') === 'true',
        'admin_language_management' => ($_ENV['ADMIN_LANGUAGE_MANAGEMENT'] ?? 'true') === 'true',
        'translation_management'    => ($_ENV['TRANSLATION_MANAGEMENT'] ?? 'false') === 'true',
        'professional_translations' => ($_ENV['PROFESSIONAL_TRANSLATIONS'] ?? 'false') === 'true',
    ],
];
