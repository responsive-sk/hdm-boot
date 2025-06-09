<?php

declare(strict_types=1);

namespace MvaBootstrap\Helpers;

use InvalidArgumentException;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Secure Path Helper for MVA Bootstrap Application.
 *
 * Provides secure path operations with built-in protection against
 * path traversal attacks and directory restrictions.
 * Adapted from the parent project.
 */
final class SecurePathHelper
{
    private Paths $paths;
    /** @var array<string, string> */

    private array $allowedDirectories;

    public function __construct(Paths $paths)
    {
        $this->paths = $paths;

        // Define allowed base directories using Paths methods
        $this->allowedDirectories = [
            'public'   => $this->paths->public(),
            'uploads'  => $this->paths->uploads(),
            'storage'  => $this->paths->storage(),
            'cache'    => $this->paths->cache(),
            'logs'     => $this->paths->logs(),
            'config'   => $this->paths->config(),
            'var'      => $this->paths->base() . DIRECTORY_SEPARATOR . 'var',
            'sessions' => $this->paths->base() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'sessions',
        ];
    }

    /**
     * Securely resolve a file path within allowed directories.
     */
    public function securePath(string $relativePath, string $baseDirectory = 'public'): string
    {
        // Validate base directory
        if (!isset($this->allowedDirectories[$baseDirectory])) {
            throw new InvalidArgumentException("Base directory '{$baseDirectory}' is not allowed");
        }

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
     * Validate path traversal patterns.
     */
    private function validatePathTraversal(string $path): void
    {
        // Check for obvious path traversal patterns
        $dangerousPatterns = [
            '..',
            '/./',
            '//',
            '\\',
            chr(0), // null byte
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                throw new InvalidArgumentException("Path traversal detected in '{$path}'");
            }
        }

        // Check for encoded path traversal
        $decoded = urldecode($path);
        if ($decoded !== $path) {
            $this->validatePathTraversal($decoded);
        }
    }

    /**
     * Normalize path separators and remove redundant elements.
     */
    private function normalizePath(string $path): string
    {
        // Convert all separators to system separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Remove duplicate separators
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $path) ?? $path;

        return $path;
    }

    /**
     * Validate non-existent path structure.
     */
    private function validateNonExistentPath(string $normalizedPath, string $basePath): string
    {
        // Get the directory part
        $directory = dirname($normalizedPath);

        // Check if directory exists or can be created
        if (!is_dir($directory)) {
            // Validate that the directory would be within allowed bounds
            $realBasePath = realpath($basePath);
            if ($realBasePath === false) {
                throw new InvalidArgumentException("Base directory does not exist: {$basePath}");
            }

            // Check if the directory path is within bounds
            if (!str_starts_with($directory, $realBasePath)) {
                throw new InvalidArgumentException('Directory path is outside allowed bounds');
            }
        }

        return $normalizedPath;
    }

    /**
     * Get allowed directories.
     */
    /** @return array<string> */
    public function getAllowedDirectories(): array
    {
        return array_keys($this->allowedDirectories);
    }
}
