#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Blog Module Test Runner.
 *
 * Uses responsive-sk/slim4-paths for safe path handling
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use ResponsiveSk\Slim4Paths\Paths;

// Initialize Paths from project root (4 levels up from module)
$projectRoot = dirname(dirname(dirname(dirname(__DIR__))));
$paths = new Paths($projectRoot);

// Get safe paths
$rootDir = $paths->base();
$moduleDir = __DIR__;
$phpunitBin = $paths->path('vendor/bin/phpunit');
$phpunitConfig = $moduleDir . '/phpunit.xml';

echo "🧪 HDM Boot Blog Module Test Runner\n";
echo "=====================================\n\n";

echo "📁 Paths:\n";
echo "  Root: {$rootDir}\n";
echo "  Module: {$moduleDir}\n";
echo "  PHPUnit: {$phpunitBin}\n";
echo "  Config: {$phpunitConfig}\n\n";

// Validate paths
if (!file_exists($phpunitBin)) {
    echo "❌ PHPUnit binary not found: {$phpunitBin}\n";
    exit(1);
}

if (!file_exists($phpunitConfig)) {
    echo "❌ PHPUnit config not found: {$phpunitConfig}\n";
    exit(1);
}

echo "🚀 Running Blog Module Tests...\n\n";

// Change to root directory for proper autoloading
chdir($rootDir);

// Build PHPUnit command
$command = escapeshellarg($phpunitBin) . ' --configuration=' . escapeshellarg($phpunitConfig);

// Add coverage if requested
if (in_array('--coverage', $argv, true)) {
    $coverageDir = $moduleDir . '/coverage';
    $command .= ' --coverage-html=' . escapeshellarg($coverageDir);
    echo "📊 Coverage report will be generated in: {$coverageDir}\n\n";
}

// Execute PHPUnit
$returnCode = 0;
passthru($command, $returnCode);

echo "\n";

if ($returnCode === 0) {
    echo "✅ Blog Module Tests: PASSED\n";
} else {
    echo "❌ Blog Module Tests: FAILED (exit code: {$returnCode})\n";
}

exit($returnCode);
