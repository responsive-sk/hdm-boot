<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Template\Infrastructure\Engines;

use MvaBootstrap\Modules\Core\Template\Domain\Contracts\TemplateEngineInterface;

/**
 * Twig Template Engine.
 *
 * Twig template engine implementation (requires Twig package).
 */
final class TwigTemplateEngine implements TemplateEngineInterface
{
    private ?\Twig\Environment $twig = null;

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    public function __construct()
    {
        if (!class_exists('\Twig\Environment')) {
            throw new \RuntimeException(
                'Twig is not installed. Run: composer require twig/twig'
            );
        }
    }

    /**
     * Render template with data.
     */
    public function render(string $template, array $data = []): string
    {
        $twig = $this->getTwigEnvironment();

        try {
            return $twig->render($template, $data);
        } catch (\Twig\Error\Error $e) {
            throw new \RuntimeException(
                'Twig rendering error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if template exists.
     */
    public function templateExists(string $template): bool
    {
        $twig = $this->getTwigEnvironment();

        try {
            return $twig->getLoader()->exists($template);
        } catch (\Twig\Error\Error $e) {
            return false;
        }
    }

    /**
     * Get template engine name.
     */
    public function getEngineName(): string
    {
        return 'twig';
    }

    /**
     * Get supported file extensions.
     */
    public function getSupportedExtensions(): array
    {
        return ['twig', 'html.twig', 'htm.twig'];
    }

    /**
     * Configure template engine.
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        // Reset Twig environment to apply new configuration
        $this->twig = null;
    }

    /**
     * Get Twig environment.
     */
    private function getTwigEnvironment(): \Twig\Environment
    {
        if ($this->twig === null) {
            $this->initializeTwig();
        }

        return $this->twig;
    }

    /**
     * Initialize Twig environment.
     */
    private function initializeTwig(): void
    {
        // Use config values (paths will be resolved by service that creates this engine)
        $templatePath = $this->config['template_path'] ?? 'templates';
        $cacheEnabled = $this->config['cache_enabled'] ?? true;
        $cachePath = $this->config['cache_path'] ?? 'var/cache/twig';
        $debug = $this->config['debug'] ?? false;

        // Create loader
        $loader = new \Twig\Loader\FilesystemLoader($templatePath);

        // Create environment
        $this->twig = new \Twig\Environment($loader, [
            'cache'            => $cacheEnabled ? $cachePath : false,
            'debug'            => $debug,
            'auto_reload'      => $debug,
            'strict_variables' => true,
        ]);

        // Add debug extension if enabled
        if ($debug) {
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        // Add custom functions and filters
        $this->addCustomExtensions();
    }

    /**
     * Add custom Twig extensions.
     */
    private function addCustomExtensions(): void
    {
        // Add escape function
        $this->twig->addFunction(new \Twig\TwigFunction('escape', function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }));

        // Add URL function
        $this->twig->addFunction(new \Twig\TwigFunction('url', function (string $route, array $params = []): string {
            $url = '/' . ltrim($route, '/');
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            return $url;
        }));

        // Add date filter
        $this->twig->addFilter(new \Twig\TwigFilter('date_format', function (?\DateTimeInterface $date, string $format = 'Y-m-d H:i:s'): string {
            if ($date === null) {
                return '';
            }

            return $date->format($format);
        }));
    }

    /**
     * Get Twig environment for advanced usage.
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->getTwigEnvironment();
    }

    /**
     * Add global variable to Twig.
     */
    public function addGlobal(string $name, $value): void
    {
        $this->getTwigEnvironment()->addGlobal($name, $value);
    }

    /**
     * Add custom function to Twig.
     */
    public function addFunction(string $name, callable $callable): void
    {
        $function = new \Twig\TwigFunction($name, $callable);
        $this->getTwigEnvironment()->addFunction($function);
    }

    /**
     * Add custom filter to Twig.
     */
    public function addFilter(string $name, callable $callable): void
    {
        $filter = new \Twig\TwigFilter($name, $callable);
        $this->getTwigEnvironment()->addFilter($filter);
    }
}
