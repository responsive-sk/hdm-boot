<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Session;

/**
 * Session Factory
 *
 * Factory for creating session instances with proper configuration.
 */
final class SessionFactory
{
    /**
     * Create session manager with default configuration.
     * 
     * @param array<string, mixed> $config
     */
    public static function create(array $config = []): SessionInterface
    {
        $defaultConfig = [
            'name' => 'app_session',
            'cache_expire' => 180,
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax',
            'use_cookies' => true,
            'use_only_cookies' => true,
            'use_strict_mode' => true,
        ];

        $config = array_merge($defaultConfig, $config);

        // Validate configuration
        self::validateConfig($config);

        // Configure PHP session settings
        foreach ($config as $key => $value) {
            if (str_starts_with($key, 'cookie_') || in_array($key, ['name', 'cache_expire', 'use_cookies', 'use_only_cookies', 'use_strict_mode'])) {
                // Safe casting for ini_set
                if (is_string($value)) {
                    $valueString = $value;
                } elseif (is_bool($value)) {
                    $valueString = $value ? '1' : '0';
                } elseif (is_int($value)) {
                    $valueString = (string) $value;
                } else {
                    $valueString = '';
                }
                ini_set("session.{$key}", $valueString);
            }
        }

        // Create our session manager
        return new SessionManager();
    }

    /**
     * Create session manager with custom configuration.
     *
     * @param array<string, mixed> $customConfig
     */
    public static function createWithConfig(array $customConfig): SessionInterface
    {
        return self::create($customConfig);
    }

    /**
     * Create session manager for testing.
     */
    public static function createForTesting(): SessionInterface
    {
        $config = [
            'name' => 'test_session',
            'cookie_secure' => false,
            'use_cookies' => false,
        ];

        return self::create($config);
    }

    /**
     * Create session manager for production.
     * 
     * @param array<string, mixed> $config
     */
    public static function createForProduction(array $config = []): SessionInterface
    {
        $productionConfig = [
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
            'cache_expire' => 180,
        ];

        $config = array_merge($productionConfig, $config);

        return self::create($config);
    }

    /**
     * Create session manager for development.
     * 
     * @param array<string, mixed> $config
     */
    public static function createForDevelopment(array $config = []): SessionInterface
    {
        $developmentConfig = [
            'cookie_secure' => false,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => false,
        ];

        $config = array_merge($developmentConfig, $config);

        return self::create($config);
    }

    /**
     * Validate session configuration.
     *
     * @param array<string, mixed> $config
     * @throws \InvalidArgumentException
     */
    private static function validateConfig(array $config): void
    {
        // Validate session name
        if (isset($config['name']) && !is_string($config['name'])) {
            throw new \InvalidArgumentException('Session name must be a string');
        }

        if (isset($config['name']) && !preg_match('/^[a-zA-Z0-9_]+$/', $config['name'])) {
            throw new \InvalidArgumentException('Session name contains invalid characters');
        }

        // Validate SameSite values
        if (isset($config['cookie_samesite'])) {
            $validSameSite = ['Strict', 'Lax', 'None'];
            if (!in_array($config['cookie_samesite'], $validSameSite, true)) {
                throw new \InvalidArgumentException('Invalid SameSite value. Must be: Strict, Lax, or None');
            }
        }

        // Validate cache_expire
        if (isset($config['cache_expire']) && (!is_int($config['cache_expire']) || $config['cache_expire'] < 0)) {
            throw new \InvalidArgumentException('cache_expire must be a non-negative integer');
        }

        // Security warning for insecure settings
        if (isset($_SERVER['HTTPS']) && isset($config['cookie_secure']) && !$config['cookie_secure']) {
            error_log('WARNING: Session cookie_secure is false on HTTPS connection');
        }
    }
}
