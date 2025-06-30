<?php
// Simple PHP info for shared hosting

// Set basic limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '30');

echo "<h1>PHP Configuration</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";

echo "<h2>Extensions</h2>";
$required = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    $color = $loaded ? 'green' : 'red';
    $status = $loaded ? '✅' : '❌';
    echo "<p style='color: $color;'>$status $ext</p>";
}

echo "<h2>File System</h2>";
$basePath = dirname(__DIR__);
echo "<p>Base: $basePath</p>";
echo "<p>Autoload: " . (file_exists($basePath . '/vendor/autoload.php') ? '✅' : '❌') . "</p>";

// Don't show full phpinfo on production for security
if (isset($_GET['full']) && $_GET['full'] === '1') {
    echo "<hr>";
    phpinfo();
}
