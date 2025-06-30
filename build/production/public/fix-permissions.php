<?php
// Fix permissions for shared hosting

echo "<!DOCTYPE html><html><head><title>Fix Permissions</title></head><body>";
echo "<h1>HDM Boot - Fix Permissions</h1>";

$basePath = dirname(__DIR__);
echo "<p>Base path: $basePath</p>";

// Directories that need write permissions
$writableDirs = [
    'var',
    'var/storage',
    'var/logs', 
    'var/cache',
    'var/sessions',
    'var/uploads',
    'var/orbit',
];

// Files that need write permissions
$writableFiles = [
    'var/storage/mark.db',
    'var/storage/user.db', 
    'var/storage/system.db',
    'var/logs/app.log',
    'var/logs/error.log',
    'var/logs/security.log',
];

echo "<h2>Setting Directory Permissions (777)</h2>";
foreach ($writableDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (is_dir($fullPath)) {
        $result = chmod($fullPath, 0777);
        $color = $result ? 'green' : 'red';
        $status = $result ? '✅' : '❌';
        echo "<p style='color: $color;'>$status $dir</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $dir (not found)</p>";
    }
}

echo "<h2>Setting File Permissions (666)</h2>";
foreach ($writableFiles as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        $result = chmod($fullPath, 0666);
        $color = $result ? 'green' : 'red';
        $status = $result ? '✅' : '❌';
        echo "<p style='color: $color;'>$status $file</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $file (not found)</p>";
    }
}

echo "<h2>Current Permissions Check</h2>";
$checkPaths = array_merge($writableDirs, $writableFiles);
foreach ($checkPaths as $path) {
    $fullPath = $basePath . '/' . $path;
    if (file_exists($fullPath)) {
        $perms = fileperms($fullPath);
        $octal = substr(sprintf('%o', $perms), -4);
        $readable = is_readable($fullPath) ? '✅' : '❌';
        $writable = is_writable($fullPath) ? '✅' : '❌';
        echo "<p>$path: $octal (R:$readable W:$writable)</p>";
    }
}

echo "<h2>Create Missing Directories</h2>";
foreach ($writableDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (!is_dir($fullPath)) {
        $result = mkdir($fullPath, 0777, true);
        $color = $result ? 'green' : 'red';
        $status = $result ? '✅' : '❌';
        echo "<p style='color: $color;'>$status Created $dir</p>";
    }
}

echo "<h2>Create Missing Log Files</h2>";
$logFiles = ['app.log', 'error.log', 'security.log'];
foreach ($logFiles as $logFile) {
    $fullPath = $basePath . '/var/logs/' . $logFile;
    if (!file_exists($fullPath)) {
        $result = touch($fullPath);
        if ($result) {
            chmod($fullPath, 0666);
        }
        $color = $result ? 'green' : 'red';
        $status = $result ? '✅' : '❌';
        echo "<p style='color: $color;'>$status Created $logFile</p>";
    }
}

echo "<h2>Test Write Access</h2>";
$testFile = $basePath . '/var/logs/permission_test.txt';
$writeTest = file_put_contents($testFile, 'Permission test: ' . date('Y-m-d H:i:s'));
if ($writeTest !== false) {
    echo "<p style='color: green;'>✅ Write test successful</p>";
    unlink($testFile);
} else {
    echo "<p style='color: red;'>❌ Write test failed</p>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>If all permissions are set correctly, try the main application</li>";
echo "<li>If write test failed, contact your hosting provider</li>";
echo "<li>Some shared hosts require 755 for directories and 644 for files</li>";
echo "</ul>";

echo "</body></html>";
