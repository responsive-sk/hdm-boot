<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Language\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Locale Value Object.
 *
 * Represents a locale identifier with validation and formatting.
 */
final readonly class Locale
{
    private const VALID_LOCALES = [
        'en_US' => 'English (United States)',
        'sk_SK' => 'Slovenčina (Slovensko)',
        'cs_CZ' => 'Čeština (Česká republika)',
        'de_DE' => 'Deutsch (Deutschland)',
        'fr_FR' => 'Français (France)',
        'es_ES' => 'Español (España)',
        'it_IT' => 'Italiano (Italia)',
        'pl_PL' => 'Polski (Polska)',
    ];

    public function __construct(
        public string $code
    ) {
        $this->validate($code);
    }

    /**
     * Create from string.
     */
    public static function fromString(string $code): self
    {
        return new self($code);
    }

    /**
     * Get default locale.
     */
    public static function default(): self
    {
        return new self('en_US');
    }

    /**
     * Get all available locales.
     *
     * @return array<string, string>
     */
    public static function getAvailable(): array
    {
        return self::VALID_LOCALES;
    }

    /**
     * Check if locale is valid.
     */
    public static function isValid(string $code): bool
    {
        return array_key_exists($code, self::VALID_LOCALES);
    }

    /**
     * Get locale display name.
     */
    public function getDisplayName(): string
    {
        return self::VALID_LOCALES[$this->code] ?? $this->code;
    }

    /**
     * Get language code (first part before underscore).
     */
    public function getLanguageCode(): string
    {
        return explode('_', $this->code)[0];
    }

    /**
     * Get country code (second part after underscore).
     */
    public function getCountryCode(): string
    {
        $parts = explode('_', $this->code);

        return $parts[1] ?? '';
    }

    /**
     * Check if this locale equals another.
     */
    public function equals(Locale $other): bool
    {
        return $this->code === $other->code;
    }

    /**
     * Convert to string.
     */
    public function toString(): string
    {
        return $this->code;
    }

    /**
     * Convert to string (magic method).
     */
    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * Validate locale code.
     */
    private function validate(string $code): void
    {
        if (empty($code)) {
            throw new InvalidArgumentException('Locale code cannot be empty');
        }

        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $code)) {
            throw new InvalidArgumentException(
                "Invalid locale format: '{$code}'. Expected format: 'xx_XX' (e.g., 'en_US')"
            );
        }

        if (!self::isValid($code)) {
            throw new InvalidArgumentException(
                "Unsupported locale: '{$code}'. Supported locales: " . implode(', ', array_keys(self::VALID_LOCALES))
            );
        }
    }
}
