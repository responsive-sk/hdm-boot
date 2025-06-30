<?php
// Simple test file for shared hosting debugging

echo "<!DOCTYPE html><html><head><title>HDM Boot Test</title></head><body>";
echo "<h1>HDM Boot Test Page</h1>";

echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>File System</h2>";
$basePath = dirname(__DIR__);
echo "<p>Base Path: " . $basePath . "</p>";
echo "<p>Vendor exists: " . (file_exists($basePath . '/vendor/autoload.php') ? 'YES' : 'NO') . "</p>";
echo "<p>Config exists: " . (file_exists($basePath . '/config') ? 'YES' : 'NO') . "</p>";
echo "<p>Var directory exists: " . (file_exists($basePath . '/var') ? 'YES' : 'NO') . "</p>";

echo "<h2>Database Files</h2>";
$dbPath = $basePath . '/var/storage';
echo "<p>Storage directory: " . $dbPath . "</p>";
echo "<p>Storage exists: " . (is_dir($dbPath) ? 'YES' : 'NO') . "</p>";
if (is_dir($dbPath)) {
    $files = scandir($dbPath);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>" . $file . " (" . filesize($dbPath . '/' . $file) . " bytes)</li>";
        }
    }
    echo "</ul>";
}

echo "<h2>Environment</h2>";
echo "<p>SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";

echo "<h2>Autoload Test</h2>";
try {
    require_once $basePath . '/vendor/autoload.php';
    echo "<p style='color: green;'>✅ Autoload successful</p>";
    
    // Test basic class loading
    if (class_exists('HdmBoot\\Boot\\App')) {
        echo "<p style='color: green;'>✅ App class found</p>";
    } else {
        echo "<p style='color: red;'>❌ App class not found</p>";
    }
    
} catch (Throwable $e) {
    echo "<p style='color: red;'>❌ Autoload failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
