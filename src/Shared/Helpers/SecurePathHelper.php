<?php

declare(strict_types=1);

namespace MvaBootstrap\Shared\Helpers;

use InvalidArgumentException;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Secure Path Helper.
 * 
 * Provides secure file and directory operations with path traversal protection.
 * Adapted from the parent MVA project.
 */
final class SecurePathHelper
{
    private array $allowedDirectories;
    private array $forbiddenPaths;
    private array $uploadRestrictions;

    public function __construct(private readonly Paths $paths)
    {
        $pathsConfig = require __DIR__ . '/../../../config/paths.php';
        
        $this->allowedDirectories = $pathsConfig['paths'];
        $this->forbiddenPaths = $pathsConfig['security']['forbidden_paths'];
        $this->uploadRestrictions = $pathsConfig['security']['upload_restrictions'];
    }

    /**
     * Get a secure path within an allowed directory.
     */
    public function securePath(string $relativePath, string $baseDirectory = 'public'): string
    {
        // Validate base directory
        if (!isset($this->allowedDirectories[$baseDirectory])) {
            throw new InvalidArgumentException("Base directory '{$baseDirectory}' is not allowed");
        }

        // Check for forbidden paths
        foreach ($this->forbiddenPaths as $forbiddenPath) {
            if (str_starts_with($relativePath, $forbiddenPath)) {
                throw new InvalidArgumentException("Access to '{$relativePath}' is forbidden");
            }
        }

        // Normalize the relative path
        $relativePath = $this->normalizePath($relativePath);

        // Check for path traversal patterns
        $this->validatePathTraversal($relativePath);

        // Get the base directory path
        $basePath = $this->allowedDirectories[$baseDirectory];

        // Combine paths
        $fullPath = $basePath . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');

        // Normalize the path
        $normalizedPath = $this->normalizePath($fullPath);

        // Use realpath to resolve any remaining .. or . components
        $realPath = realpath($normalizedPath);

        // If file doesn't exist, validate the directory structure
        if ($realPath === false) {
            $realPath = $this->validateNonExistentPath($normalizedPath, $basePath);
        }

        // Ensure the resolved path is still within the allowed directory
        $realBasePath = realpath($basePath);
        if ($realBasePath === false || !str_starts_with($realPath, $realBasePath)) {
            throw new InvalidArgumentException(
                "Path '{$relativePath}' resolves outside the allowed directory '{$baseDirectory}'"
            );
        }

        return $realPath;
    }

    /**
     * Check if a file exists securely.
     */
    public function fileExists(string $relativePath, string $baseDirectory = 'public'): bool
    {
        try {
            $securePath = $this->securePath($relativePath, $baseDirectory);
            return file_exists($securePath);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Read file contents securely.
     */
    public function readFile(string $relativePath, string $baseDirectory = 'public'): string
    {
        $securePath = $this->securePath($relativePath, $baseDirectory);

        if (!file_exists($securePath)) {
            throw new InvalidArgumentException("File '{$relativePath}' does not exist");
        }

        if (!is_readable($securePath)) {
            throw new InvalidArgumentException("File '{$relativePath}' is not readable");
        }

        $content = file_get_contents($securePath);
        if ($content === false) {
            throw new InvalidArgumentException("Failed to read file '{$relativePath}'");
        }

        return $content;
    }

    /**
     * Write file contents securely.
     */
    public function writeFile(string $relativePath, string $content, string $baseDirectory = 'var'): bool
    {
        $securePath = $this->securePath($relativePath, $baseDirectory);

        // Ensure directory exists
        $directory = dirname($securePath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new InvalidArgumentException("Failed to create directory for '{$relativePath}'");
            }
        }

        return file_put_contents($securePath, $content) !== false;
    }

    /**
     * Get allowed directories for file operations.
     */
    public function getAllowedDirectories(): array
    {
        return array_keys($this->allowedDirectories);
    }

    /**
     * Get upload path for files.
     */
    public function getUploadPath(string $filename): string
    {
        $sanitizedFilename = $this->sanitizeFilename($filename);
        return $this->securePath('uploads/' . $sanitizedFilename, 'var');
    }

    /**
     * Validate file upload.
     */
    public function validateUpload(string $filename, int $fileSize): void
    {
        // Check file size
        if ($fileSize > $this->uploadRestrictions['max_size']) {
            throw new InvalidArgumentException(
                'File size exceeds maximum allowed size of ' . 
                $this->formatBytes($this->uploadRestrictions['max_size'])
            );
        }

        // Check file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($extension, $this->uploadRestrictions['forbidden_extensions'], true)) {
            throw new InvalidArgumentException("File extension '{$extension}' is not allowed");
        }

        if (!in_array($extension, $this->uploadRestrictions['allowed_extensions'], true)) {
            throw new InvalidArgumentException("File extension '{$extension}' is not allowed");
        }
    }

    /**
     * Normalize path separators and remove redundant components.
     */
    private function normalizePath(string $path): string
    {
        // Convert all separators to forward slashes
        $path = str_replace('\\', '/', $path);
        
        // Remove multiple consecutive slashes
        $path = preg_replace('#/+#', '/', $path);
        
        return $path;
    }

    /**
     * Validate path for traversal attempts.
     */
    private function validatePathTraversal(string $path): void
    {
        // Check for obvious path traversal patterns
        $dangerousPatterns = ['../', '..\\', '../', '..\\'];
        
        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                throw new InvalidArgumentException("Path traversal detected in '{$path}'");
            }
        }

        // Check for encoded path traversal
        $decodedPath = urldecode($path);
        if ($decodedPath !== $path) {
            $this->validatePathTraversal($decodedPath);
        }
    }

    /**
     * Validate non-existent path structure.
     */
    private function validateNonExistentPath(string $path, string $basePath): string
    {
        $directory = dirname($path);
        $realDirectory = realpath($directory);
        
        if ($realDirectory === false) {
            // Directory doesn't exist, check if we can create it
            $realBasePath = realpath($basePath);
            if ($realBasePath === false) {
                throw new InvalidArgumentException("Base directory does not exist");
            }
            
            // Validate that the directory would be within bounds
            if (!str_starts_with($directory, $realBasePath)) {
                throw new InvalidArgumentException("Directory would be outside allowed base path");
            }
            
            return $path;
        }
        
        return $realDirectory . DIRECTORY_SEPARATOR . basename($path);
    }

    /**
     * Sanitize filename for safe storage.
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Ensure filename is not empty
        if (empty($filename) || $filename === '.') {
            $filename = 'file_' . time();
        }
        
        return $filename;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        
        return sprintf("%.1f %s", $bytes / (1024 ** $factor), $units[$factor]);
    }
}
