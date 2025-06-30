#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * FTPS Deployment Script for Shared Hosting
 * 
 * Deploys HDM Boot application to shared hosting via FTPS.
 * Optimized for shared hosting environments with limited access.
 * 
 * Usage: php bin/deploy-ftps.php [environment] [options]
 * 
 * Examples:
 *   php bin/deploy-ftps.php production
 *   php bin/deploy-ftps.php staging --dry-run
 *   php bin/deploy-ftps.php production --backup
 */

// Ensure we're running from project root
if (!file_exists(__DIR__ . '/../composer.json')) {
    echo "❌ Error: Must be run from project root\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

class FtpsDeployment
{
    private array $config;
    private bool $dryRun = false;
    private bool $createBackup = false;
    private string $environment;
    private string $buildDir;
    private string $timestamp;

    public function __construct(string $environment = 'production')
    {
        $this->environment = $environment;
        $this->timestamp = date('Y-m-d_H-i-s');
        $this->buildDir = sys_get_temp_dir() . '/hdm-boot-build-' . $this->timestamp;
        
        $this->loadConfig();
        $this->parseArguments();
    }

    public function deploy(): void
    {
        echo "🚀 Starting FTPS deployment to {$this->environment}\n";
        echo "=====================================\n\n";

        try {
            $this->validateEnvironment();
            $this->createProductionBuild();
            
            if ($this->createBackup) {
                $this->createRemoteBackup();
            }
            
            $this->uploadFiles();
            $this->runRemoteCommands();
            $this->verifyDeployment();
            
            echo "\n✅ Deployment completed successfully!\n";
            
        } catch (Exception $e) {
            echo "\n❌ Deployment failed: " . $e->getMessage() . "\n";
            $this->cleanup();
            exit(1);
        }
        
        $this->cleanup();
    }

    private function loadConfig(): void
    {
        $configFile = __DIR__ . "/../config/deploy/{$this->environment}.php";
        
        if (!file_exists($configFile)) {
            throw new Exception("Deployment config not found: {$configFile}");
        }
        
        $this->config = require $configFile;
        
        // Validate required config
        $required = ['host', 'username', 'password', 'remote_path'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new Exception("Missing required config: {$key}");
            }
        }
    }

    private function parseArguments(): void
    {
        global $argv;
        
        foreach ($argv as $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            }
            if ($arg === '--backup') {
                $this->createBackup = true;
            }
        }
        
        if ($this->dryRun) {
            echo "🔍 DRY RUN MODE - No actual changes will be made\n\n";
        }
    }

    private function validateEnvironment(): void
    {
        echo "🔍 Validating environment...\n";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            throw new Exception('PHP 8.3+ required');
        }
        
        // Check required extensions
        $required = ['ftp', 'openssl', 'zip'];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Required PHP extension missing: {$ext}");
            }
        }
        
        // Validate environment file
        $envFile = ".env.{$this->environment}";
        if (!file_exists($envFile)) {
            throw new Exception("Environment file not found: {$envFile}");
        }
        
        echo "  ✅ Environment validation passed\n";
    }

    private function createProductionBuild(): void
    {
        echo "📦 Creating production build...\n";
        
        // Create build directory
        if (!mkdir($this->buildDir, 0755, true)) {
            throw new Exception("Failed to create build directory");
        }
        
        // Copy project files (excluding development files)
        $this->copyProjectFiles();
        
        // Install production dependencies
        $this->installProductionDependencies();
        
        // Optimize for production
        $this->optimizeForProduction();
        
        // Create deployment package
        $this->createDeploymentPackage();
        
        echo "  ✅ Production build created\n";
    }

    private function copyProjectFiles(): void
    {
        $excludes = [
            '.git',
            '.github',
            'tests',
            'docs',
            $paths->logs(),
            $paths->cache(),
            'node_modules',
            '.env.example',
            '.env.dev',
            '.env.testing',
            'phpunit.xml',
            'phpstan.neon',
            '.php-cs-fixer.php',
        ];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $relativePath = $iterator->getSubPathName();
            
            // Skip excluded paths
            $skip = false;
            foreach ($excludes as $exclude) {
                if (strpos($relativePath, $exclude) === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;
            
            $targetPath = $this->buildDir . '/' . $relativePath;
            
            if ($file->isDir()) {
                mkdir($targetPath, 0755, true);
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($file->getPathname(), $targetPath);
            }
        }
    }

    private function installProductionDependencies(): void
    {
        $cmd = "cd {$this->buildDir} && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction";
        
        if ($this->dryRun) {
            echo "  [DRY RUN] Would run: {$cmd}\n";
            return;
        }
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Composer install failed: " . implode("\n", $output));
        }
    }

    private function optimizeForProduction(): void
    {
        // Copy production environment file
        $envSource = ".env.{$this->environment}";
        $envTarget = $this->buildDir . '/.env';
        
        if (!copy($envSource, $envTarget)) {
            throw new Exception("Failed to copy environment file");
        }
        
        // Create required directories
        $dirs = [$paths->logs(), $paths->cache(), 'var/storage', $paths->get('sessions')];
        foreach ($dirs as $dir) {
            $fullPath = $this->buildDir . '/' . $dir;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
        }
        
        // Set proper permissions
        chmod($this->buildDir . '/var', 0755);
        
        // Remove development files
        $devFiles = [
            'composer.lock',
            'package.json',
            'package-lock.json',
            'webpack.config.js',
        ];
        
        foreach ($devFiles as $file) {
            $filePath = $this->buildDir . '/' . $file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function createDeploymentPackage(): void
    {
        $packagePath = sys_get_temp_dir() . "/hdm-boot-{$this->environment}-{$this->timestamp}.zip";
        
        $zip = new ZipArchive();
        if ($zip->open($packagePath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Failed to create deployment package");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->buildDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($this->buildDir) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
        
        $zip->close();
        
        $this->config['package_path'] = $packagePath;
        echo "  📦 Package created: " . basename($packagePath) . "\n";
    }

    private function createRemoteBackup(): void
    {
        echo "💾 Creating remote backup...\n";
        
        if ($this->dryRun) {
            echo "  [DRY RUN] Would create backup of remote files\n";
            return;
        }
        
        // Implementation depends on hosting provider capabilities
        // Most shared hosting doesn't support remote backup via FTPS
        echo "  ⚠️  Remote backup not implemented for shared hosting\n";
        echo "  💡 Recommendation: Use hosting provider's backup tools\n";
    }

    private function uploadFiles(): void
    {
        echo "📤 Uploading files via FTPS...\n";
        
        if ($this->dryRun) {
            echo "  [DRY RUN] Would upload files to {$this->config['host']}\n";
            return;
        }
        
        // Connect to FTPS
        $connection = ftp_ssl_connect($this->config['host'], $this->config['port'] ?? 21);
        if (!$connection) {
            throw new Exception("Failed to connect to FTPS server");
        }
        
        // Login
        if (!ftp_login($connection, $this->config['username'], $this->config['password'])) {
            throw new Exception("FTPS login failed");
        }
        
        // Set passive mode
        ftp_pasv($connection, true);
        
        // Change to remote directory
        if (!ftp_chdir($connection, $this->config['remote_path'])) {
            throw new Exception("Failed to change to remote directory");
        }
        
        // Upload files
        $this->uploadDirectory($connection, $this->buildDir, '');
        
        ftp_close($connection);
        echo "  ✅ Files uploaded successfully\n";
    }

    private function uploadDirectory($connection, string $localDir, string $remoteDir): void
    {
        $iterator = new DirectoryIterator($localDir);
        
        foreach ($iterator as $file) {
            if ($file->isDot()) continue;
            
            $localPath = $file->getPathname();
            $remotePath = $remoteDir . '/' . $file->getFilename();
            
            if ($file->isDir()) {
                // Create remote directory
                @ftp_mkdir($connection, $remotePath);
                $this->uploadDirectory($connection, $localPath, $remotePath);
            } else {
                // Upload file
                if (!ftp_put($connection, $remotePath, $localPath, FTP_BINARY)) {
                    throw new Exception("Failed to upload: {$remotePath}");
                }
                echo "    📄 Uploaded: {$remotePath}\n";
            }
        }
    }

    private function runRemoteCommands(): void
    {
        echo "⚙️  Running remote commands...\n";
        
        if ($this->dryRun) {
            echo "  [DRY RUN] Would run remote initialization commands\n";
            return;
        }
        
        // Most shared hosting doesn't support SSH
        // Commands would need to be run via web interface or cPanel
        echo "  ⚠️  Remote command execution not available on shared hosting\n";
        echo "  💡 Manual steps required:\n";
        echo "     1. Initialize databases via web interface\n";
        echo "     2. Set file permissions via cPanel\n";
        echo "     3. Configure cron jobs if needed\n";
    }

    private function verifyDeployment(): void
    {
        echo "🔍 Verifying deployment...\n";
        
        if ($this->dryRun) {
            echo "  [DRY RUN] Would verify deployment\n";
            return;
        }
        
        // Basic HTTP check
        $url = $this->config['verify_url'] ?? null;
        if ($url) {
            $response = @file_get_contents($url);
            if ($response === false) {
                throw new Exception("Deployment verification failed - site not accessible");
            }
            echo "  ✅ Site is accessible\n";
        } else {
            echo "  ⚠️  No verify_url configured - manual verification required\n";
        }
    }

    private function cleanup(): void
    {
        if (is_dir($this->buildDir)) {
            $this->removeDirectory($this->buildDir);
        }
        
        if (isset($this->config['package_path']) && file_exists($this->config['package_path'])) {
            unlink($this->config['package_path']);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($dir);
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from command line\n";
    exit(1);
}

$environment = $argv[1] ?? 'production';
$deployment = new FtpsDeployment($environment);
$deployment->deploy();
