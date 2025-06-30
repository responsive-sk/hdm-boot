<?php
// Ultra-simple test - no dependencies, no autoload, no nothing

echo "Hello World from HDM Boot!<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Memory usage: " . memory_get_usage(true) . " bytes<br>";

// Test basic file operations
$testFile = __DIR__ . '/../var/logs/simple-test.log';
if (file_put_contents($testFile, 'Test: ' . date('Y-m-d H:i:s'))) {
    echo "✅ File write test: SUCCESS<br>";
} else {
    echo "❌ File write test: FAILED<br>";
}

echo "Test completed successfully!";
?>
