<?php

declare(strict_types=1);

/**
 * Blog Module Test Runner.
 *
 * Uses responsive-sk/slim4-paths for safe path handling
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use ResponsiveSk\Slim4Paths\Paths;

try {
    // Initialize paths from project root
    $projectRoot = dirname(__DIR__, 4);
    $pathsService = Paths::fromHere(__DIR__, 4);
    $paths = new Paths($projectRoot, [
        'vendor' => $projectRoot . '/vendor',
        'tests'  => __DIR__ . '/tests',
        'logs'   => $pathsService->path('logs'),
    ]);

    // Get PHPUnit binary path safely
    $vendorDir = $paths->getPath($projectRoot, 'vendor');
    $phpunitBin = $paths->getPath($vendorDir, 'bin/phpunit');
    $testsDir = __DIR__ . '/tests';
    $configFile = $paths->getPath($testsDir, 'phpunit.xml');

    // Verify paths exist
    if (!file_exists($phpunitBin)) {
        throw new RuntimeException("PHPUnit binary not found at: {$phpunitBin}");
    }

    if (!file_exists($configFile)) {
        throw new RuntimeException("PHPUnit config not found at: {$configFile}");
    }

    // Build command
    $command = sprintf(
        'cd %s && %s --configuration %s',
        escapeshellarg($testsDir),
        escapeshellarg($phpunitBin),
        escapeshellarg($configFile)
    );

    echo "🧪 Running Blog Module Tests...\n";
    echo "📁 Tests directory: {$testsDir}\n";
    echo "⚙️  Config file: {$configFile}\n";
    echo "🔧 Command: {$command}\n\n";

    // Execute tests
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    // Display results
    foreach ($output as $line) {
        echo $line . "\n";
    }

    // Exit with same code as PHPUnit
    exit($returnCode);
} catch (Exception $e) {
    echo '❌ Error running tests: ' . $e->getMessage() . "\n";
    exit(1);
}
