<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Template\Application\DTOs;

/**
 * Render Template Request DTO.
 *
 * Data Transfer Object for template rendering requests.
 */
final readonly class RenderTemplateRequest
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $template,
        public array $data = [],
        public ?string $contentType = null
    ) {
        $this->validate();
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $requestData
     */
    public static function fromArray(array $requestData): self
    {
        return new self(
            $requestData['template'] ?? '',
            $requestData['data'] ?? [],
            $requestData['content_type'] ?? null
        );
    }

    /**
     * Validate request data.
     */
    private function validate(): void
    {
        if (empty($this->template)) {
            throw new \InvalidArgumentException('Template name is required');
        }

        if (!is_array($this->data)) {
            throw new \InvalidArgumentException('Template data must be an array');
        }
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'template'     => $this->template,
            'data'         => $this->data,
            'content_type' => $this->contentType,
        ];
    }
}
