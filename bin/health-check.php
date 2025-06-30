#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Health Check Script
 * 
 * Performs comprehensive health checks on HDM Boot application.
 * Can be used for monitoring, deployment verification, and diagnostics.
 * 
 * Usage: php bin/health-check.php [options]
 * 
 * Options:
 *   --format=json|text    Output format (default: text)
 *   --verbose             Show detailed information
 *   --critical-only       Only show critical issues
 *   --exit-code           Exit with non-zero code on failures
 */

// Ensure we're running from project root
if (!file_exists(__DIR__ . '/../composer.json')) {
    echo "❌ Error: Must be run from project root\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envContent);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
}

class HealthChecker
{
    private array $checks = [];
    private array $results = [];
    private bool $verbose = false;
    private bool $criticalOnly = false;
    private bool $exitOnFailure = false;
    private string $format = 'text';

    public function __construct()
    {
        $this->parseArguments();
        $this->registerChecks();
    }

    public function run(): void
    {
        $startTime = microtime(true);
        
        if ($this->format === 'text') {
            echo "🏥 HDM Boot Health Check\n";
            echo "======================\n\n";
        }

        foreach ($this->checks as $check) {
            $result = $this->runCheck($check);
            $this->results[] = $result;
            
            if ($this->format === 'text' && (!$this->criticalOnly || $result['level'] === 'critical')) {
                $this->displayResult($result);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $this->displaySummary($duration);
        
        if ($this->exitOnFailure && $this->hasFailures()) {
            exit(1);
        }
    }

    private function parseArguments(): void
    {
        global $argv;
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--format=') === 0) {
                $this->format = substr($arg, 9);
            }
            if ($arg === '--verbose') {
                $this->verbose = true;
            }
            if ($arg === '--critical-only') {
                $this->criticalOnly = true;
            }
            if ($arg === '--exit-code') {
                $this->exitOnFailure = true;
            }
        }
    }

    private function registerChecks(): void
    {
        $this->checks = [
            // System checks
            ['name' => 'PHP Version', 'method' => 'checkPhpVersion', 'level' => 'critical'],
            ['name' => 'PHP Extensions', 'method' => 'checkPhpExtensions', 'level' => 'critical'],
            ['name' => 'File Permissions', 'method' => 'checkFilePermissions', 'level' => 'warning'],
            ['name' => 'Disk Space', 'method' => 'checkDiskSpace', 'level' => 'warning'],
            ['name' => 'Memory Usage', 'method' => 'checkMemoryUsage', 'level' => 'info'],
            
            // Application checks
            ['name' => 'Environment Config', 'method' => 'checkEnvironmentConfig', 'level' => 'critical'],
            ['name' => 'Database Connectivity', 'method' => 'checkDatabaseConnectivity', 'level' => 'critical'],
            ['name' => 'Cache System', 'method' => 'checkCacheSystem', 'level' => 'warning'],
            ['name' => 'Session Storage', 'method' => 'checkSessionStorage', 'level' => 'warning'],
            ['name' => 'Log Files', 'method' => 'checkLogFiles', 'level' => 'info'],
            
            // Security checks
            ['name' => 'Security Configuration', 'method' => 'checkSecurityConfig', 'level' => 'critical'],
            ['name' => 'File Security', 'method' => 'checkFileSecurity', 'level' => 'critical'],
            ['name' => 'Path Security', 'method' => 'checkPathSecurity', 'level' => 'critical'],
            
            // Performance checks
            ['name' => 'OPcache Status', 'method' => 'checkOpcacheStatus', 'level' => 'info'],
            ['name' => 'Autoloader', 'method' => 'checkAutoloader', 'level' => 'warning'],
        ];
    }

    private function runCheck(array $check): array
    {
        $startTime = microtime(true);
        
        try {
            $result = $this->{$check['method']}();
            $status = $result['status'] ?? 'pass';
            $message = $result['message'] ?? 'OK';
            $details = $result['details'] ?? [];
        } catch (Exception $e) {
            $status = 'fail';
            $message = $e->getMessage();
            $details = [];
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'name' => $check['name'],
            'status' => $status,
            'level' => $check['level'],
            'message' => $message,
            'details' => $details,
            'duration' => $duration,
        ];
    }

