<?php

declare(strict_types=1);

/**
 * Staging FTPS Deployment Configuration
 * 
 * Configure your staging environment FTPS details here.
 */

return [
    // FTPS Server Configuration
    'host' => $_ENV['STAGING_DEPLOY_HOST'] ?? 'staging.your-hosting-server.com',
    'port' => (int) ($_ENV['STAGING_DEPLOY_PORT'] ?? 21),
    'username' => $_ENV['STAGING_DEPLOY_USERNAME'] ?? 'staging-username',
    'password' => $_ENV['STAGING_DEPLOY_PASSWORD'] ?? 'staging-password',
    
    // Remote Path Configuration
    'remote_path' => $_ENV['STAGING_DEPLOY_REMOTE_PATH'] ?? '/staging',
    
    // Deployment Settings
    'timeout' => 300,
    'retry_attempts' => 3,
    'verify_url' => $_ENV['STAGING_DEPLOY_VERIFY_URL'] ?? 'https://staging.your-domain.com/health',
    
    // File Transfer Settings
    'transfer_mode' => FTP_BINARY,
    'passive_mode' => true,
    
    // Backup Settings
    'create_backup' => false, // Less critical for staging
    'backup_retention_days' => 3,
    
    // Optimization Settings (less aggressive for staging)
    'compress_files' => false,
    'minify_assets' => false,
    'optimize_images' => false,
    
    // Staging Specific
    'php_version' => '8.3',
    'memory_limit' => '256M',
    'max_execution_time' => 300,
    
    // Files to exclude from deployment
    'exclude_files' => [
        '.git*',
        '.env.example',
        '.env.dev',
        '.env.production',
        'tests/',
        'docs/',
        'node_modules/',
        'var/logs/*',
        'var/cache/*',
        'composer.lock',
        'package*.json',
        'webpack.config.js',
        'phpunit.xml',
        'phpstan.neon',
        '.php-cs-fixer.php',
    ],
    
    // Directories to create
    'create_directories' => [
        'var/logs',
        'var/cache',
        'var/storage',
        'var/sessions',
        'public/storage',
    ],
    
    // File permissions
    'file_permissions' => [
        'directories' => 0755,
        'files' => 0644,
        'executables' => 0755,
    ],
    
    // Health check configuration
    'health_check' => [
        'enabled' => true,
        'url' => $_ENV['STAGING_DEPLOY_VERIFY_URL'] ?? 'https://staging.your-domain.com/health',
        'timeout' => 30,
        'expected_status' => 200,
    ],
    
    // Notification settings
    'notifications' => [
        'email' => [
            'enabled' => false,
        ],
        'slack' => [
            'enabled' => false,
        ],
    ],
    
    // Environment specific overrides
    'environment_overrides' => [
        'APP_ENV' => 'staging',
        'APP_DEBUG' => 'false',
        'LOG_LEVEL' => 'info',
        'CACHE_ENABLED' => 'true',
        'SESSION_SECURE' => 'true',
    ],
];
