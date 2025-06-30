<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Infrastructure\Engines;

use HdmBoot\Modules\Core\Session\Services\CsrfService;
use HdmBoot\Modules\Core\Template\Domain\Contracts\TemplateEngineInterface;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * PHP Template Engine.
 *
 * Native PHP template engine implementation.
 */
final class PhpTemplateEngine implements TemplateEngineInterface
{
    private string $templatePath = 'templates';

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    public function __construct(
        private readonly CsrfService $csrfService,
        private readonly SessionInterface $session
    ) {
    }

    /**
     * Render template with data.
     */
    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->templatePath . '/' . $template;

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Add security helpers and session data
        $templateData = array_merge($data, [
            'csrf'    => $this->csrfService,
            'session' => $this->session,
            'user'    => $this->session->get('user_data'),
            'flash'   => [
                'success' => $this->session->getFlash()->get('success'),
                'error'   => $this->session->getFlash()->get('error'),
                'info'    => $this->session->getFlash()->get('info'),
            ],
            // Template helpers
            'escape' => [$this, 'escape'],
            'url'    => [$this, 'url'],
        ]);

        // Start output buffering
        ob_start();

        try {
            // Extract variables for template
            extract($templateData, EXTR_SKIP);

            // Include template
            include $templateFile;

            // Get content
            $content = ob_get_clean();

            if ($content === false) {
                throw new \RuntimeException('Failed to render template');
            }

            return $content;
        } catch (\Throwable $e) {
            ob_end_clean(); // Clean buffer on error
            throw $e;
        }
    }

    /**
     * Check if template exists.
     */
    public function templateExists(string $template): bool
    {
        $templateFile = $this->templatePath . '/' . $template;

        return file_exists($templateFile);
    }

    /**
     * Get template engine name.
     */
    public function getEngineName(): string
    {
        return 'php';
    }

    /**
     * Get supported file extensions.
     */
    public function getSupportedExtensions(): array
    {
        return ['php', 'phtml'];
    }

    /**
     * Configure template engine.
     */
    public function configure(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        if (isset($config['template_path']) && is_string($config['template_path'])) {
            $this->templatePath = $config['template_path'];
        }
    }

    /**
     * Escape HTML for output.
     */
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate URL for route.
     *
     * @param array<string, mixed> $params
     */
    public function url(string $route, array $params = []): string
    {
        // Simple URL generation - can be enhanced later
        $url = '/' . ltrim($route, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Get template path.
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * Set template path.
     */
    public function setTemplatePath(string $path): void
    {
        $this->templatePath = $path;
    }

    /**
     * Get configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
