#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot - Environment Validation Script
 * 
 * Validates production environment configuration to prevent deployment issues.
 * 
 * Usage:
 *   php bin/validate-env.php
 *   php bin/validate-env.php --env=prod
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

function validateEnvironment(string $env = 'prod'): array
{
    $errors = [];
    $warnings = [];
    
    // Load environment variables
    try {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (Exception $e) {
        $errors[] = "Failed to load .env file: " . $e->getMessage();
        return ['errors' => $errors, 'warnings' => $warnings];
    }
    
    // Required environment variables
    $required = [
        'APP_ENV' => 'Application environment',
        'APP_NAME' => 'Application name',
        'JWT_SECRET' => 'JWT secret key',
        'DATABASE_URL' => 'Database connection',
    ];
    
    foreach ($required as $var => $description) {
        if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
            $errors[] = "Missing required environment variable: {$var} ({$description})";
        }
    }
    
    // Production-specific validations
    if ($env === 'prod') {
        // APP_DEBUG should be false
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] !== 'false') {
            $errors[] = "APP_DEBUG must be 'false' in production (current: {$_ENV['APP_DEBUG']})";
        }
        
        // APP_ENV should be prod
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== 'prod') {
            $warnings[] = "APP_ENV is not 'prod' (current: {$_ENV['APP_ENV']})";
        }
        
        // JWT_SECRET validation
        if (isset($_ENV['JWT_SECRET'])) {
            $jwtSecret = $_ENV['JWT_SECRET'];
            if (strlen($jwtSecret) < 32) {
                $errors[] = "JWT_SECRET too short for production (current: " . strlen($jwtSecret) . " chars, minimum: 32)";
            }
            if ($jwtSecret === 'your-secret-key-change-in-production' || 
                $jwtSecret === 'dev-secret-key-change-in-production' ||
                str_contains($jwtSecret, 'CHANGE_ME')) {
                $errors[] = "JWT_SECRET contains default/placeholder value - must be changed for production";
            }
        }
        
        // Database validation
        if (isset($_ENV['DATABASE_URL'])) {
            $dbUrl = $_ENV['DATABASE_URL'];
            if (str_contains($dbUrl, 'sqlite:') && str_contains($dbUrl, 'app_dev.db')) {
                $warnings[] = "Using development database in production environment";
            }
        }
    }
    
    // File permissions check
    $criticalPaths = [
        '.env' => 'Environment file',
        'var/' => 'Variable directory',
        'var/logs/' => 'Logs directory',
        'var/cache/' => 'Cache directory',
    ];
    
    foreach ($criticalPaths as $path => $description) {
        if (!file_exists($path)) {
            if ($path === '.env') {
                $errors[] = "Critical file missing: {$path} ({$description})";
            } else {
                $warnings[] = "Directory missing: {$path} ({$description}) - will be created automatically";
            }
        } else {
            // Check permissions
            if ($path === '.env') {
                $perms = fileperms($path) & 0777;
                if ($perms !== 0600 && $perms !== 0644) {
                    $warnings[] = "Insecure permissions on {$path}: " . decoct($perms) . " (recommended: 600)";
                }
            }
        }
    }
    
    // PHP configuration checks
    $phpChecks = [
        'opcache.enable' => 'OPcache should be enabled in production',
        'display_errors' => 'Display errors should be disabled in production',
    ];
    
    foreach ($phpChecks as $setting => $message) {
        $value = ini_get($setting);
        if ($setting === 'opcache.enable' && !$value && $env === 'prod') {
            $warnings[] = $message . " (current: disabled)";
        }
        if ($setting === 'display_errors' && $value && $env === 'prod') {
            $warnings[] = $message . " (current: enabled)";
        }
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

function printResults(array $results): int
{
    $errors = $results['errors'];
    $warnings = $results['warnings'];
    
    echo "\n🔍 HDM Boot Environment Validation\n";
    echo "==================================\n\n";
    
    if (empty($errors) && empty($warnings)) {
        echo "✅ Environment validation passed!\n";
        echo "🚀 Ready for production deployment.\n\n";
        return 0;
    }
    
    if (!empty($errors)) {
        echo "❌ ERRORS (must be fixed):\n";
        foreach ($errors as $error) {
            echo "  • {$error}\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️  WARNINGS (recommended fixes):\n";
        foreach ($warnings as $warning) {
            echo "  • {$warning}\n";
        }
        echo "\n";
    }
    
    if (!empty($errors)) {
        echo "🚨 Fix all errors before deploying to production!\n\n";
        return 1;
    } else {
        echo "✅ No critical errors found.\n";
        echo "⚠️  Consider addressing warnings for optimal security.\n\n";
        return 0;
    }
}

// Parse command line arguments
$env = 'prod';
if (isset($argv[1]) && str_starts_with($argv[1], '--env=')) {
    $env = substr($argv[1], 6);
}

// Validate environment
$results = validateEnvironment($env);
$exitCode = printResults($results);

exit($exitCode);
