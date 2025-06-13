<?php

declare(strict_types=1);

namespace MvaBootstrap\Modules\Core\Language\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Translation Key Value Object.
 *
 * Represents a translation key with validation and formatting.
 */
final readonly class TranslationKey
{
    public function __construct(
        public string $key
    ) {
        $this->validate($key);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $key): self
    {
        return new self($key);
    }

    /**
     * Check if key contains parameters.
     */
    public function hasParameters(): bool
    {
        return preg_match('/\{[^}]+\}/', $this->key) === 1;
    }

    /**
     * Extract parameter names from key.
     *
     * @return array<string>
     */
    public function getParameterNames(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->key, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Get namespace (part before first dot).
     */
    public function getNamespace(): ?string
    {
        $parts = explode('.', $this->key);

        return count($parts) > 1 ? $parts[0] : null;
    }

    /**
     * Get key without namespace.
     */
    public function getKeyWithoutNamespace(): string
    {
        $parts = explode('.', $this->key);

        return count($parts) > 1 ? implode('.', array_slice($parts, 1)) : $this->key;
    }

    /**
     * Check if this key equals another.
     */
    public function equals(TranslationKey $other): bool
    {
        return $this->key === $other->key;
    }

    /**
     * Convert to string.
     */
    public function toString(): string
    {
        return $this->key;
    }

    /**
     * Convert to string (magic method).
     */
    public function __toString(): string
    {
        return $this->key;
    }

    /**
     * Validate translation key.
     */
    private function validate(string $key): void
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Translation key cannot be empty');
        }

        if (strlen($key) > 255) {
            throw new InvalidArgumentException('Translation key cannot be longer than 255 characters');
        }

        // Allow alphanumeric, dots, underscores, hyphens, spaces, punctuation, and parameter placeholders
        if (!preg_match('/^[a-zA-Z0-9._\-{}\s!?.,;:()]+$/', $key)) {
            throw new InvalidArgumentException(
                "Invalid translation key format: '{$key}'. Only alphanumeric characters, dots, underscores, hyphens, spaces, basic punctuation, and parameter placeholders are allowed"
            );
        }
    }
}
