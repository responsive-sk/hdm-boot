<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Application\DTOs;

/**
 * Language Settings Request DTO.
 *
 * Data Transfer Object for language settings requests.
 */
final readonly class LanguageSettingsRequest
{
    public function __construct(
        public ?string $locale = null,
        public ?string $action = null
    ) {
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Safe extraction of locale
        $localeValue = $data['locale'] ?? null;
        $locale = $localeValue !== null && is_string($localeValue) ? $localeValue : null;

        // Safe extraction of action
        $actionValue = $data['action'] ?? null;
        $action = $actionValue !== null && is_string($actionValue) ? $actionValue : null;

        return new self(
            locale: $locale,
            action: $action
        );
    }

    /**
     * Check if this is a get request.
     */
    public function isGetRequest(): bool
    {
        return $this->action === null || $this->action === 'get';
    }

    /**
     * Check if this is a set request.
     */
    public function isSetRequest(): bool
    {
        return $this->action === 'set' || ($this->action === null && $this->locale !== null);
    }

    /**
     * Validate request data.
     *
     * @return array<string> Validation errors
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->isSetRequest() && empty($this->locale)) {
            $errors[] = 'Locale is required when setting language';
        }

        if ($this->locale !== null && !preg_match('/^[a-z]{2}_[A-Z]{2}$/', $this->locale)) {
            $errors[] = 'Invalid locale format. Expected format: xx_XX (e.g., en_US)';
        }

        if ($this->action !== null && !in_array($this->action, ['get', 'set'], true)) {
            $errors[] = 'Invalid action. Allowed actions: get, set';
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