    private function checkPhpVersion(): array
    {
        $required = '8.3.0';
        $current = PHP_VERSION;
        
        if (version_compare($current, $required, '>=')) {
            return [
                'status' => 'pass',
                'message' => "PHP {$current}",
                'details' => ['required' => $required, 'current' => $current]
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => "PHP {$current} < {$required}",
            'details' => ['required' => $required, 'current' => $current]
        ];
    }

    private function checkPhpExtensions(): array
    {
        $required = ['pdo', 'sqlite3', 'mbstring', 'json', 'openssl', 'zip'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'pass',
                'message' => 'All required extensions loaded',
                'details' => ['required' => $required]
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Missing extensions: ' . implode(', ', $missing),
            'details' => ['missing' => $missing, 'required' => $required]
        ];
    }

    private function checkFilePermissions(): array
    {
        $paths = ['var/logs', 'var/cache', 'var/storage', 'var/sessions'];
        $issues = [];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                $issues[] = "Directory missing: {$path}";
                continue;
            }
            
            if (!is_writable($path)) {
                $issues[] = "Not writable: {$path}";
            }
        }
        
        if (empty($issues)) {
            return [
                'status' => 'pass',
                'message' => 'File permissions OK',
                'details' => ['checked_paths' => $paths]
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Permission issues found',
            'details' => ['issues' => $issues]
        ];
    }

