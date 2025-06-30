<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Template\Domain\Services;

use HdmBoot\Modules\Core\Template\Domain\Contracts\TemplateEngineInterface;
use HdmBoot\Modules\Core\Template\Domain\Contracts\TemplateRendererInterface;
use HdmBoot\Modules\Core\Template\Domain\Events\TemplateRenderedEvent;
use HdmBoot\Modules\Core\Template\Domain\ValueObjects\TemplateData;
use HdmBoot\Modules\Core\Template\Domain\ValueObjects\TemplateName;
use HdmBoot\SharedKernel\Events\ModuleEventBus;
use Psr\Log\LoggerInterface;

/**
 * Template Service.
 *
 * Core business logic for template operations.
 */
final class TemplateService implements TemplateRendererInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $globalVariables = [];

    private string $templateDirectory = 'templates';

    private bool $cacheEnabled = true;

    public function __construct(
        private readonly TemplateEngineInterface $templateEngine,
        private readonly ModuleEventBus $eventBus,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Render template with data.
     */
    public function render(string $template, array $data = []): string
    {
        $templateName = TemplateName::fromString($template);
        $templateData = TemplateData::fromArray($data);

        $this->logger->debug('Rendering template', [
            'template'         => $templateName->toString(),
            'data_keys'        => $templateData->getKeys(),
            'global_variables' => array_keys($this->globalVariables),
        ]);

        // Validate template exists
        if (!$this->exists($template)) {
            throw new \RuntimeException("Template '{$template}' not found");
        }

        // Merge global variables with template data
        $mergedData = $templateData->merge(
            TemplateData::fromArray($this->globalVariables)
        );

        $startTime = microtime(true);

        try {
            // Render template using engine
            $content = $this->templateEngine->render(
                $templateName->toString(),
                $mergedData->toArray()
            );

            $renderTime = microtime(true) - $startTime;

            $this->logger->info('Template rendered successfully', [
                'template'       => $templateName->toString(),
                'render_time'    => round($renderTime * 1000, 2) . 'ms',
                'content_length' => strlen($content),
            ]);

            // Publish template rendered event
            $event = TemplateRenderedEvent::create(
                $templateName,
                $templateData,
                $renderTime
            );
            $this->eventBus->publish('Template', $event);

            return $content;
        } catch (\Throwable $e) {
            $this->logger->error('Template rendering failed', [
                'template' => $templateName->toString(),
                'error'    => $e->getMessage(),
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
            ]);

            throw new \RuntimeException(
                "Failed to render template '{$template}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check if template exists.
     */
    public function exists(string $template): bool
    {
        $templateName = TemplateName::fromString($template);

        // Add default extension if not present
        if (!$templateName->hasExtension()) {
            $extensions = $this->templateEngine->getSupportedExtensions();
            foreach ($extensions as $extension) {
                $templateWithExt = $templateName->withExtension($extension);
                if ($this->templateEngine->templateExists($templateWithExt->toString())) {
                    return true;
                }
            }

            return false;
        }

        return $this->templateEngine->templateExists($templateName->toString());
    }

    /**
     * Add global template variable.
     */
    public function addGlobal(string $name, $value): void
    {
        $this->globalVariables[$name] = $value;

        $this->logger->debug('Global template variable added', [
            'name' => $name,
            'type' => gettype($value),
        ]);
    }

    /**
     * Remove global template variable.
     */
    public function removeGlobal(string $name): void
    {
        unset($this->globalVariables[$name]);

        $this->logger->debug('Global template variable removed', [
            'name' => $name,
        ]);
    }

    /**
     * Get all global variables.
     *
     * @return array<string, mixed>
     */
    public function getGlobalVariables(): array
    {
        return $this->globalVariables;
    }

    /**
     * Set template directory.
     */
    public function setTemplateDirectory(string $directory): void
    {
        $this->templateDirectory = $directory;

        $this->logger->debug('Template directory changed', [
            'directory' => $directory,
        ]);
    }

    /**
     * Get template directory.
     */
    public function getTemplateDirectory(): string
    {
        return $this->templateDirectory;
    }

    /**
     * Enable or disable template caching.
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;

        $this->logger->debug('Template caching changed', [
            'enabled' => $enabled,
        ]);
    }

    /**
     * Check if template caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Clear template cache.
     */
    public function clearCache(): void
    {
        // Implementation depends on template engine
        $this->logger->info('Template cache cleared');
    }

    /**
     * Render template with type-safe value objects.
     */
    public function renderWithValueObjects(
        TemplateName $templateName,
        TemplateData $templateData
    ): string {
        return $this->render(
            $templateName->toString(),
            $templateData->toArray()
        );
    }

    /**
     * Get template engine information.
     *
     * @return array<string, mixed>
     */
    public function getEngineInfo(): array
    {
        return [
            'name'                   => $this->templateEngine->getEngineName(),
            'supported_extensions'   => $this->templateEngine->getSupportedExtensions(),
            'cache_enabled'          => $this->cacheEnabled,
            'template_directory'     => $this->templateDirectory,
            'global_variables_count' => count($this->globalVariables),
        ];
    }
}
