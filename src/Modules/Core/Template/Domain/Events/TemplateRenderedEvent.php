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
     * Get event identifier for tracking.
     */
    public function getEventId(): string
    {
        return 'template_rendered_' . $this->templateName->toString() . '_' . $this->occurredAt->getTimestamp();
    }

    /**
     * Get event name.
     */
    public function getEventName(): string
    {
        return 'template.rendered';
    }

    /**
     * Get event version for evolution.
     */
    public function getVersion(): int
    {
        return 1;
    }

    /**
     * Get event payload for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id'           => $this->getEventId(),
            'event_name'         => $this->getEventName(),
            'version'            => $this->getVersion(),
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
     * Get event data for logging.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        return [
            'event' => $this->getEventName(),
            'template' => $this->templateName->toString(),
            'render_time_ms' => round($this->renderTime * 1000, 2),
            'data_count' => $this->templateData->count(),
        ];
    }

    /**
     * Get event data (alias for backward compatibility).
     *
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return $this->toArray();
    }

    /**
     * Get occurred at timestamp.
     */
    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
