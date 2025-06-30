<?php

declare(strict_types=1);

/**
 * Production FTPS Deployment Configuration
 * 
 * Configure your shared hosting FTPS details here.
 * Copy this file and update with your actual credentials.
 */

return [
    // FTPS Server Configuration
    'host' => $_ENV['DEPLOY_HOST'] ?? 'your-hosting-server.com',
    'port' => (int) ($_ENV['DEPLOY_PORT'] ?? 21),
    'username' => $_ENV['DEPLOY_USERNAME'] ?? 'your-username',
    'password' => $_ENV['DEPLOY_PASSWORD'] ?? 'your-password',
    
    // Remote Path Configuration
    'remote_path' => $_ENV['DEPLOY_REMOTE_PATH'] ?? '/public_html',
    
    // Deployment Settings
    'timeout' => 300, // 5 minutes
    'retry_attempts' => 3,
    'verify_url' => $_ENV['DEPLOY_VERIFY_URL'] ?? 'https://your-domain.com/health',
    
    // File Transfer Settings
    'transfer_mode' => FTP_BINARY,
    'passive_mode' => true,
    
    // Backup Settings
    'create_backup' => true,
    'backup_retention_days' => 7,
    
    // Optimization Settings
    'compress_files' => true,
    'minify_assets' => true,
    'optimize_images' => false, // Requires additional tools
    
    // Shared Hosting Specific
    'php_version' => '8.3',
    'memory_limit' => '256M',
    'max_execution_time' => 300,
    
    // Files to exclude from deployment
    'exclude_files' => [
        '.git*',
        '.env.example',
        '.env.dev',
        '.env.testing',
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
        'README.md',
        'CHANGELOG.md',
        'CONTRIBUTING.md',
    ],
    
    // Directories to create on remote server
    'create_directories' => [
        'var/logs',
        'var/cache',
        'var/storage',
        'var/sessions',
        'public/storage',
    ],
    
    // File permissions for shared hosting
    'file_permissions' => [
        'directories' => 0755,
        'files' => 0644,
        'executables' => 0755,
    ],
    
    // Post-deployment commands (if SSH available)
    'post_deploy_commands' => [
        // Most shared hosting doesn't support SSH
        // These would need to be run manually via cPanel/web interface
        // 'php bin/init-all-databases.php',
        // 'php bin/generate-keys.php',
        // 'php bin/validate-env.php production',
    ],
    
    // Health check configuration
    'health_check' => [
        'enabled' => true,
        'url' => $_ENV['DEPLOY_VERIFY_URL'] ?? 'https://your-domain.com/health',
        'timeout' => 30,
        'expected_status' => 200,
        'expected_content' => 'OK', // Optional content check
    ],
    
    // Notification settings
    'notifications' => [
        'email' => [
            'enabled' => false,
            'to' => $_ENV['DEPLOY_NOTIFY_EMAIL'] ?? 'admin@your-domain.com',
            'subject' => 'HDM Boot Deployment Notification',
        ],
        'slack' => [
            'enabled' => false,
            'webhook_url' => $_ENV['SLACK_WEBHOOK_URL'] ?? '',
            'channel' => '#deployments',
        ],
    ],
    
    // Rollback configuration
    'rollback' => [
        'enabled' => true,
        'keep_versions' => 3,
        'backup_path' => '/backups',
    ],
    
    // Shared hosting provider specific settings
    'hosting_provider' => [
        'name' => $_ENV['HOSTING_PROVIDER'] ?? 'generic',
        'control_panel' => 'cpanel', // cpanel, plesk, directadmin
        'php_selector' => true, // If provider supports PHP version selection
        'cron_jobs' => true, // If provider supports cron jobs
        'ssl_support' => true,
        'database_limit' => 5, // Number of databases allowed
    ],
    
    // Environment specific overrides
    'environment_overrides' => [
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'LOG_LEVEL' => 'warning',
        'CACHE_ENABLED' => 'true',
        'SESSION_SECURE' => 'true',
        'SESSION_HTTPONLY' => 'true',
        'SESSION_SAMESITE' => 'strict',
    ],
];