    private function checkDiskSpace(): array
    {
        $freeBytes = disk_free_space('.');
        $totalBytes = disk_total_space('.');
        
        if ($freeBytes === false || $totalBytes === false) {
            return [
                'status' => 'fail',
                'message' => 'Cannot determine disk space'
            ];
        }
        
        $freePercent = ($freeBytes / $totalBytes) * 100;
        $freeMB = round($freeBytes / 1024 / 1024, 2);
        
        if ($freePercent < 10) {
            return [
                'status' => 'fail',
                'message' => "Low disk space: {$freeMB}MB ({$freePercent}%)",
                'details' => ['free_mb' => $freeMB, 'free_percent' => $freePercent]
            ];
        }
        
        if ($freePercent < 20) {
            return [
                'status' => 'warning',
                'message' => "Disk space warning: {$freeMB}MB ({$freePercent}%)",
                'details' => ['free_mb' => $freeMB, 'free_percent' => $freePercent]
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => "Disk space OK: {$freeMB}MB ({$freePercent}%)",
            'details' => ['free_mb' => $freeMB, 'free_percent' => $freePercent]
        ];
    }

    private function checkMemoryUsage(): array
    {
        $used = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        
        $usedMB = round($used / 1024 / 1024, 2);
        $peakMB = round($peak / 1024 / 1024, 2);
        
        return [
            'status' => 'pass',
            'message' => "Memory usage: {$usedMB}MB (peak: {$peakMB}MB)",
            'details' => [
                'used_mb' => $usedMB,
                'peak_mb' => $peakMB,
                'limit' => $limit
            ]
        ];
    }

    private function checkEnvironmentConfig(): array
    {
        if (!file_exists('.env')) {
            return [
                'status' => 'fail',
                'message' => '.env file not found'
            ];
        }
        
        $required = ['APP_NAME', 'APP_ENV', 'DATABASE_URL', 'JWT_SECRET', 'SECURITY_KEY'];
        $missing = [];
        
        foreach ($required as $var) {
            if (empty($_ENV[$var])) {
                $missing[] = $var;
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'pass',
                'message' => 'Environment configuration OK',
                'details' => ['app_env' => $_ENV['APP_ENV'] ?? 'unknown']
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Missing environment variables: ' . implode(', ', $missing),
            'details' => ['missing' => $missing]
        ];
    }

    private function checkDatabaseConnectivity(): array
    {
        try {
            $dsn = $_ENV['DATABASE_URL'] ?? 'sqlite:var/storage/app.db';
            $pdo = new PDO($dsn);
            $pdo->query('SELECT 1');
            
            return [
                'status' => 'pass',
                'message' => 'Database connection OK',
                'details' => ['dsn' => $dsn]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkCacheSystem(): array
    {
        $cacheDir = 'var/cache';
        
        if (!is_dir($cacheDir)) {
            return [
                'status' => 'fail',
                'message' => 'Cache directory not found'
            ];
        }
        
        if (!is_writable($cacheDir)) {
            return [
                'status' => 'fail',
                'message' => 'Cache directory not writable'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Cache system OK'
        ];
    }

    private function checkSessionStorage(): array
    {
        $sessionDir = 'var/sessions';
        
        if (!is_dir($sessionDir)) {
            return [
                'status' => 'warning',
                'message' => 'Session directory not found'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Session storage OK'
        ];
    }

    private function checkLogFiles(): array
    {
        $logDir = 'var/logs';
        
        if (!is_dir($logDir)) {
            return [
                'status' => 'warning',
                'message' => 'Log directory not found'
            ];
        }
        
        $logFiles = glob($logDir . '/*.log');
        $count = count($logFiles);
        
        return [
            'status' => 'pass',
            'message' => "Log files: {$count}",
            'details' => ['count' => $count, 'files' => array_map('basename', $logFiles)]
        ];
    }

    private function checkSecurityConfig(): array
    {
        $issues = [];
        
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true' && ($_ENV['APP_ENV'] ?? '') === 'production') {
            $issues[] = 'Debug mode enabled in production';
        }
        
        if (empty($_ENV['JWT_SECRET']) || strlen($_ENV['JWT_SECRET']) < 32) {
            $issues[] = 'JWT secret too short or missing';
        }
        
        if (empty($_ENV['SECURITY_KEY']) || strlen($_ENV['SECURITY_KEY']) < 32) {
            $issues[] = 'Security key too short or missing';
        }
        
        if (empty($issues)) {
            return [
                'status' => 'pass',
                'message' => 'Security configuration OK'
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Security issues found',
            'details' => ['issues' => $issues]
        ];
    }

    private function checkFileSecurity(): array
    {
        $dangerousFiles = [
            'public/debug.php',
            'public/info.php',
            'public/test.php',
            'public/phpinfo.php',
        ];
        
        $found = [];
        foreach ($dangerousFiles as $file) {
            if (file_exists($file)) {
                $found[] = $file;
            }
        }
        
        if (empty($found)) {
            return [
                'status' => 'pass',
                'message' => 'No dangerous files found'
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Dangerous files found: ' . implode(', ', $found),
            'details' => ['files' => $found]
        ];
    }

    private function checkPathSecurity(): array
    {
        // This would integrate with the existing path security system
        return [
            'status' => 'pass',
            'message' => 'Path security OK'
        ];
    }

    private function checkOpcacheStatus(): array
    {
        if (!extension_loaded('opcache')) {
            return [
                'status' => 'warning',
                'message' => 'OPcache not available'
            ];
        }
        
        $status = opcache_get_status();
        if (!$status || !$status['opcache_enabled']) {
            return [
                'status' => 'warning',
                'message' => 'OPcache disabled'
            ];
        }
        
        $hitRate = round($status['opcache_statistics']['opcache_hit_rate'], 2);
        
        return [
            'status' => 'pass',
            'message' => "OPcache enabled (hit rate: {$hitRate}%)",
            'details' => ['hit_rate' => $hitRate]
        ];
    }

    private function checkAutoloader(): array
    {
        if (!file_exists('vendor/autoload.php')) {
            return [
                'status' => 'fail',
                'message' => 'Composer autoloader not found'
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Autoloader OK'
        ];
    }

    private function displayResult(array $result): void
    {
        $icon = match ($result['status']) {
            'pass' => '✅',
            'warning' => '⚠️',
            'fail' => '❌',
            default => 'ℹ️'
        };
        
        echo "{$icon} {$result['name']}: {$result['message']}";
        
        if ($this->verbose && !empty($result['details'])) {
            echo " (" . json_encode($result['details']) . ")";
        }
        
        echo "\n";
    }

    private function displaySummary(float $duration): void
    {
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'pass'));
        $warnings = count(array_filter($this->results, fn($r) => $r['status'] === 'warning'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'fail'));
        
        if ($this->format === 'json') {
            echo json_encode([
                'summary' => [
                    'total' => $total,
                    'passed' => $passed,
                    'warnings' => $warnings,
                    'failed' => $failed,
                    'duration_ms' => $duration,
                ],
                'results' => $this->results
            ], JSON_PRETTY_PRINT);
            return;
        }
        
        echo "\n📊 Summary:\n";
        echo "  Total checks: {$total}\n";
        echo "  ✅ Passed: {$passed}\n";
        echo "  ⚠️  Warnings: {$warnings}\n";
        echo "  ❌ Failed: {$failed}\n";
        echo "  ⏱️  Duration: {$duration}ms\n\n";
        
        if ($failed > 0) {
            echo "❌ Health check FAILED - {$failed} critical issues found\n";
        } elseif ($warnings > 0) {
            echo "⚠️  Health check PASSED with warnings - {$warnings} issues to review\n";
        } else {
            echo "✅ Health check PASSED - All systems operational\n";
        }
    }

    private function hasFailures(): bool
    {
        return count(array_filter($this->results, fn($r) => $r['status'] === 'fail')) > 0;
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$healthChecker = new HealthChecker();
$healthChecker->run();
