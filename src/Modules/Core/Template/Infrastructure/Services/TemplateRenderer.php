<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Infrastructure\Services;

use HdmBoot\Modules\Core\Session\Services\CsrfService;
use Psr\Http\Message\ResponseInterface;
use ResponsiveSk\Slim4Paths\Paths;
use ResponsiveSk\Slim4Session\SessionInterface;

/**
 * Simple Template Renderer.
 *
 * Renders PHP templates with security features.
 */
final class TemplateRenderer
{
    /** @var array<string, mixed> */
    private array $globalData = [];

    public function __construct(
        private readonly string $templatePath,
        private readonly CsrfService $csrfService,
        private readonly SessionInterface $session,
        private readonly Paths $paths
    ) {
    }

    /**
     * Render template.
     *
     * @param array<string, mixed> $data
     */
    public function render(
        ResponseInterface $response,
        string $template,
        array $data = []
    ): ResponseInterface {
        // Use Paths for secure file access (fallback to templatePath if needed)
        $templateFile = $this->paths->templates($template);

        // Fallback to templatePath if Paths doesn't work
        if (!file_exists($templateFile) && !empty($this->templatePath)) {
            $templateFile = $this->paths->getPath($this->templatePath, $template);
        }

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Merge global data with template data
        $templateData = array_merge($this->globalData, $data);

        // Add security helpers
        $templateData['csrf'] = $this->csrfService;
        $templateData['session'] = $this->session;
        $templateData['user'] = $this->session->get('user_data');
        $templateData['flash'] = [
            'success' => $this->session->getFlash()->get('success'),
            'error'   => $this->session->getFlash()->get('error'),
            'info'    => $this->session->getFlash()->get('info'),
        ];

        // Start output buffering
        ob_start();

        // Extract variables for template
        extract($templateData, EXTR_SKIP);

        // Include template
        include $templateFile;

        // Get content
        $content = ob_get_clean();

        if ($content === false) {
            throw new \RuntimeException('Failed to render template');
        }

        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Add global data available to all templates.
     *
     * @param array<string, mixed> $data
     */
    public function addGlobalData(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    /**
     * Set global data.
     *
     * @param array<string, mixed> $data
     */
    public function setGlobalData(array $data): void
    {
        $this->globalData = $data;
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
     */
    /**
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
     * Render module template.
     *
     * @param array<string, mixed> $data
     */
    public function renderModule(
        ResponseInterface $response,
        string $module,
        string $template,
        array $data = []
    ): ResponseInterface {
        // Use Paths for secure module template access
        $templateFile = $this->paths->moduleTemplates($module, $template);

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Module template not found: {$module}/{$template}");
        }

        // Merge global data with template data
        $templateData = array_merge($this->globalData, $data);

        // Add security helpers
        $templateData['csrf'] = $this->csrfService;
        $templateData['session'] = $this->session;
        $templateData['user'] = $this->session->get('user_data');
        $templateData['flash'] = [
            'success' => $this->session->getFlash()->get('success'),
            'error'   => $this->session->getFlash()->get('error'),
            'info'    => $this->session->getFlash()->get('info'),
        ];

        // Start output buffering
        ob_start();

        // Extract variables for template
        extract($templateData, EXTR_SKIP);

        // Include template
        include $templateFile;

        // Get content
        $content = ob_get_clean();

        if ($content === false) {
            throw new \RuntimeException('Failed to render module template');
        }

        $response->getBody()->write($content);

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
