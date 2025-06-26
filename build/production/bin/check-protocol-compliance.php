#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Protocol Compliance Checker.
 * 
 * Validates codebase against HDM Boot Protocol v2.0 requirements.
 * Reports violations and provides remediation guidance.
 */

echo "🔍 HDM Boot Protocol Compliance Checker\n";
echo "======================================\n\n";

$violations = [];
$warnings = [];
$passed = [];

// Check 1: Forbidden "admin" terminology
echo "🚫 Checking for forbidden 'admin' terminology...\n";

$adminFiles = [];
$srcDir = __DIR__ . '/../src';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Check for admin terminology (case insensitive)
        if (preg_match('/\b(admin|administrator)\b/i', $content)) {
            $lines = explode("\n", $content);
            $violatingLines = [];
            
            foreach ($lines as $lineNum => $line) {
                if (preg_match('/\b(admin|administrator)\b/i', $line)) {
                    $violatingLines[] = ($lineNum + 1) . ': ' . trim($line);
                }
            }
            
            $adminFiles[] = [
                'file' => str_replace($srcDir . '/', '', $file->getPathname()),
                'lines' => $violatingLines
            ];
        }
    }
}

if (!empty($adminFiles)) {
    $violations[] = [
        'rule' => 'PILLAR II: Forbidden "Admin" Terminology',
        'severity' => 'CRITICAL',
        'count' => count($adminFiles),
        'files' => $adminFiles
    ];
} else {
    $passed[] = '✅ No forbidden "admin" terminology found';
}

// Check 2: Database file existence
echo "🗄️ Checking three-database architecture...\n";

$requiredDatabases = ['storage/mark.db', 'storage/user.db', 'storage/system.db'];
$missingDatabases = [];

foreach ($requiredDatabases as $db) {
    if (!file_exists(__DIR__ . '/../' . $db)) {
        $missingDatabases[] = $db;
    }
}

if (!empty($missingDatabases)) {
    $violations[] = [
        'rule' => 'PILLAR I: Three-Database Isolation',
        'severity' => 'CRITICAL',
        'message' => 'Missing required databases',
        'missing' => $missingDatabases
    ];
} else {
    $passed[] = '✅ All three databases exist';
}

// Check 3: Secure path usage
echo "🔒 Checking secure path resolution...\n";

$pathViolations = [];
$pathPattern = '/\.\.[\/\\\\]/'; // Look for ../ or ..\

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        if (preg_match($pathPattern, $content)) {
            $lines = explode("\n", $content);
            $violatingLines = [];
            
            foreach ($lines as $lineNum => $line) {
                if (preg_match($pathPattern, $line)) {
                    $violatingLines[] = ($lineNum + 1) . ': ' . trim($line);
                }
            }
            
            $pathViolations[] = [
                'file' => str_replace($srcDir . '/', '', $file->getPathname()),
                'lines' => $violatingLines
            ];
        }
    }
}

if (!empty($pathViolations)) {
    $warnings[] = [
        'rule' => 'PILLAR III: Secure Path Resolution',
        'severity' => 'WARNING',
        'message' => 'Potential path traversal vulnerabilities',
        'files' => $pathViolations
    ];
} else {
    $passed[] = '✅ No obvious path traversal vulnerabilities';
}

// Check 4: Permission management
echo "📁 Checking permission management...\n";

$permissionViolations = [];
$permissionPatterns = [
    '/\bchmod\s*\(/i',
    '/\bmkdir\s*\(/i',
    '/\bfile_put_contents\s*\(/i'
];

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        foreach ($permissionPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // Check if PermissionManager is used in the same file
                if (!preg_match('/PermissionManager/i', $content)) {
                    $permissionViolations[] = str_replace($srcDir . '/', '', $file->getPathname());
                    break;
                }
            }
        }
    }
}

if (!empty($permissionViolations)) {
    $warnings[] = [
        'rule' => 'PILLAR IV: Centralized Permission Management',
        'severity' => 'WARNING',
        'message' => 'Direct file operations without PermissionManager',
        'files' => array_unique($permissionViolations)
    ];
} else {
    $passed[] = '✅ Permission management appears centralized';
}

// Generate Report
echo "\n📋 PROTOCOL COMPLIANCE REPORT\n";
echo "============================\n\n";

echo "🎯 HDM Boot Protocol v2.0 Compliance Check\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Critical Violations
if (!empty($violations)) {
    echo "🚨 CRITICAL VIOLATIONS (" . count($violations) . ")\n";
    echo "===================\n";
    
    foreach ($violations as $violation) {
        echo "❌ {$violation['rule']}\n";
        echo "   Severity: {$violation['severity']}\n";
        
        if (isset($violation['count'])) {
            echo "   Files affected: {$violation['count']}\n";
        }
        
        if (isset($violation['message'])) {
            echo "   Issue: {$violation['message']}\n";
        }
        
        if (isset($violation['files'])) {
            echo "   Files:\n";
            foreach (array_slice($violation['files'], 0, 5) as $file) {
                if (is_array($file)) {
                    echo "     • {$file['file']}\n";
                    foreach (array_slice($file['lines'], 0, 3) as $line) {
                        echo "       {$line}\n";
                    }
                } else {
                    echo "     • {$file}\n";
                }
            }
            if (count($violation['files']) > 5) {
                echo "     ... and " . (count($violation['files']) - 5) . " more files\n";
            }
        }
        
        if (isset($violation['missing'])) {
            foreach ($violation['missing'] as $missing) {
                echo "     • {$missing}\n";
            }
        }
        
        echo "\n";
    }
}

// Warnings
if (!empty($warnings)) {
    echo "⚠️  WARNINGS (" . count($warnings) . ")\n";
    echo "=========\n";
    
    foreach ($warnings as $warning) {
        echo "⚠️  {$warning['rule']}\n";
        echo "   Severity: {$warning['severity']}\n";
        echo "   Issue: {$warning['message']}\n";
        
        if (isset($warning['files'])) {
            echo "   Files: " . count($warning['files']) . "\n";
        }
        
        echo "\n";
    }
}

// Passed Checks
if (!empty($passed)) {
    echo "✅ PASSED CHECKS (" . count($passed) . ")\n";
    echo "=============\n";
    
    foreach ($passed as $check) {
        echo "{$check}\n";
    }
    echo "\n";
}

// Summary
$totalChecks = count($violations) + count($warnings) + count($passed);
$complianceScore = round((count($passed) / $totalChecks) * 100, 1);

echo "📊 COMPLIANCE SUMMARY\n";
echo "====================\n";
echo "Total Checks: {$totalChecks}\n";
echo "Passed: " . count($passed) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "Critical: " . count($violations) . "\n";
echo "Compliance Score: {$complianceScore}%\n\n";

if (count($violations) > 0) {
    echo "🚨 PROTOCOL COMPLIANCE: FAILED\n";
    echo "Critical violations must be fixed before production deployment.\n";
    exit(1);
} elseif (count($warnings) > 0) {
    echo "⚠️  PROTOCOL COMPLIANCE: PARTIAL\n";
    echo "Warnings should be addressed for full compliance.\n";
    exit(2);
} else {
    echo "✅ PROTOCOL COMPLIANCE: PASSED\n";
    echo "Codebase is fully compliant with HDM Boot Protocol v2.0.\n";
    exit(0);
}
