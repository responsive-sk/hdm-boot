<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Set testing environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['DATABASE_URL'] = 'sqlite::memory:';
$_ENV['JWT_SECRET'] = 'test-secret-key-for-testing-only';
$_ENV['JWT_EXPIRY'] = '3600';
$_ENV['SECURITY_THROTTLING_DISABLED'] = 'true';

// Create necessary directories for testing
$testDirs = [
    __DIR__ . '/../var/coverage',
    __DIR__ . '/../var/logs',
    __DIR__ . '/../var/cache',
    __DIR__ . '/../var/storage',
    __DIR__ . '/../var/sessions',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0o755, true);
    }
}

echo "🧪 Test environment initialized\n";
echo "📁 Test directories created\n";
echo '🔧 Environment: ' . ($_ENV['APP_ENV'] ?? 'unknown') . "\n";
echo '🗄️ Database: ' . ($_ENV['DATABASE_URL'] ?? 'unknown') . "\n\n";
