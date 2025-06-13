<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\Models;

use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\Locale;
use MvaBootstrap\Modules\Core\Language\Domain\ValueObjects\TranslationKey;

/**
 * Translation Domain Model.
 *
 * Represents a single translation entry.
 */
final class Translation
{
    public function __construct(
        private TranslationKey $key,
        private Locale $locale,
        private string $value,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    /**
     * Create new translation.
     */
    public static function create(
        TranslationKey $key,
        Locale $locale,
        string $value
    ): self {
        return new self($key, $locale, $value);
    }

    /**
     * Update translation value.
     */
    public function updateValue(string $value): self
    {
        return new self(
            $this->key,
            $this->locale,
            $value,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    /**
     * Get translation key.
     */
    public function getKey(): TranslationKey
    {
        return $this->key;
    }

    /**
     * Get locale.
     */
    public function getLocale(): Locale
    {
        return $this->locale;
    }

    /**
     * Get translation value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get created at timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get updated at timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Check if translation has parameters.
     */
    public function hasParameters(): bool
    {
        return $this->key->hasParameters();
    }

    /**
     * Get parameter names from key.
     *
     * @return array<string>
     */
    public function getParameterNames(): array
    {
        return $this->key->getParameterNames();
    }

    /**
     * Interpolate parameters into translation value.
     *
     * @param array<string, string> $parameters
     */
    public function interpolate(array $parameters = []): string
    {
        if (!$this->hasParameters()) {
            return $this->value;
        }

        $result = $this->value;
        foreach ($parameters as $name => $value) {
            $result = str_replace('{' . $name . '}', (string) $value, $result);
        }

        return $result;
    }

    /**
     * Check if this translation equals another.
     */
    public function equals(Translation $other): bool
    {
        return $this->key->equals($other->key)
            && $this->locale->equals($other->locale)
            && $this->value === $other->value;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key'        => $this->key->toString(),
            'locale'     => $this->locale->toString(),
            'value'      => $this->value,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
