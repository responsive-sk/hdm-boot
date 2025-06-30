#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HDM Boot Paths Audit Tool.
 *
 * Comprehensive audit of paths system including:
 * - Configuration validation
 * - Directory structure security
 * - Code usage patterns
 * - Permission analysis
 * - Auto-fix capabilities
 *
 * Usage: php bin/audit-paths.php [--fix] [--verbose] [--security-only]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ResponsiveSk\Slim4Paths\Paths;

echo "🔍 HDM Boot Paths Audit\n";
echo "========================\n\n";

class PathsAuditor
{
    private Paths $paths;
    private array $issues = [];
    private array $hardcodedPaths = [];
    private array $directoryStructure = [];

    public function __construct()
    {
        $this->paths = new Paths(__DIR__ . '/..');
    }

    public function runAudit(): void
    {
        echo "📁 Analyzing directory structure...\n";
        $this->analyzeDirectoryStructure();
        
        echo "🔍 Scanning for hardcoded paths...\n";
        $this->scanForHardcodedPaths();
        
        echo "⚙️ Checking path configurations...\n";
        $this->checkPathConfigurations();
        
        echo "🛡️ Checking path security...\n";
        $this->checkPathSecurity();
        
        $this->generateReport();
    }

    private function analyzeDirectoryStructure(): void
    {
        $directories = [
            'var' => [],
            'storage' => [],
            'cache' => [],
            'logs' => [],
        ];

        // Find all relevant directories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $path = $file->getPathname();
                
                // Skip vendor and .git
                if (strpos($path, 'vendor/') !== false || strpos($path, '.git/') !== false) {
                    continue;
                }

                $dirname = basename($path);
                if (isset($directories[$dirname])) {
                    $directories[$dirname][] = $path;
                }
            }
        }

        $this->directoryStructure = $directories;

        // Report duplicates
        foreach ($directories as $type => $paths) {
            if (count($paths) > 1) {
                $this->issues[] = [
                    'type' => 'duplicate_directories',
                    'severity' => 'high',
                    'message' => "Multiple {$type} directories found",
                    'details' => $paths
                ];
            }
        }
    }

    private function scanForHardcodedPaths(): void
    {
        $patterns = [
            'var/' => 'Hardcoded var/ path',
            'storage/' => 'Hardcoded storage/ path',
            '__DIR__ . \'/../../var' => 'Hardcoded relative var path',
            '__DIR__ . \'/../../storage' => 'Hardcoded relative storage path',
            'var/cache' => 'Hardcoded cache path',
            'var/logs' => 'Hardcoded logs path',
        ];

        $pathsServicePatterns = [
            '$this->paths->path(',
            '$paths->path(',
            '->path(',
            'Paths(',
        ];

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);

            foreach ($patterns as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    // Check if this is actually a Paths service call
                    $lines = $this->findLinesWithPattern($content, $pattern);
                    $isPathsService = false;

                    foreach ($lines as $line) {
                        foreach ($pathsServicePatterns as $pathsPattern) {
                            if (strpos($line['content'], $pathsPattern) !== false) {
                                $isPathsService = true;
                                break 2;
                            }
                        }
                    }

                    // Only report if it's not a Paths service call
                    if (!$isPathsService) {
                        $this->hardcodedPaths[] = [
                            'file' => $file,
                            'pattern' => $pattern,
                            'description' => $description,
                            'lines' => $lines
                        ];
                    }
                }
            }
        }
    }

    private function checkPathConfigurations(): void
    {
        // Check if Paths service is properly configured
        $configFiles = [
            'config/paths.php',
            'config/app.php',
        ];

        foreach ($configFiles as $configFile) {
            if (!file_exists($configFile)) {
                $this->issues[] = [
                    'type' => 'missing_config',
                    'severity' => 'medium',
                    'message' => "Missing path configuration file: {$configFile}",
                    'details' => []
                ];
            }
        }
    }

    private function checkPathSecurity(): void
    {
        $securityChecks = [
            'var' => ['writable' => true, 'web_accessible' => false],
            'storage' => ['writable' => true, 'web_accessible' => false],
            'public' => ['writable' => false, 'web_accessible' => true],
        ];

        foreach ($securityChecks as $dir => $requirements) {
            $path = "./{$dir}";
            if (is_dir($path)) {
                if ($requirements['writable'] && !is_writable($path)) {
                    $this->issues[] = [
                        'type' => 'permission_issue',
                        'severity' => 'high',
                        'message' => "Directory {$dir} should be writable but isn't",
                        'details' => ['path' => $path]
                    ];
                }

                // Check for .htaccess protection
                if (!$requirements['web_accessible'] && !file_exists("{$path}/.htaccess")) {
                    $this->issues[] = [
                        'type' => 'security_issue',
                        'severity' => 'high',
                        'message' => "Directory {$dir} should be protected from web access",
                        'details' => ['path' => $path, 'solution' => 'Add .htaccess with Deny from all']
                    ];
                }
            }
        }
    }

    private function getPhpFiles(): array
    {
        $files = [];
        $directories = ['src/', 'config/', 'bin/'];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    private function findLinesWithPattern(string $content, string $pattern): array
    {
        $lines = explode("\n", $content);
        $matches = [];

        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $pattern) !== false) {
                $matches[] = [
                    'line' => $lineNumber + 1,
                    'content' => trim($line)
                ];
            }
        }

        return $matches;
    }

    private function generateReport(): void
    {
        echo "\n📊 PATHS AUDIT REPORT\n";
        echo "=====================\n\n";

        // Directory Structure Report
        echo "📁 DIRECTORY STRUCTURE:\n";
        foreach ($this->directoryStructure as $type => $paths) {
            echo "   {$type}: " . count($paths) . " directories\n";
            foreach ($paths as $path) {
                echo "      - {$path}\n";
            }
        }

        // Issues Report
        echo "\n⚠️  ISSUES FOUND: " . count($this->issues) . "\n";
        foreach ($this->issues as $issue) {
            $severity = strtoupper($issue['severity']);
            echo "   [{$severity}] {$issue['message']}\n";
            if (!empty($issue['details'])) {
                foreach ($issue['details'] as $detail) {
                    if (is_string($detail)) {
                        echo "      - {$detail}\n";
                    } elseif (is_array($detail)) {
                        echo "      - " . json_encode($detail) . "\n";
                    }
                }
            }
        }

        // Hardcoded Paths Report
        echo "\n🔧 HARDCODED PATHS: " . count($this->hardcodedPaths) . "\n";
        foreach ($this->hardcodedPaths as $hardcoded) {
            echo "   📄 {$hardcoded['file']}\n";
            echo "      Pattern: {$hardcoded['pattern']}\n";
            echo "      Issue: {$hardcoded['description']}\n";
            foreach ($hardcoded['lines'] as $line) {
                echo "      Line {$line['line']}: {$line['content']}\n";
            }
            echo "\n";
        }

        // Recommendations
        echo "\n💡 RECOMMENDATIONS:\n";
        echo "   1. Consolidate all var/ directories into single ./var/\n";
        echo "   2. Move storage/ outside of public/ for security\n";
        echo "   3. Replace hardcoded paths with Paths service calls\n";
        echo "   4. Add .htaccess protection to sensitive directories\n";
        echo "   5. Create path configuration file for centralized management\n";
        echo "   6. Implement path validation and sanitization\n";

        // Summary
        $highIssues = count(array_filter($this->issues, fn($i) => $i['severity'] === 'high'));
        $mediumIssues = count(array_filter($this->issues, fn($i) => $i['severity'] === 'medium'));
        
        echo "\n📈 SUMMARY:\n";
        echo "   High Priority Issues: {$highIssues}\n";
        echo "   Medium Priority Issues: {$mediumIssues}\n";
        echo "   Hardcoded Paths: " . count($this->hardcodedPaths) . "\n";
        echo "   Overall Status: " . ($highIssues > 0 ? "🔴 NEEDS ATTENTION" : "🟡 REVIEW NEEDED") . "\n";
    }
}

// Run the audit
$auditor = new PathsAuditor();
$auditor->runAudit();

echo "\n✅ Paths audit completed!\n";
