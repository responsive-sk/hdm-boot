<?php
// Debug file for shared hosting troubleshooting

// PHP Configuration
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '60');
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

echo "<!DOCTYPE html><html><head><title>HDM Boot Debug</title></head><body>";
echo "<h1>HDM Boot Debug Information</h1>";

echo "<h2>1. PHP Environment</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

echo "<h2>2. File System Check</h2>";
$basePath = dirname(__DIR__);
echo "<p>Base Path: " . $basePath . "</p>";
echo "<p>Current Working Directory: " . getcwd() . "</p>";

$checkPaths = [
    'vendor/autoload.php' => 'Composer Autoload',
    'config/paths.php' => 'Paths Config',
    'var/storage' => 'Storage Directory',
    'var/logs' => 'Logs Directory',
    'src/Boot/App.php' => 'App Class',
];

foreach ($checkPaths as $path => $description) {
    $fullPath = $basePath . '/' . $path;
    $exists = file_exists($fullPath);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅' : '❌';
    echo "<p style='color: $color;'>$status $description: $path</p>";
}

echo "<h2>3. Database Files</h2>";
$dbPath = $basePath . '/var/storage';
if (is_dir($dbPath)) {
    $files = scandir($dbPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'db') {
            $size = filesize($dbPath . '/' . $file);
            echo "<p style='color: green;'>✅ $file (" . number_format($size) . " bytes)</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Storage directory not found</p>";
}

echo "<h2>4. Autoload Test</h2>";
try {
    require_once $basePath . '/vendor/autoload.php';
    echo "<p style='color: green;'>✅ Autoload successful</p>";
    
    // Test class loading
    $testClasses = [
        'HdmBoot\\Boot\\App',
        'ResponsiveSk\\Slim4Paths\\Paths',
        'Slim\\App',
    ];
    
    foreach ($testClasses as $class) {
        if (class_exists($class)) {
            echo "<p style='color: green;'>✅ Class found: $class</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Class not found: $class</p>";
        }
    }
    
} catch (Throwable $e) {
    echo "<p style='color: red;'>❌ Autoload failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>5. App Initialization Test</h2>";
try {
    if (class_exists('HdmBoot\\Boot\\App')) {
        echo "<p style='color: blue;'>🔄 Attempting to create App instance...</p>";
        
        // Don't run the app, just test creation
        $reflection = new ReflectionClass('HdmBoot\\Boot\\App');
        echo "<p style='color: green;'>✅ App class can be reflected</p>";
        
        if ($reflection->hasMethod('__construct')) {
            echo "<p style='color: green;'>✅ App has constructor</p>";
        }
        
        if ($reflection->hasMethod('run')) {
            echo "<p style='color: green;'>✅ App has run method</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ App class not available</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color: red;'>❌ App test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>6. Environment Variables</h2>";
$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    echo "<p style='color: green;'>✅ .env file exists</p>";
} else {
    echo "<p style='color: orange;'>⚠️ .env file not found (using defaults)</p>";
}

echo "<h2>7. Permissions Check</h2>";
$checkDirs = ['var/storage', 'var/logs', 'var/cache', 'var/sessions'];
foreach ($checkDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (is_dir($fullPath)) {
        $writable = is_writable($fullPath);
        $color = $writable ? 'green' : 'red';
        $status = $writable ? '✅' : '❌';
        echo "<p style='color: $color;'>$status $dir is " . ($writable ? 'writable' : 'not writable') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ $dir directory missing</p>";
    }
}

echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>If all checks pass, try accessing the main application</li>";
echo "<li>If errors occur, check the logs in var/logs/</li>";
echo "<li>For FastCGI errors, contact your hosting provider about PHP limits</li>";
echo "</ul>";

echo "</body></html>";
