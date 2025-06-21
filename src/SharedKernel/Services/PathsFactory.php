<?php

declare(strict_types=1);

namespace MvaBootstrap\SharedKernel\Services;

use ResponsiveSk\Slim4Paths\Paths;

/**
 * Paths Factory.
 *
 * Centralized factory for creating Paths instances with proper configuration.
 * Eliminates the need for hardcoded path concatenation in modules.
 */
final class PathsFactory
{
    private static ?Paths $instance = null;

    /**
     * Get singleton Paths instance.
     */
    public static function create(): Paths
    {
        if (self::$instance === null) {
            self::$instance = self::createFromConfig();
        }

        return self::$instance;
    }

    /**
     * Create new Paths instance from configuration.
     */
    public static function createFromConfig(): Paths
    {
        $configFile = self::findConfigFile();

        if (!file_exists($configFile)) {
            throw new \RuntimeException("Paths config file not found: {$configFile}");
        }

        /** @var array{base_path: string, paths: array<string, string>} $config */
        $config = require $configFile;

        // @phpstan-ignore-next-line booleanOr.alwaysFalse
        if (!isset($config['base_path']) || !isset($config['paths'])) {
            throw new \RuntimeException('Invalid paths configuration');
        }

        // @phpstan-ignore-next-line booleanOr.alwaysFalse
        if (!is_string($config['base_path']) || !is_array($config['paths'])) {
            throw new \RuntimeException('Invalid paths configuration types');
        }

        return new Paths($config['base_path'], $config['paths']);
    }

    /**
     * Find paths config file by traversing up from current directory.
     */
    private static function findConfigFile(): string
    {
        // Start from the directory where this factory is located
        $currentDir = __DIR__;

        // Traverse up to find project root (where composer.json exists)
        while ($currentDir !== '/' && !file_exists($currentDir . '/composer.json')) {
            $currentDir = dirname($currentDir);
        }

        return $currentDir . '/config/paths.php';
    }

    /**
     * Reset singleton instance (useful for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Set custom Paths instance (useful for testing).
     */
    public static function setInstance(Paths $paths): void
    {
        self::$instance = $paths;
    }
}
