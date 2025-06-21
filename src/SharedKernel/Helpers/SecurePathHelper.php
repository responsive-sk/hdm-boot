<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Helpers;

use InvalidArgumentException;
use MvaBootstrap\SharedKernel\Services\PathsFactory;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Secure Path Helper.
 *
 * Provides secure file and directory operations with path traversal protection.
 * Adapted from the parent MVA project.
 */
final class SecurePathHelper
{
    /** @var array<string, string> */
    private array $allowedDirectories;

    /** @var array<string> */
    private array $forbiddenPaths;

    /** @var array<string, mixed> */
    private array $uploadRestrictions;

    public function __construct(private readonly Paths $paths)
    {
        // Use Paths service instead of loading config file directly
        $this->allowedDirectories = [
            'public'    => $this->paths->public(),
            'uploads'   => $this->paths->uploads(),
            'assets'    => $this->paths->assets(),
            'templates' => $this->paths->templates(),
            'storage'   => $this->paths->storage(),
            'cache'     => $this->paths->cache(),
            'logs'      => $this->paths->logs(),
            'config'    => $this->paths->config(),
            'var'       => $this->paths->storage(), // Use storage for var directory
        ];

        // Security configuration - these should be moved to a proper config service
        $this->forbiddenPaths = [
            '.env',
            '.git',
            'vendor',
            'config',
            'bootstrap',
        ];

        $this->uploadRestrictions = [
            'max_size'             => 5242880, // 5MB
            'allowed_extensions'   => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md'],
            'forbidden_extensions' => ['php', 'exe', 'bat', 'sh', 'js', 'html'],
        ];
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

        // Combine paths using PathsFactory for security
        $paths = PathsFactory::create();
        $fullPath = $paths->getPath($basePath, ltrim($relativePath, '/\\'));

        // Normalize the path
        $normalizedPath = $this->normalizePath($fullPath);

        // Use PathsFactory for secure path resolution instead of realpath
        $paths = PathsFactory::create();

        // Validate that the path is within allowed directory using string comparison
        if (!str_starts_with($normalizedPath, $basePath)) {
            throw new InvalidArgumentException(
                "Path '{$relativePath}' resolves outside the allowed directory '{$baseDirectory}'"
            );
        }

        // Return the normalized path - PathsFactory already handles security
        return $normalizedPath;
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
            if (!mkdir($directory, 0o755, true)) {
                throw new InvalidArgumentException("Failed to create directory for '{$relativePath}'");
            }
        }

        return file_put_contents($securePath, $content) !== false;
    }

    /**
     * Get allowed directories for file operations.
     *
     * @return array<string>
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
        $maxSize = $this->uploadRestrictions['max_size'];
        if (!is_int($maxSize)) {
            throw new InvalidArgumentException('Invalid max_size configuration');
        }
        if ($fileSize > $maxSize) {
            throw new InvalidArgumentException(
                'File size exceeds maximum allowed size of ' .
                $this->formatBytes($maxSize)
            );
        }

        // Check file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $forbiddenExtensions = $this->uploadRestrictions['forbidden_extensions'];
        $allowedExtensions = $this->uploadRestrictions['allowed_extensions'];

        if (!is_array($forbiddenExtensions) || !is_array($allowedExtensions)) {
            throw new InvalidArgumentException('Invalid extensions configuration');
        }

        if (in_array($extension, $forbiddenExtensions, true)) {
            throw new InvalidArgumentException("File extension '{$extension}' is not allowed");
        }

        if (!in_array($extension, $allowedExtensions, true)) {
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
        $path = preg_replace('#/+#', '/', $path) ?? $path;

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
     * Sanitize filename for safe storage.
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);

        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?? $filename;

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
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / (1024 ** $factor), $units[$factor]);
    }
}
