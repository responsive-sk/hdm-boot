<?php

declare(strict_types=1);

// PHP Configuration for Shared Hosting
// These settings work even when php_value in .htaccess is not supported
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('max_input_time', '60');
ini_set('post_max_size', '32M');
ini_set('upload_max_filesize', '32M');

// Error handling - disable display_errors in production
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../var/logs/php_errors.log');

// Ensure error log directory exists
$logDir = __DIR__ . '/../var/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Import classes before try block
use HdmBoot\Boot\App;

try {
    require_once __DIR__ . '/../vendor/autoload.php';

    (new App())->run();
} catch (Throwable $e) {
    // Log the error
    error_log("Fatal error in index.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Show user-friendly error
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><title>Application Error</title></head><body>";
    echo "<h1>Application Error</h1>";
    echo "<p>The application encountered an error. Please check the logs.</p>";
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    echo "</body></html>";
}
