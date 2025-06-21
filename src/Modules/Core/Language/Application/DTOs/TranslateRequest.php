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
        // Safe extraction of key
        $keyValue = $data['key'] ?? $data['text'] ?? '';
        $key = is_string($keyValue) ? $keyValue : '';

        // Safe extraction of locale
        $localeValue = $data['locale'] ?? null;
        $locale = $localeValue !== null && is_string($localeValue) ? $localeValue : null;

        // Safe extraction of parameters
        $parametersValue = $data['parameters'] ?? $data['params'] ?? [];
        /** @var array<string, string> $parameters */
        $parameters = is_array($parametersValue) ? $parametersValue : [];

        return new self(
            key: $key,
            locale: $locale,
            parameters: $parameters
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

        // Parameters are always array due to constructor type hint

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
