<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Application\DTOs;

/**
 * Translate Request DTO.
 *
 * Data Transfer Object for translation requests.
 */
final readonly class TranslateRequest
{
    /**
     * @param array<string, string> $parameters
     */
    public function __construct(
        public string $key,
        public ?string $locale = null,
        public array $parameters = []
    ) {
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: (string) ($data['key'] ?? $data['text'] ?? ''),
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            parameters: (array) ($data['parameters'] ?? $data['params'] ?? [])
        );
    }

    /**
     * Validate request data.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->key)) {
            $errors[] = 'Translation key is required';
        }

        if ($this->locale !== null && !preg_match('/^[a-z]{2}_[A-Z]{2}$/', $this->locale)) {
            $errors[] = 'Invalid locale format. Expected format: xx_XX (e.g., en_US)';
        }

        if (!is_array($this->parameters)) {
            $errors[] = 'Parameters must be an array';
        }

        return $errors;
    }

    /**
     * Check if request is valid.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
