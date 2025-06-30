<?php

declare(strict_types=1);

namespace ResponsiveSk\Slim4Paths;

/**
 * Simple paths management for Slim 4 applications
 * 
 * Provides easy access to common application paths without external dependencies.
 * Designed to be lightweight and fast.
 */
class Paths
{
    private string $basePath;
    
    /** @var array<string, string> */
    private array $paths;

    /**
     * Constructor
     * 
     * @param string $basePath Application base path (usually __DIR__ or dirname(__DIR__))
     * @param array<string, string> $customPaths Optional custom paths to override defaults
     */
    public function __construct(string $basePath, array $customPaths = [])
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->initializePaths($customPaths);
    }

    /**
     * Initialize default paths with optional custom overrides
     * 
     * @param array<string, string> $customPaths
     */
    private function initializePaths(array $customPaths): void
    {
        $defaultPaths = [
            // Core directories
            'base' => $this->basePath,
            'config' => $this->basePath . '/config',
            'src' => $this->basePath . '/src',
            'public' => $this->basePath . '/public',

            // Template directories
            'templates' => $this->basePath . '/templates',
            'views' => $this->basePath . '/templates',
            'layouts' => $this->basePath . '/templates/layouts',
            'partials' => $this->basePath . '/templates/partials',

            // Content directories (Orbit CMS)
            'content' => $this->basePath . '/content',
            'articles' => $this->basePath . '/content/articles',
            'orbit' => $this->basePath . '/var/orbit',

            // Runtime directories
            'var' => $this->basePath . '/var',
            'cache' => $this->basePath . '/var/cache',
            'logs' => $this->basePath . '/var/logs',
            'storage' => $this->basePath . '/var/storage',
            'keys' => $this->basePath . '/var/keys',
            'exports' => $this->basePath . '/var/exports',
            'imports' => $this->basePath . '/var/imports',

            // Asset directories
            'assets' => $this->basePath . '/public/assets',
            'css' => $this->basePath . '/public/assets/css',
            'js' => $this->basePath . '/public/assets/js',
            'images' => $this->basePath . '/public/assets/images',
            'fonts' => $this->basePath . '/public/assets/fonts',
            'media' => $this->basePath . '/public/media',
            'uploads' => $this->basePath . '/public/uploads',

            // Development directories
            'tests' => $this->basePath . '/tests',
            'docs' => $this->basePath . '/docs',
            'scripts' => $this->basePath . '/scripts',
            'bin' => $this->basePath . '/bin',
            'vendor' => $this->basePath . '/vendor',

            // Localization directories
            'lang' => $this->basePath . '/resources/lang',
            'translations' => $this->basePath . '/resources/translations',
            'locales' => $this->basePath . '/resources/locales',
        ];

        $this->paths = array_merge($defaultPaths, $customPaths);
    }

    /**
     * Get path by name
     * 
     * @param string $name Path name (e.g., 'config', 'templates', 'logs')
     * @return string Full path
     * @throws \InvalidArgumentException If path name doesn't exist
     */
    public function get(string $name): string
    {
        if (!isset($this->paths[$name])) {
            throw new \InvalidArgumentException("Path '{$name}' not found");
        }

        return $this->paths[$name];
    }

    /**
     * Create path relative to base path
     * 
     * @param string $relativePath Relative path from base
     * @return string Full path
     */
    public function path(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/\\');
    }

    /**
     * Check if path name exists
     */
    public function has(string $name): bool
    {
        return isset($this->paths[$name]);
    }

    /**
     * Get all configured paths
     * 
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->paths;
    }

    /**
     * Get base path
     */
    public function base(): string
    {
        return $this->get('base');
    }

    /**
     * Get config path with optional file
     */
    public function config(string $file = ''): string
    {
        $path = $this->get('config');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get templates path with optional file
     */
    public function templates(string $file = ''): string
    {
        $path = $this->get('templates');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get public path with optional file
     */
    public function public(string $file = ''): string
    {
        $path = $this->get('public');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get logs path with optional file
     */
    public function logs(string $file = ''): string
    {
        $path = $this->get('logs');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get storage path with optional file
     */
    public function storage(string $file = ''): string
    {
        $path = $this->get('storage');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get cache path with optional file
     */
    public function cache(string $file = ''): string
    {
        $path = $this->get('cache');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get assets path with optional file
     */
    public function assets(string $file = ''): string
    {
        $path = $this->get('assets');
        return $file ? $path . '/' . ltrim($file, '/\\') : $path;
    }

    /**
     * Get uploads path with optional file
     */
    public function uploads(string $file = ''): string
    {
        $path = $this->get('uploads');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get content path with optional file
     */
    public function content(string $file = ''): string
    {
        $path = $this->get('content');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get articles path with optional file
     */
    public function articles(string $file = ''): string
    {
        $path = $this->get('articles');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get src path with optional file
     */
    public function src(string $file = ''): string
    {
        $path = $this->get('src');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get orbit path with optional file
     */
    public function orbit(string $file = ''): string
    {
        $path = $this->get('orbit');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get orbit database path
     */
    public function orbitDatabase(string $name): string
    {
        return $this->getPath($this->orbit(), $name . '.db');
    }

    /**
     * Get module config path
     */
    public function moduleConfig(string $module): string
    {
        return $this->getPath($this->src('Modules'), $module, 'config.php');
    }

    /**
     * Get module routes path
     */
    public function moduleRoutes(string $module): string
    {
        return $this->getPath($this->src('Modules'), $module, 'routes.php');
    }

    /**
     * Get module templates path
     */
    public function moduleTemplates(string $module, string $template = ''): string
    {
        $path = $this->getPath($this->src('Modules'), $module, 'templates');
        return $template ? $this->getPath($path, $template) : $path;
    }

    /**
     * Get views path with optional template
     */
    public function views(string $template = ''): string
    {
        $path = $this->get('views');
        return $template ? $this->getPath($path, $template) : $path;
    }

    /**
     * Get layouts path with optional layout
     */
    public function layouts(string $layout = ''): string
    {
        $path = $this->get('layouts');
        return $layout ? $this->getPath($path, $layout) : $path;
    }

    /**
     * Get partials path with optional partial
     */
    public function partials(string $partial = ''): string
    {
        $path = $this->get('partials');
        return $partial ? $this->getPath($path, $partial) : $path;
    }

    /**
     * Get CSS assets path with optional file
     */
    public function css(string $file = ''): string
    {
        $path = $this->get('css');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get JavaScript assets path with optional file
     */
    public function js(string $file = ''): string
    {
        $path = $this->get('js');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get images assets path with optional file
     */
    public function images(string $file = ''): string
    {
        $path = $this->get('images');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get fonts assets path with optional file
     */
    public function fonts(string $file = ''): string
    {
        $path = $this->get('fonts');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get media path with optional file
     */
    public function media(string $file = ''): string
    {
        $path = $this->get('media');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get docs path with optional file
     */
    public function docs(string $file = ''): string
    {
        $path = $this->get('docs');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get scripts path with optional file
     */
    public function scripts(string $file = ''): string
    {
        $path = $this->get('scripts');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get bin path with optional file (for executable scripts)
     */
    public function bin(string $file = ''): string
    {
        $path = $this->get('bin');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get keys path with optional file (for security keys)
     */
    public function keys(string $file = ''): string
    {
        $path = $this->get('keys');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get exports path with optional file
     */
    public function exports(string $file = ''): string
    {
        $path = $this->get('exports');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get imports path with optional file
     */
    public function imports(string $file = ''): string
    {
        $path = $this->get('imports');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get language path with locale and optional file
     */
    public function lang(string $locale, string $file = ''): string
    {
        $path = $this->getPath($this->get('lang'), $locale);
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get translations path with optional file
     */
    public function translations(string $file = ''): string
    {
        $path = $this->get('translations');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Get locales path
     */
    public function locales(): string
    {
        return $this->get('locales');
    }

    /**
     * Get tests path with optional file
     */
    public function tests(string $file = ''): string
    {
        $path = $this->get('tests');
        return $file ? $this->getPath($path, $file) : $path;
    }

    /**
     * Secure path joining method.
     *
     * Prevents path traversal attacks by validating relative paths.
     * This is the MAIN method that should be used for all path operations.
     *
     * @param string $basePath Base directory path
     * @param string $relativePath Relative path to join
     * @return string Secure joined path
     * @throws \InvalidArgumentException If path traversal is detected
     */
    public function getPath(string $basePath, string $relativePath): string
    {
        // Validate relative path for security
        if (str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException("Path traversal detected in: {$relativePath}");
        }

        if (str_contains($relativePath, '~')) {
            throw new \InvalidArgumentException("Home directory access not allowed: {$relativePath}");
        }

        // Clean and normalize the path
        $relativePath = ltrim($relativePath, '/\\');

        // Use DIRECTORY_SEPARATOR for cross-platform compatibility
        return $basePath . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * Create Paths instance from current file location
     *
     * Convenience method to create a Paths instance by going up a specified number
     * of directory levels from the current file location. This is especially useful
     * in modular systems where you need to reference the project root.
     *
     * @param string $dir Starting directory (usually __DIR__)
     * @param int $levelsUp Number of directory levels to go up (default: 3)
     * @return self New Paths instance
     * @throws \RuntimeException If the resolved path is invalid
     *
     * @example
     * // From a file at src/Modules/Core/SomeClass.php, go up 3 levels to project root
     * $paths = Paths::fromHere(__DIR__, 3);
     *
     * // From a file at src/Services/SomeService.php, go up 2 levels to project root
     * $paths = Paths::fromHere(__DIR__, 2);
     *
     * // Use resolved paths
     * $dbPath = $paths->storage('database.db');
     * $logPath = $paths->logs('app.log');
     */
    public static function fromHere(string $dir = __DIR__, int $levelsUp = 3): self
    {
        // Build the relative path string for going up directories
        $upPath = str_repeat('/..', $levelsUp);

        // Resolve the absolute path
        $basePath = realpath($dir . $upPath);

        if ($basePath === false || !is_string($basePath)) {
            throw new \RuntimeException(
                "Could not resolve base path from: {$dir} going up {$levelsUp} levels. " .
                "Attempted path: {$dir}{$upPath}"
            );
        }

        // Verify the resolved path exists and is a directory
        if (!is_dir($basePath)) {
            throw new \RuntimeException(
                "Resolved path is not a directory: {$basePath}"
            );
        }

        return new self($basePath);
    }

    /**
     * Create Paths instance from environment variable
     *
     * Reads the base path from an environment variable. Useful for applications
     * that need to configure the base path via environment configuration.
     *
     * @param string $envVar Environment variable name (default: APP_BASE_PATH)
     * @return self New Paths instance
     * @throws \RuntimeException If environment variable is not set or path is invalid
     *
     * @example
     * // Set environment variable
     * putenv('APP_BASE_PATH=/path/to/project');
     *
     * // Create Paths instance from environment
     * $paths = Paths::fromEnv();
     *
     * // Or use custom environment variable
     * $paths = Paths::fromEnv('BASE_PATH');
     */
    public static function fromEnv(string $envVar = 'APP_BASE_PATH'): self
    {
        $basePath = getenv($envVar);

        if ($basePath === false || $basePath === '') {
            throw new \RuntimeException("Environment variable '{$envVar}' is not set or empty");
        }

        // Resolve and validate the path
        $resolvedPath = realpath($basePath);

        if ($resolvedPath === false || !is_string($resolvedPath)) {
            throw new \RuntimeException(
                "Environment variable '{$envVar}' contains invalid path: {$basePath}"
            );
        }

        if (!is_dir($resolvedPath)) {
            throw new \RuntimeException(
                "Environment variable '{$envVar}' path is not a directory: {$resolvedPath}"
            );
        }

        return new self($resolvedPath);
    }


}
