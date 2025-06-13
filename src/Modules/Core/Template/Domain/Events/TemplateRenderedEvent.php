<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Template\Domain\Events;

use MvaBootstrap\Modules\Core\Template\Domain\ValueObjects\TemplateData;
use MvaBootstrap\Modules\Core\Template\Domain\ValueObjects\TemplateName;
use MvaBootstrap\SharedKernel\Events\DomainEvent;

/**
 * Template Rendered Event.
 *
 * Fired when a template is successfully rendered.
 */
final readonly class TemplateRenderedEvent implements DomainEvent
{
    public function __construct(
        public TemplateName $templateName,
        public TemplateData $templateData,
        public float $renderTime,
        public \DateTimeImmutable $occurredAt
    ) {
    }

    /**
     * Create template rendered event.
     */
    public static function create(
        TemplateName $templateName,
        TemplateData $templateData,
        float $renderTime
    ): self {
        return new self(
            $templateName,
            $templateData,
            $renderTime,
            new \DateTimeImmutable()
        );
    }

    /**
     * Get event name.
     */
    public function getEventName(): string
    {
        return 'template.rendered';
    }

    /**
     * Get event data.
     */
    public function getEventData(): array
    {
        return [
            'template_name'      => $this->templateName->toString(),
            'template_directory' => $this->templateName->getDirectory(),
            'template_extension' => $this->templateName->getExtension(),
            'data_keys'          => $this->templateData->getKeys(),
            'data_count'         => $this->templateData->count(),
            'render_time_ms'     => round($this->renderTime * 1000, 2),
            'occurred_at'        => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get occurred at timestamp.
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
